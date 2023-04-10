#!/usr/bin/php
<?php

/* squareBracket to Qobo Conversion script

This script expects the schemes of both the SB and Qobo database to be identical, and that the Qobo DB depends on Bunny.

-grkb, April 9th, 2023.

*/

$needOtherInstance = true;
require('lib/common.php');
use Codedungeon\PHPCliColors\Color;
use Intervention\Image\ImageManager;

$manager = new ImageManager();

echo Color::GREEN, 'cattleDog: squareBracket to Qobo Conversion script', Color::RESET, PHP_EOL;
echo Color::YELLOW, 'Notice: nearly everything will be migrated, as the database schemes should be identical.', Color::RESET, PHP_EOL;

$users = fetchArray(otherQuery("SELECT * FROM users"));
$videos = fetchArray(otherQuery("SELECT * FROM videos"));
$views = fetchArray(otherQuery("SELECT * FROM views"));
$comments = fetchArray(otherQuery("SELECT * FROM comments"));
$channel_comments = fetchArray(otherQuery("SELECT * FROM channel_comments"));
$bans = fetchArray(otherQuery("SELECT * FROM bans"));

echo Color::GREEN, 'Migrating users.', Color::RESET, PHP_EOL;

foreach ($users as $user)
{
	if (!sbResult("SELECT name from users WHERE name = ?", [$user['name']]))
	{
		echo Color::GREEN, $user['name'] . " doesn't exist on Qobo database.", Color::RESET, PHP_EOL;
		
		echo Color::GRAY, "Migrating " . $user['name'] . "...", Color::RESET, PHP_EOL;

		if (empty($user['email'])) {
			echo Color::YELLOW, $user['name'] . " is lacking an email address.", Color::RESET, PHP_EOL;
			$email = $user['name'] . "@squarebracket.pw";
		} else {
			$email = $user['email'];
		}
		
		if (empty($user['about'])) {
			$about = "Migrated to Qobo via cattleDog.";
		} else {
			$about = $user['about'] . " - Migrated to Qobo via cattleDog.";
		}
		
		if (empty($user['title'])) {
			$title = $user['name'];
		} else {
			$title = $user['title'];
		}
		
		sbQuery("INSERT INTO users (name, email, password, token, joined, title, about, customcolor, lastview) VALUES (?,?,?,?,?,?,?,?,?)",
					[$user['name'],$email,$user['password'], $user['token'], $user['joined'], $title, $about, $user['customcolor'], $user['lastview']]);
		
	} else {
		echo Color::GRAY, $user['name'] . " already exists on Qobo database.", Color::RESET, PHP_EOL;
	}
	
	// i just realized you can call opensb functions within cattledog as a side effect of the verification code which is fucking cool. -grkb 4/9/2023
	if ((!$storage->fileExists("/dynamic/pfp/" . $user['name'] . ".png")) and (file_exists($otherFiles . "/dynamic/pfp/" . $user['name'] . ".png")))
	{
		echo Color::GREEN, "Uploading " . $user['name'] . "'s profile picture to Bunny.", Color::RESET, PHP_EOL;
		$storage->cdUpload("/dynamic/pfp/" . $user['name'] . ".png", $otherFiles . "/dynamic/pfp/" . $user['name'] . ".png");
	}
	
	$qbid = sbResult("SELECT id from users WHERE name = ?", [$user['name']]);
	sbQuery("UPDATE users SET joined = ? WHERE id = ?", [$user['joined'],$qbid]);
	$user_id_array[$user['id']] = [ 
		"sb_id" => $user['id'],
		"username" => $user['name'],
		"qobo_id" => $qbid
	];
}

echo Color::GREEN, 'Migrating bans.', Color::RESET, PHP_EOL;

foreach ($bans as $ban)
{
	sbQuery("INSERT INTO bans (userid, reason) VALUES (?,?)",
		[$user_id_array[$ban["userid"]]["qobo_id"], $ban["reason"]]);
}

echo Color::GREEN, 'Migrating comments.', Color::RESET, PHP_EOL;

foreach ($comments as $comment)
{
	sbQuery("INSERT INTO comments (id, reply_to, comment, author, date) VALUES (?,?,?,?,?)",
		[$comment["id"], 0, $comment["comment"], $user_id_array[$comment["author"]]["qobo_id"], $comment["date"]]);
}

foreach ($channel_comments as $comment)
{
	sbQuery("INSERT INTO channel_comments (id, reply_to, comment, author, date) VALUES (?,?,?,?,?)",
		[$user_id_array[$comment["id"]]["qobo_id"], 0, $comment["comment"], $user_id_array[$comment["author"]]["qobo_id"], $comment["date"]]);
}


echo Color::GREEN, 'Migrating videos.', Color::RESET, PHP_EOL;

foreach ($videos as $video)
{
	if (!sbResult("SELECT video_id from videos WHERE video_id = ?", [$video['video_id']]))
	{
		if (file_exists($otherFiles . "/dynamic/videos/" . $video["video_id"] . ".converted.mp4")) {
			echo Color::GREEN, $video['title'] . " doesn't exist on Qobo database.", Color::RESET, PHP_EOL;
			$authorData = $user_id_array[$video['author']];
			sbQuery("INSERT INTO videos (video_id, title, description, author, time, tags, videofile, flags) VALUES (?,?,?,?,?,?,?,?)",
				[$video["video_id"], $video["title"], $video["description"], $authorData["qobo_id"], $video["time"], $video["tags"], $video["videofile"], 0]);
			$storage->processVideo($video["video_id"], $otherFiles . "/dynamic/videos/" . $video["video_id"] . ".converted.mp4");
		} else {
			echo Color::RED, $video['title'] . "is missing its video file!", Color::RESET, PHP_EOL;
		}
	} else {
		echo Color::GRAY, $video['title'] . "exists on Qobo database.", Color::RESET, PHP_EOL;
	}
}