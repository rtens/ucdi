<?php

use rtens\ucdi\Bootstrapper;
use rtens\ucdi\GoogleCalendar;
use watoki\curir\protocol\Url;

require_once __DIR__ . '/vendor/autoload.php';

session_start();

$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

$client = new Google_Client();
$client->setApplicationName('U Can Do It');
$client->setScopes([Google_Service_Calendar::CALENDAR, Google_Service_Oauth2::USERINFO_EMAIL]);
$client->setAuthConfigFile(__DIR__ . '/user/client_secret.json');
$client->setClassConfig(Google_Http_Request::class, "disable_gzip", true);

if (isset($_GET['logout'])) {
    unset($_SESSION['token']);
    echo "Logged-out <a href='?'>Log-in</a>";
    exit();
}

if (isset($_GET['calendar'])) {
    $_SESSION['calendar'] = $_GET['calendar'];
}

if (isset($_GET['resetCalendar'])) {
    unset($_SESSION['calendar']);
}

if (isset($_GET['code'])) {
    $client->authenticate($_GET['code']);
    $_SESSION['token'] = $client->getAccessToken();
    header('Location: ' . $baseUrl);
}

if (isset($_SESSION['token'])) {
    $client->setAccessToken($_SESSION['token']);
}

if (!$client->getAccessToken()) {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    exit();
}

$cal = new Google_Service_Calendar($client);
$info = new Google_Service_Oauth2($client);

$userDir = __DIR__ . '/user/' . $info->userinfo->get()->email;

(new Bootstrapper($userDir, $info->userinfo->get()->email, Url::fromString(dirname($baseUrl)), new GoogleCalendar($cal)))
    ->runWebApp();