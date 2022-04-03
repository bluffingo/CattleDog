#!/usr/bin/php
<?php

require('lib/common.php');
use Codedungeon\PHPCliColors\Color;

echo Color::GREEN, 'Chaziz Migration Tool: PokTube to squareBracket Conversion script', Color::RESET, PHP_EOL;
echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;

$users = fetchArray(pokQuery("SELECT * FROM users"));

/*
explaination time:

killer199 was a dummy account not owned by chainsword
MarineAttia2002 was banned from PokTube due to erratic behaviour
iGizmo2328 was banned from PokTube for sneaking furry vore porn in the middle of a GoAnimate video.
blog was an internal account for something that was never implemented.
*/

$dontMigrateUsers = ['killer199', 'MarineAttia2002', 'iGizmo2328', 'blog'];

echo Color::GREEN, 'USER CONVERSION', Color::RESET, PHP_EOL;
echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;

foreach ($users as $user)
{
	if (!sbResult("SELECT name from users WHERE name = ?", [$user['username']]))
	{
		echo Color::GREEN, $user['username'] . " doesn't exist on squareBracket database... Creating.", Color::RESET, PHP_EOL;
		if (in_array($user['username'], $dontMigrateUsers)) {
			echo Color::RED, $user['username'] . " is in blacklist, skipping.", Color::RESET, PHP_EOL;
		} else {
			$time = strtotime($user['registeredon']);
			$token = bin2hex(random_bytes(20));
			sbQuery("INSERT INTO users (name, password, token, joined, title, about) VALUES (?,?,?,?,?,?)",
				[$user['username'],$user['password'], $token, $time, $user['prof_name'], $user['aboutme']]);
		}
	} else {
		echo Color::GRAY, $user['username'] . " was ethier converted (or already registered pre-migration), skipping.", Color::RESET, PHP_EOL;
	}
}