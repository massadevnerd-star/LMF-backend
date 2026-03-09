<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Api\VerifyEmailController;

class AuthController extends Controller
{
    // Register User
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Default role: Parent
        $user->assignRole('parent');

        // Invia email di verifica
        VerifyEmailController::sendVerificationEmail($user);

        return response()->json([
            'message' => 'Registrazione completata. Controlla la tua casella di posta per verificare il tuo indirizzo email.',
            'requiresVerification' => true
        ]);
    }

    // Login User
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenziali non valide.'
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email non verificata. Controlla la tua casella di posta.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Log successful login
        activity()
            ->causedBy($user)
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'login_method' => 'email',
            ])
            ->log('User logged in');

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('roles'),
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        // Check if token is transient (SPA/Session based)
        $accessToken = $request->user()->currentAccessToken();

        if ($accessToken instanceof \Laravel\Sanctum\TransientToken) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } else {
            // API Token
            $accessToken->delete();
        }

        // Log logout
        activity()
            ->causedBy($request->user())
            ->withProperties([
                'ip' => request()->ip(),
            ])
            ->log('User logged out');

        return response()->json(['message' => 'Logged out successfully']);
    }

    // Get User Profile
    public function user(Request $request)
    {
        return response()->json($request->user()->load('roles', 'permissions'));
    }

    // Google Login Redirect
    public function redirectToGoogle()
    {
        // Stateless for API context often preferred if separation is strict, 
        // but here we might need stateful if using session. 
        // For pure API, stateless() is common.
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Google Callback
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // New User Registration via Google
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(uniqid()), // Random dummy password
                    'email_verified_at' => now(),
                ]);
                $user->assignRole('parent');
            }

            // Log the user in (Crucial for SPA/Sanctum cookie-based auth)
            Auth::login($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            // In a real SPA, you usually redirect back to the frontend with the token 
            // in the URL or set a cookie.
            // For this implementation, let's assume we return JSON and the frontend handles the popup/redirect flow separately,
            // OR we redirect to frontend with token param.

            $frontendUrl = env('FRONTEND_URL', 'http://192.168.8.103:3000');

            return redirect("{$frontendUrl}/auth/callback?token={$token}");

        } catch (\Exception $e) {
            return response()->json(['error' => 'Google Login Failed: ' . $e->getMessage()], 500);
        }
    }
    // Set or Update PIN
    public function setPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4|regex:/^[0-9]+$/',
        ]);

        $user = $request->user();
        $user->pin = Hash::make($request->pin);
        $user->save();

        return response()->json(['message' => 'PIN impostato con successo']);
    }

    // Verify PIN
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:4',
        ]);

        $user = $request->user();

        if (!$user->pin || !Hash::check($request->pin, $user->pin)) {
            return response()->json(['message' => 'PIN non valido'], 403);
        }

        return response()->json(['message' => 'PIN corretto']);
    }
    // Get All Users (Admin)
    public function index()
    {
        $users = User::with(['roles', 'children'])->orderBy('created_at', 'desc')->paginate(20);
        return response()->json($users);
    }

    // Update profile (name + email)
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return response()->json(['message' => 'Profilo aggiornato.', 'user' => $user->fresh()->load('roles')]);
    }

    // Update password
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'La password attuale non è corretta.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password aggiornata con successo.']);
    }

    // Update avatar (file upload)
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar if it exists and is stored locally
        if ($user->avatar && str_contains($user->avatar, '/storage/avatars/')) {
            $oldPath = str_replace('/storage/', '', parse_url($user->avatar, PHP_URL_PATH));
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $url = url('/storage/' . $path);

        $user->update(['avatar' => $url]);

        return response()->json([
            'message' => 'Avatar aggiornato con successo.',
            'avatar' => $url,
            'user' => $user->fresh()->load('roles'),
        ]);
    }

    /**
     * Update avatar seed (DiceBear selection)
     */
    public function updateAvatarSeed(Request $request)
    {
        $validSeeds = ['Felix', 'Aneka', 'Zoe', 'Marc', 'Leo', 'Molly', 'Simba', 'Lala', 'Coco', 'Bubba', 'Kiki', 'Ziggy'];

        $request->validate([
            'seed' => ['required', 'string', \Illuminate\Validation\Rule::in($validSeeds)],
        ]);

        $user = $request->user();
        $user->update(['avatar' => $request->seed]);

        return response()->json([
            'message' => 'Avatar aggiornato.',
            'user' => $user->fresh()->load('roles'),
        ]);
    }
}
