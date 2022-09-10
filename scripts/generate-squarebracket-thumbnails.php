#!/usr/bin/php
<?php

/* squareBracket thumbnail regeneration script */
$needOtherInstance = false;
require('lib/common.php');
use Codedungeon\PHPCliColors\Color;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Coordinate;

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
			$ffmpeg = FFMpeg::create();
			$videoFile = $ffmpeg->open($file);
			$ffprobe = FFProbe::create();
			$duration = $ffprobe
				->streams($file) 	// extracts file informations
				->videos()          // filters video streams
				->first()           // returns the first video stream
				->get('nb_frames'); // returns the duration property
			$fracFramerate = $ffprobe
				->streams($file) 	      // extracts file informations
				->videos()                // filters video streams
				->first()                 // returns the first video stream
				->get('avg_frame_rate');  // returns the duration property
			$seccount = round($duration / 4);
			$seccount2 = $seccount * 1.5;
			$framerate = explode("/", $fracFramerate)[0] / explode("/", $fracFramerate)[1];
			
			$frame = $videoFile->frame(Coordinate\TimeCode::fromSeconds($seccount2 / $framerate));
			$frame->filters()->custom('scale=512x288');
			$frame->save($squarebracketFiles . '/dynamic/thumbnails/' . $video["video_id"] . '.png');
			break;
		case 2:
			break;
	}			
}