#!/usr/bin/php
<?php

/* PokTube to squareBracket Conversion script

Schizopost time:

This script expects that the $otherDB setting is set to a database with the layout of PokTube. It should not
be confused with pokTwo, which used a database more simillar to squareBracket. PokTube was based on FrameBit,
one of the the many attempts at trying to replicate 2005 YouTube. FrameBit was briefly only worked on for a
while in January 2021 by an individual, and was full of SQL injection vulns (which was why we ditched the
PokTube codebase in April 2021 in favor writing a new codebase, which became the basis of squareBracket.)

-grkb, June 19th, 2022.

*/

$needOtherInstance = true;
require('lib/common.php');
use Codedungeon\PHPCliColors\Color;
use Intervention\Image\ImageManager;

$manager = new ImageManager();

echo Color::GREEN, 'cattleDog: PokTube to squareBracket Conversion script', Color::RESET, PHP_EOL;
echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;


$users = fetchArray(otherQuery("SELECT * FROM users"));
$videos = fetchArray(otherQuery("SELECT * FROM videodb WHERE isApproved = 1;")); // declined videos don't go on squarebracket
$comments = fetchArray(otherQuery("SELECT * FROM comments"));

/*
explaination time:

killer199 was a dummy account not owned by chainsword
MarineAttia2002 was banned from PokTube due to erratic behaviour
iGizmo2328 was banned from PokTube for sneaking furry vore porn in the middle of a GoAnimate video.
blog was an internal account for something that was never implemented.
*/

// USERS

$dontMigrateUsers = ['killer199', 'MarineAttia2002', 'iGizmo2328', 'blog'];

if ($migrateUsers) {
	echo Color::GREEN, 'USER CONVERSION', Color::RESET, PHP_EOL;
	echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;

	foreach ($users as $user)
	{
		$userNames[] = $user['username'];
		if (!sbResult("SELECT name from users WHERE name = ?", [$user['username']]))
		{
			echo Color::GREEN, $user['username'] . " doesn't exist on squareBracket database... Creating.", Color::RESET, PHP_EOL;
			if (in_array($user['username'], $dontMigrateUsers)) {
				echo Color::RED, $user['username'] . " is in blacklist, skipping.", Color::RESET, PHP_EOL;
				$userNames = array_remove_by_value($userNames, $user['username']);
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
}

if ($migrateVideos) {
	echo Color::GREEN, 'VIDEO CONVERSION', Color::RESET, PHP_EOL;
	echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;

	// VIDEOS
	foreach ($videos as $video)
	{
		$videoIDs[] = $video['VideoID'];
		if (!sbResult("SELECT video_id from videos WHERE video_id = ?", [$video['VideoID']]))
		{
			echo Color::GREEN, $video['VideoName'] . " by " . $video['Uploader'] . " doesn't exist on squareBracket database... migrating.", Color::RESET, PHP_EOL;
			if ($video['VideoID'] != "V05kmlJpDzC") {
				if (otherResult("SELECT HQVideoFile from videodb WHERE VideoID = ?", [$video['VideoID']])) {
					echo Color::GRAY, $video['VideoName'] . " by " . $video['Uploader'] . " is in HD.", Color::RESET, PHP_EOL;
					$properFile = $video['HQVideoFile'];
				} else {
					echo Color::GRAY, $video['VideoName'] . " by " . $video['Uploader'] . " is not in HD.", Color::RESET, PHP_EOL;
					$properFile = $video['VideoFile'];
				}
			} else {
				echo Color::GRAY, $video['VideoName'] . " by " . $video['Uploader'] . "'s HD file is invalid", Color::RESET, PHP_EOL;
				$properFile = $video['VideoFile'];
			}
			$properFile = $otherFiles . "/" . $properFile;
			$sbVideoLocation = "/videos/" . $video['VideoID'] . ".converted.mp4";
			$copyFileTo = $squarebracketFiles . $sbVideoLocation;
			$uploaderID = sbResult("SELECT id from users WHERE name = ?", [$video['Uploader']]); // this should work if users were migrated before
			$time = strtotime($video['UploadDate']);
			if(!copy($properFile,$copyFileTo)){
				echo "failed to copy " . $properFile;
			} else {
			sbQuery("INSERT INTO videos (video_id, title, description, author, time, videofile, videolength) VALUES (?,?,?,?,?,?,?)",
				[$video['VideoID'],$video['VideoName'],$video['VideoDesc'],$uploaderID,$time,$sbVideoLocation,$video['VideoLength']]);
			}
		} else {
			echo Color::GRAY, $video['VideoName'] . " by " . $video['Uploader'] . " already exists... skipping.", Color::RESET, PHP_EOL;
		}
		$pokThumbFile = $otherFiles . "/content/thumbs/" . $video['VideoID'] . ".png";
		$sbThumbFile = $squarebracketFiles . "/assets/thumb/" . $video['VideoID'] . ".png";
		if(!copy($pokThumbFile,$sbThumbFile)){
			echo "failed to copy " . $properFile; // FIXME: call ffmpeg to generate thumbnail for any vids that don't have that.
		} else {
			$img = $manager->make($sbThumbFile);
			$img->resize(640, 360);
			$img->save($sbThumbFile, 0, 'png');
		}
	}
}

if ($migrateComments) {
	echo Color::GREEN, 'COMMENT CONVERSION', Color::RESET, PHP_EOL;
	echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;

	// COMMENTS (oh dear fucking god)
	foreach ($comments as $comment)
	{
		$commentPosterID = sbResult("SELECT id from users WHERE name = ?", [$comment['user']]); // this should work if users were migrated before
		$date = strtotime($comment['date']);
		$comment['comment'] = stripBBCode($comment['comment']); // poktube had bbcode functionality, so strip that.
		if (in_array($comment['id'], $userNames) AND in_array($comment['id'], $videoIDs)) {
			echo Color::GREEN, 'The comment ' . $comment['commentid'] . ' by user ' . $comment['user'] . ' is from both a profile and a video. Only migrating it to the video comment.', Color::RESET, PHP_EOL;
			sbQuery("INSERT INTO comments (id, comment, author, date) VALUES (?,?,?,?)",
				[$comment['id'],$comment['comment'],$commentPosterID,$date]);
		} elseif (in_array($comment['id'], $userNames)) {
			echo Color::GREEN, 'The comment ' . $comment['commentid'] . ' by user ' . $comment['user'] . ' is from a profile.', Color::RESET, PHP_EOL;
			sbQuery("INSERT INTO channel_comments (id, comment, author, date) VALUES (?,?,?,?)",
				[$comment['id'],$comment['comment'],$commentPosterID,$date]);
		} elseif (in_array($comment['id'], $videoIDs)) {
			echo Color::GREEN, 'The comment ' . $comment['commentid'] . ' by user ' . $comment['user'] . ' is from a video.', Color::RESET, PHP_EOL;
			sbQuery("INSERT INTO comments (id, comment, author, date) VALUES (?,?,?,?)",
				[$comment['id'],$comment['comment'],$commentPosterID,$date]);
		} else {
			echo Color::GRAY, 'The comment ' . $comment['commentid'] . ' by user ' . $comment['user'] . ' is from an invaild place or a bulletin, skipping.', Color::RESET, PHP_EOL;
		}
	}
}