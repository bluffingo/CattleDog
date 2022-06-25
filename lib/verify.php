#!/usr/bin/php
<?php

/* cattleDog Verify Script

This script confirms settings 'n shit before migration code is run. 
It is dependent on squareBracket code and is ran everytime a cattleDog
script is launched.

-grkb, June 25th, 2022.

*/

require('lib/common.php');

$_SESSION['isCattleDog'] = true;
$isDebug = false;
$isMaintenance = false;

require($squarebracketFiles . '/lib/common.php'); // required to get version number

use Codedungeon\PHPCliColors\Color;

echo Color::GREEN, 'cattleDog: Verification Script', Color::RESET, PHP_EOL;
echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;

if(!isset($versionNumber)) {
  echo Color::RED, "squareBracket instance does not report a version number, outdated version? (Implemented in March 2022 during Beta 2.1.0)", Color::RESET, PHP_EOL;
  $hasError = true;
}
if(!isset($gitBranch)) {
  echo Color::RED, "squareBracket instance does not report Git Branch, outdated version? (Implemented in June 2022 during Beta 2.1.0)", Color::RESET, PHP_EOL;
  $hasError = true;
}
if(isset($hasError)) {
	die("cattleDog cannot run until these issues are fixed!");
}

echo Color::GRAY, 'squareBracket Version: ' . $versionNumber . " (commit " . gitCommit($squarebracketFiles, $gitBranch) . ") ", Color::RESET, PHP_EOL;

echo Color::GRAY, 'Setting: squareBracket Database Name: ' . $squarebracketDB, Color::RESET, PHP_EOL;
echo Color::GRAY, 'Setting: squareBracket Files Directory: ' . $squarebracketFiles, Color::RESET, PHP_EOL;

echo Color::GRAY, '=================================================================', Color::RESET, PHP_EOL;

echo Color::GRAY, 'Setting: Other Database Name: ' . $otherDB, Color::RESET, PHP_EOL;
echo Color::GRAY, 'Setting: Other Files Directory: ' . $otherFiles, Color::RESET, PHP_EOL;

echo Color::GRAY, '=================================================================', Color::RESET, PHP_EOL;

echo Color::GRAY, 'Setting: Migrate Comments: ' . ($migrateComments ? 'true' : 'false'), Color::RESET, PHP_EOL;
echo Color::GRAY, 'Setting: Migrate Users: ' . ($migrateUsers ? 'true' : 'false'), Color::RESET, PHP_EOL;
echo Color::GRAY, 'Setting: Migrate Videos: ' . ($migrateVideos ? 'true' : 'false'), Color::RESET, PHP_EOL;
echo PHP_EOL;