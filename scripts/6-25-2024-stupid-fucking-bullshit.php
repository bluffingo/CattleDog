<?php

// Migrating The Missing Shit !
// Chaziz, June 25th 2024

use Streaming\FFMpeg;
use FFMpeg\Coordinate;
use FFMpeg\Media;
use FFMpeg\Filters;

require('lib/common.php');

global $host, $user, $pass;

// use the same settings from the sb instance for mpeg/probe paths
$config = [
    'timeout' => 3600, // The timeout for the underlying process
    'ffmpeg.threads' => 12,   // The number of threads that FFmpeg should use
    'ffmpeg.binaries' => "ffmpeg",
    'ffprobe.binaries' => "ffprobe",
];

$sbOldDB = new Database($host, $user, $pass, "squarebracket_migration_2021db"); // squareBracket DB dump from December 2021

$ictyVideos = $sbOldDB->fetchArray($sbOldDB->query("SELECT * FROM videos WHERE author = 0"));

$sbOldLocation = realpath("H:/cheeserox/videos");
$sbNewLocation = realpath("C:/OpenSB/dynamic/videos_temporary");

foreach ($ictyVideos as $video)
{
    if (!file_exists($sbNewLocation . "/" . $video["video_id"] . ".converted.mp4")) {
        echo $video["video_id"] . PHP_EOL;

        $ffmpeg = FFMpeg::create();
        $videoFile = $ffmpeg->open($sbOldLocation . "/" . $video["video_id"] . ".mpd");
        $format = new Streaming\Format\x264();
        //$format->on('progress', function ($video, $format, $percentage){
        //    echo sprintf("\rTranscoding...(%s%%) [%s%s]", $percentage, str_repeat('#', $percentage), str_repeat('-', (100 - $percentage)));
        //});

        $videoFile->stream2file()
            ->setFormat($format)
            ->save($sbNewLocation . "/" . $video["video_id"] . ".converted.mp4");
    }
}