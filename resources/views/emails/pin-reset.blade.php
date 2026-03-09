<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset PIN</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #fff9f0;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #FF6B6B;
            margin-bottom: 10px;
        }

        .code-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 48px;
            font-weight: bold;
            letter-spacing: 8px;
            text-align: center;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
        }

        .content {
            color: #333;
            line-height: 1.8;
            font-size: 16px;
        }

        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            color: #999;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">🧙‍♂️ Le Mie Fiabe</div>
            <h1 style="color: #667eea; margin: 0;">Reset del tuo PIN</h1>
        </div>

        <div class="content">
            <p>Ciao! 👋</p>
            <p>Hai richiesto di reimpostare il tuo PIN di sicurezza. Ecco il tuo codice di verifica:</p>

            <div class="code-box">
                {{ $code }}
            </div>

            <p><strong>Inserisci questo codice nell'applicazione per completare il reset del PIN.</strong></p>

            <div class="warning">
                <strong>⚠️ Importante:</strong>
                <ul style="margin: 10px 0;">
                    <li>Questo codice è valido per <strong>15 minuti</strong></li>
                    <li>Non condividere questo codice con nessuno</li>
                    <li>Se non hai richiesto questo reset, ignora questa email</li>
                </ul>
            </div>

            <p>Se hai bisogno di aiuto, contattaci a <a href="mailto:support@lemiestorie.it">support@lemiestorie.it</a>
            </p>
        </div>

        <div class="footer">
            <p>© 2026 Le Mie Fiabe - Storie Magiche per Bambini</p>
            <p style="font-size: 12px; color: #ccc;">Questa è un'email automatica, per favore non rispondere.</p>
        </div>
    </div>
</body>

</html>