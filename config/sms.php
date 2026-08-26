<?php

return [

    // Pilote d'envoi des SMS : log | whatsapp | twilio | http
    // « log » n'envoie rien et sert de repli quand aucun fournisseur n'est en place,
    // ce qui est le cas de ce projet.
    'driver' => env('SMS_DRIVER', 'log'),

    // Affiche le code OTP directement à l'écran (session flash) au lieu de compter
    // uniquement sur l'envoi réel — utile en local si le fournisseur SMS est en panne.
    'show_code_flash' => env('SMS_SHOW_CODE_FLASH', false),

    'otp_message' => 'Votre code de connexion SMH : %s',

    'whatsapp' => [
        'instance' => env('ULTRAMSG_INSTANCE'),
        'token' => env('ULTRAMSG_TOKEN'),
    ],

    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'http' => [
        'url' => env('SMS_HTTP_URL'),
        'method' => env('SMS_HTTP_METHOD', 'post'),
        'timeout' => env('SMS_HTTP_TIMEOUT', 15),
        'headers' => [],
        'body_template' => [],
    ],

];
