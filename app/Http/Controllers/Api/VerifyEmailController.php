<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationMail;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Verifica l'indirizzo email tramite link
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Verifica hash
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Link non valido o scaduto.'], 403);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            // Log verifica
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'action' => 'email_verified',
                ])
                ->log('User verified email');
        }

        // Redirect al frontend
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        return redirect()->away($frontendUrl . '/auth/verify-email?verified=1');
    }

    /**
     * Reinvia la notifica di verifica email
     */
    public function resend(Request $request)
    {
        // Se c'è un utente loggato, usiamolo
        if ($request->user()) {
            $user = $request->user();
        } else {
            // Altrimenti se viene passata in chiaro
            $request->validate(['email' => 'required|email']);
            $user = User::where('email', $request->email)->first();
        }

        if (!$user) {
            return response()->json(['message' => 'Utente non trovato.'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email già verificata.']);
        }

        $this->sendVerificationEmail($user);

        return response()->json(['message' => 'Il link di verifica è stato reinviato.']);
    }

    /**
     * Helper logica invio
     */
    public static function sendVerificationEmail(User $user)
    {
        $backendUrl = env('APP_URL', 'http://localhost:8000');

        // Costruisce la URL personalizzata evitando di usare le default routes di Laravel
        // Che potrebbero fallire se il nome route non è settato correttamente.
        $hash = sha1($user->getEmailForVerification());

        // Signed route simulata 
        // In un mondo reale si userebbe URL::temporarySignedRoute() se configurato per API web
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(120),
            ['id' => $user->getKey(), 'hash' => $hash]
        );

        Mail::to($user->email)->send(new VerificationMail($verificationUrl, $user->name));
    }
}
