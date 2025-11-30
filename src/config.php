<?php
return [
    'rp' => [
        'name' => 'WebAuthn Demo',
        'id' => $_SERVER['HTTP_HOST'],
    ],
    'timeout' => 60000,
    'attestation' => 'none',
    'pubKeyCredParams' => [
        ['type' => 'public-key', 'alg' => -7],  // ES256
        ['type' => 'public-key', 'alg' => -257] // RS256
    ],
];