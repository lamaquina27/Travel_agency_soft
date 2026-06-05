<?php

$appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost/Travel_agency_soft', '/');

define('GMAIL_CLIENT_ID',     $_ENV['GMAIL_CLIENT_ID']     ?? '');
define('GMAIL_CLIENT_SECRET', $_ENV['GMAIL_CLIENT_SECRET'] ?? '');
define('GMAIL_REDIRECT_URI', $appUrl . '/gmail/oauth?action=callback');
define('GMAIL_SCOPES', [
    'openid',
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile',
    'https://www.googleapis.com/auth/gmail.readonly',
    'https://www.googleapis.com/auth/gmail.send',
    'https://www.googleapis.com/auth/gmail.modify',
]);
