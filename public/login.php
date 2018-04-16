<?php require_once __DIR__ . '/../bootstrap.php';

session_start();

use Squid\Patreon\Exceptions\OAuthReturnedError;
use Squid\Patreon\OAuth;
use Squid\Patreon\Patreon;

if (
  ! getenv('PATREON_CLIENT_ID') ||
  ! getenv('PATREON_CLIENT_SECRET') ||
  ! getenv('PATREON_REDIRECT_URI')
) {
    echo "You must configure the Patreon OAuth client values to be able to accept logins. ";
    echo "Please refer to the 'Accepting Logins' portion of the README.";
    exit;
}

// Create a new OAuth client using the values from .env
$oauth = new OAuth(
    getenv('PATREON_CLIENT_ID'),
    getenv('PATREON_CLIENT_SECRET'),
    getenv('PATREON_REDIRECT_URI')
);

// If the request does not have a `code` parameter then it means that they have
// not been sent here by Patreon, so we need to redirect them to the Patreon
// login page.
if (! isset($_GET['code'])) {
    Header("Location: {$oauth->getAuthorizationUrl()}");
    exit;
}

// If the request has got this far it means that there is a `code` parameter
// which we can use to send to Patreon and get an access token (and refresh token)
// in return. This access token can then be used to request the users information
// from Patreon using the Patreon client.
try {
    $tokens = $oauth->getAccessToken($_GET['code']);
} catch(OAuthReturnedError $e) {
    echo "An error occurred completing your login: {$e->getMessage()}";
    exit;
}

// Save their access token to their session so that when they visit subsequent
// pages on the website we can send requests to Patreon on their behalf.
$_SESSION['accessToken'] = $tokens['access_token'];

Header('Location: patrons.php');
exit;
