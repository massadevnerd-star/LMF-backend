<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Invia l'email con il link di reset della password
     */
    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // Ritorniamo sempre success per non rivelare se un'email esiste o no
        if (!$user) {
            return response()->json([
                'message' => 'Se l\'email esiste nel nostro sistema, riceverai un link per reimpostare la password.'
            ]);
        }

        // Genera token
        $token = Str::random(60);

        // Salva token in db
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        // Front-end URL
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $resetUrl = $frontendUrl . '/auth/reset-password?token=' . $token . '&email=' . urlencode($user->email);

        // Invia email
        Mail::to($user->email)->send(new PasswordResetMail($resetUrl, $user->name));

        return response()->json([
            'message' => 'Se l\'email esiste nel nostro sistema, riceverai un link per reimpostare la password.'
        ]);
    }

    /**
     * Reimposta la password usando il token fornito (dal link email)
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $resetStatus = Password::INVALID_TOKEN;

        // Cerca token
        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if ($record && Hash::check($request->token, $record->token)) {
            // Controlla scadenza (60 minuti)
            if (Carbon::parse($record->created_at)->addMinutes(60)->isFuture()) {
                $user = User::where('email', $request->email)->first();
                if ($user) {
                    $user->password = Hash::make($request->password);
                    $user->setRememberToken(Str::random(60));
                    $user->save();

                    // Cancella token
                    DB::table('password_reset_tokens')->where('email', $request->email)->delete();

                    $resetStatus = Password::PASSWORD_RESET;
                } else {
                    $resetStatus = Password::INVALID_USER;
                }
            }
        }

        if ($resetStatus == Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reimpostata con successo! Ora puoi accedere.']);
        }

        return response()->json(['message' => 'Il link di reset non è valido o è scaduto.'], 400);
    }
}
