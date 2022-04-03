<?php
if (!file_exists('conf/config.php')) {
	die('Please read the installing instructions in the README file.');
}

// load profiler first
require_once('lib/profiler.php');
$profiler = new Profiler();

require_once('conf/config.php');
require_once('vendor/autoload.php');
foreach (glob("lib/*.php") as $file) {
	require_once($file);
}

if (!file_exists($squarebracketFiles . "/index.php")) {
    die("squareBracket instance does not seem to exist outside of database. Make sure it is pointed to the right directory.");
}
if (!file_exists($poktubeFiles . "/index.php")) {
    die("PokTube instance does not seem to exist outside of database. Make sure it is pointed to the right directory.");
}

if (!empty($blockedUA) && isset($_SERVER['HTTP_USER_AGENT'])) {
	foreach ($blockedUA as $bl) {
		if (str_contains($_SERVER['HTTP_USER_AGENT'], $bl)) {
			http_response_code(403);
			echo '403';
			die();
		}
	}
}

// Redirect all pages to https if https is enabled.
if (!isCli() && $https && !isset($_SERVER['HTTPS'])) {
	header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
	die();
}

if (!isset($acmlm))
	$userfields = userfields();

// Unset legacy authentication cookies for security purposes. This will hopefully delete it from browsers and clients.
if (isset($_COOKIE['user']))	setcookie('user', 'DEPRECATED', 1, '/');
if (isset($_COOKIE['passenc'])) setcookie('passenc', 'DEPRECATED', 1, '/');