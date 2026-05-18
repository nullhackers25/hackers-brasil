<?php
session_start();

require_once 'config.php';

$clientId = GOOGLE_CLIENT_ID;
$redirectUri = GOOGLE_REDIRECT_URI;

$authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online',
    'prompt' => 'select_account'
]);

header('Location: ' . $authUrl);
exit;
