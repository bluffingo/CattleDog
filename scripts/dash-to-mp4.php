#!/usr/bin/php
<?php

/* squareBracket dash-to-mp4 script */
$needOtherInstance = false;
require('lib/common.php');
use Codedungeon\PHPCliColors\Color;
use Streaming\FFMpeg;
use FFMpeg\Coordinate;
use FFMpeg\Media;
use FFMpeg\Filters;

// use the same settings from the sb instance for mpeg/probe paths
$config = [
    'timeout' => 3600, // The timeout for the underlying process
    'ffmpeg.threads' => 12,   // The number of threads that FFmpeg should use
    'ffmpeg.binaries' => ($ffmpegPath ? $ffmpegPath : 'ffmpeg'),
    'ffprobe.binaries' => ($ffprobePath ? $ffprobePath : 'ffprobe'),
];

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
			break;
		case 2:
			$ffmpeg = FFMpeg::create();
			$videoFile = $ffmpeg->open($file);
			$format = new Streaming\Format\x264();
			$format->on('progress', function ($video, $format, $percentage){
				echo sprintf("\rTranscoding...(%s%%) [%s%s]", $percentage, str_repeat('#', $percentage), str_repeat('-', (100 - $percentage)));
			});

			$videoFile->stream2file()
				->setFormat($format)
				->save($squarebracketFiles . "/dynamic/videos/" . $video["video_id"] . ".converted.mp4");
			break;
	}			
}