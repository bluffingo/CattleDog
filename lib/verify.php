#!/usr/bin/php
<?php

/* cattleDog Verify Script

This script confirms settings 'n shit before migration code is run. 
It is dependent on squareBracket code and is ran everytime a cattleDog
script is launched.

-grkb, June 25th, 2022.

*/

require('lib/common.php');
use Codedungeon\PHPCliColors\Color;

$_SESSION['isCattleDog'] = true;
$isDebug = false;
$isMaintenance = false;
$sbCommon = $squarebracketFiles . '/private/class/common.php';

echo Color::GREEN, 'cattleDog: Verification', Color::RESET, PHP_EOL;
echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;

if(!file_exists($sbCommon)) {
	echo Color::RED, 'squareBracket instance is not refactored Beta 3+', Color::RESET, PHP_EOL;
	$hasError = true;
} else {
	require($sbCommon);
}

if(isset($hasError)) {
	die("cattleDog cannot run until these issues are fixed!");
}

echo Color::GRAY, 'squareBracket Version: ' . $versionNumber . " (commit " . gitCommit($squarebracketFiles, $gitBranch) . ") ", Color::RESET, PHP_EOL;

echo Color::GRAY, 'Setting: squareBracket Database Name: ' . $squarebracketDB, Color::RESET, PHP_EOL;
echo Color::GRAY, 'Setting: squareBracket Files Directory: ' . $squarebracketFiles, Color::RESET, PHP_EOL;

echo Color::GRAY, '=================================================================', Color::RESET, PHP_EOL;

if($needOtherInstance) {
	echo Color::GRAY, 'Setting: Other Database Name: ' . $otherDB, Color::RESET, PHP_EOL;
	echo Color::GRAY, 'Setting: Other Files Directory: ' . $otherFiles, Color::RESET, PHP_EOL;
} else {
	echo Color::YELLOW, 'This script does not require a non-SB instance', Color::RESET, PHP_EOL;
}

echo Color::GRAY, '=================================================================', Color::RESET, PHP_EOL;

echo Color::GRAY, 'Setting: Migrate Comments: ' . ($migrateComments ? 'true' : 'false'), Color::RESET, PHP_EOL;
echo Color::GRAY, 'Setting: Migrate Users: ' . ($migrateUsers ? 'true' : 'false'), Color::RESET, PHP_EOL;
echo Color::GRAY, 'Setting: Migrate Videos: ' . ($migrateVideos ? 'true' : 'false'), Color::RESET, PHP_EOL;
echo PHP_EOL;