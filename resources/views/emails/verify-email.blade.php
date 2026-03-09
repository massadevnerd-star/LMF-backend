<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Verifica la tua email</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div
        style="max-w-md: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="color: #4f46e5; text-align: center;">Benvenuto nel Castello Magico, {{ $userName }}! ✨</h2>
        <p style="color: #333333; font-size: 16px; line-height: 1.5;">
            Siamo felici di averti con noi. Prima di iniziare a creare le tue storie, abbiamo bisogno di verificare il
            tuo indirizzo email.
        </p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $verificationUrl }}"
                style="background-color: #6366f1; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                Verifica Indirizzo Email
            </a>
        </div>
        <p style="color: #666666; font-size: 14px; line-height: 1.5;">
            Se non hai creato tu questo account, puoi tranquillamente ignorare questa email.
        </p>
        <p style="color: #999999; font-size: 12px; margin-top: 30px; text-align: center;">
            Se il pulsante non funziona, copia e incolla questo link nel tuo browser:<br>
            <a href="{{ $verificationUrl }}" style="color: #6366f1;">{{ $verificationUrl }}</a>
        </p>
    </div>
</body>

</html>