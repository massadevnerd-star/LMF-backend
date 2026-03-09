<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reimposta la tua password</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div
        style="max-w-md: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="color: #4f46e5; text-align: center;">Reimposta Password</h2>
        <p style="color: #333333; font-size: 16px; line-height: 1.5;">
            Ciao {{ $userName }},<br><br>
            Abbiamo ricevuto una richiesta per reimpostare la password del tuo account su Le Mie Fiabe.
            Puoi reimpostare la tua password cliccando sul pulsante qui sotto:
        </p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}"
                style="background-color: #ec4899; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                Reimposta Password
            </a>
        </div>
        <p style="color: #666666; font-size: 14px; line-height: 1.5;">
            Questo link scadrà tra 60 minuti. Se non hai richiesto tu il reset della password, puoi tranquillamente
            ignorare questa email.
        </p>
        <p style="color: #999999; font-size: 12px; margin-top: 30px; text-align: center;">
            Se il pulsante non funziona, copia e incolla questo link nel tuo browser:<br>
            <a href="{{ $resetUrl }}" style="color: #ec4899;">{{ $resetUrl }}</a>
        </p>
    </div>
</body>

</html>