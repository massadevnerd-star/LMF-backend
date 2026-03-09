<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PinResetMail;
use Carbon\Carbon;

class PinController extends Controller
{
    /**
     * Change user's PIN (requires old PIN verification)
     */
    public function changePin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_pin' => 'required|string|size:4',
            'new_pin' => 'required|string|size:4',
            'new_pin_confirmation' => 'required|string|size:4|same:new_pin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Errore di validazione',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Verify old PIN
        if (!Hash::check($request->old_pin, $user->pin)) {
            return response()->json([
                'message' => 'Il vecchio PIN non è corretto'
            ], 401);
        }

        // Ensure new PIN is different from old PIN
        if ($request->old_pin === $request->new_pin) {
            return response()->json([
                'message' => 'Il nuovo PIN deve essere diverso dal vecchio PIN'
            ], 422);
        }

        // Update PIN
        $user->pin = Hash::make($request->new_pin);
        $user->save();

        // Log PIN change
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'ip' => request()->ip(),
                'action' => 'pin_changed',
            ])
            ->log('User changed PIN');

        return response()->json([
            'message' => 'PIN aggiornato con successo!'
        ]);
    }

    /**
     * Verify current PIN
     */
    public function verifyPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pin' => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'PIN non valido',
                'valid' => false
            ], 422);
        }

        $user = $request->user();
        $isValid = Hash::check($request->pin, $user->pin);

        if (!$isValid) {
            return response()->json([
                'valid' => false,
                'message' => 'PIN non corretto'
            ], 403);
        }

        return response()->json([
            'valid' => true,
            'message' => 'PIN corretto'
        ]);
    }

    /**
     * Request PIN reset via email
     */
    public function requestReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Email non valida o non trovata',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate 6-digit reset code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store reset code (using password_resets table)
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($code),
                'created_at' => Carbon::now()
            ]
        );

        // Send email
        try {
            Mail::to($request->email)->send(new PinResetMail($code));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Errore durante l\'invio dell\'email. Riprova più tardi.'
            ], 500);
        }

        return response()->json([
            'message' => 'Codice di reset inviato via email. Controlla la tua casella di posta.'
        ]);
    }

    /**
     * Verify reset code and set new PIN
     */
    public function verifyResetAndSetPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'reset_code' => 'required|string|size:6',
            'new_pin' => 'required|string|size:4',
            'new_pin_confirmation' => 'required|string|size:4|same:new_pin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Errore di validazione',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find reset record
        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$reset) {
            return response()->json([
                'message' => 'Codice di reset non trovato'
            ], 404);
        }

        // Check if code is expired (15 minutes)
        $createdAt = Carbon::parse($reset->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Il codice di reset è scaduto. Richiedi un nuovo codice.'
            ], 410);
        }

        // Verify reset code
        if (!Hash::check($request->reset_code, $reset->token)) {
            return response()->json([
                'message' => 'Codice di reset non valido'
            ], 401);
        }

        // Update user's PIN
        $user = \App\Models\User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Utente non trovato'
            ], 404);
        }

        $user->pin = Hash::make($request->new_pin);
        $user->save();

        // Delete reset code
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'PIN reimpostato con successo!'
        ]);
    }
}
