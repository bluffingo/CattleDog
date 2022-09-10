#!/usr/bin/php
<?php

/* squareBracket dash-to-mp4 script */
$needOtherInstance = false;
require('lib/common.php');
use Codedungeon\PHPCliColors\Color;

if (!file_exists($squarebracketFiles . "/dynamic/videos2/")) {
	mkdir($squarebracketFiles . "/dynamic/videos2/", 0777, true);
}

$videos = fetchArray(sbQuery("SELECT * from videos WHERE post_type = 0 OR post_type = 1"));
foreach ($videos as $video) {
	if(file_exists($squarebracketFiles . "/dynamic/videos/" . $video["video_id"] . ".converted.mp4")) {
		echo Color::GRAY, $video['title'] . " is in MP4.", Color::RESET, PHP_EOL;
		$format = 1;
		$file = $squarebracketFiles . "/dynamic/videos/" . $video["video_id"] . ".converted.mp4";
	} elseif(file_exists($squarebracketFiles . "/dynamic/videos/" . $video["video_id"] . ".mpd")) {
		echo Color::GRAY, $video['title'] . " is in MPEG-DASH.", Color::RESET, PHP_EOL;
		$format = 2;
		$file = $squarebracketFiles . "/dynamic/videos/" . $video["video_id"] . ".mpd";
	} else {
		echo Color::RED, $video['title'] . " doesn't exist. what the fuck.", Color::RESET, PHP_EOL;
		$format = 0;
		$file = null;
	}
	
	switch ($format) {
		case 0:
			break;
		case 1:
			copy($file, $squarebracketFiles . "/dynamic/videos2/" . $video["video_id"] . ".converted.mp4");
			break;
		case 2:
			echo Color::RED, "DASH is not supported!", Color::RESET, PHP_EOL;
			break;
	}			
}