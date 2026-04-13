<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $aiSubject }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .content { padding: 20px 0; }
        .footer { font-size: 12px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="UnicoDoc Logo" height="40">
        </div>

        <div class="content">
            {{-- Attenzione: usiamo {!! !!} e non {{ }} perché l'AI genererà tag HTML (es. <strong>, <p>) e vogliamo renderizzarli, non stamparli come testo --}}
            {!! $aiBody !!}
        </div>

        <div class="footer">
            <p>Questa è una comunicazione automatica relativa alla tua pratica DSR gestita tramite UnicoDoc.</p>
            <p>Ai sensi del GDPR (Regolamento UE 2016/679), hai diritto alla cancellazione dei tuoi dati. <br>
               Per informazioni: privacy@tuaazienda.com</p>
        </div>

    </div>
</body>
</html>
