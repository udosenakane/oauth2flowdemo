<?php
 $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            
 $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

return [
    'identity_server' => 'https://id-sandbox.cashtoken.africa/oauth',
    'client_id' => 'wprQYMZBqqx-dgszFUfQG',
    'scope' => 'openid email profile',
    'response_type' => 'code',
    'redirect_uris' => [
        'http://localhost:3000/oauth-callback',
        'http://localhost:3000/callback'
    ],
    'state' => 12345,
    'nonce' => 67890,
    // 'response_mode' => 'form_post',
    'code_challenge' => $codeChallenge,
    'code_verifier' => $codeVerifier
];