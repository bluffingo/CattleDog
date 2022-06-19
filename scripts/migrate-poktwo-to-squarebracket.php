#!/usr/bin/php
<?php

/* pokTwo to squareBracket Conversion script

Schizopost time:

This script expects that the $otherDB setting is set to a database with the layout of pokTwo. It should not
be confused with pokTube, which predates pokTwo by more than a year. pokTwo's database layout is not 
compatible with squareBracket's database layout due to internal differences regarding tags and account data.

The reason why we aren't continuing development on pokTwo is due to the fact that it was only made as a
temporary replacement to squareBracket. From March to June 2022, I did not have control of sB, which had
caused me to start development on pokTwo. My attempt at a departure from OYC had made pokTwo's future
uncertain since porting Finalium to pokTwo would make it a mismatch of two slightly different backends.

In conclusion, pokTwo's dead, and let's just move the files over to squareBracket!

-grkb, June 19th, 2022.

*/

require('lib/common.php');
use Codedungeon\PHPCliColors\Color;
use Intervention\Image\ImageManager;

$manager = new ImageManager(['driver' => 'imagick']);

echo Color::GREEN, 'cattleDog: PokTwo to squareBracket Conversion script', Color::RESET, PHP_EOL;
echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;


$users = fetchArray(otherQuery("SELECT * FROM users"));
$videos = fetchArray(otherQuery("SELECT * FROM videos"));
$views = fetchArray(otherQuery("SELECT * FROM views"));
$comments = fetchArray(otherQuery("SELECT * FROM comments")); // NOTE: channel comments were not implemented on poktwo.

if ($migrateUsers) {
	echo Color::GREEN, 'USER CONVERSION', Color::RESET, PHP_EOL;
	echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;
	
	foreach ($users as $user)
	{
		if (!sbResult("SELECT name from users WHERE name = ?", [$user['name']]))
		{
			echo Color::GREEN, $user['name'] . " doesn't exist on squareBracket database... Creating.", Color::RESET, PHP_EOL;
			if (empty($user['title'])) {
				$displayName = $user['name'];
			} else {
				$displayName = $user['title'];
			}
			sbQuery("INSERT INTO users (name, email, password, token, joined, title, about, lastview) VALUES (?,?,?,?,?,?,?,?)",
					[$user['name'],$user['email'],$user['password'], $user['token'], $user['joined'], $displayName, $user['about'], $user['lastview']]);
		} else {
			echo Color::GRAY, $user['name'] . " was ethier converted (or already registered pre-migration), skipping.", Color::RESET, PHP_EOL;
			if (!sbResult("SELECT email from users WHERE name = ?", [$user['name']]))
			{
				echo Color::YELLOW, $user['name'] . " doesn't have an email on squareBracket but might do on pokTwo. attempting to port...", Color::RESET, PHP_EOL;
				sbQuery("UPDATE users SET email = ? WHERE name = ?", [$user['email'],$user['name']]);
			} else {
				echo Color::GRAY, $user['name'] . " already has an email on squareBracket.", Color::RESET, PHP_EOL;
			}
		}
	}
}

// FIXME: TAG SYSTEM

if ($migrateVideos) {
	echo Color::GREEN, 'VIDEO CONVERSION', Color::RESET, PHP_EOL;
	echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;
	
	foreach ($videos as $video)
	{
		$authorName = otherResult("SELECT name from users WHERE id = ?", [$video['author']]);
		$newAuthorID = sbResult("SELECT id from users WHERE name = ?", [$authorName]);
		if (!sbResult("SELECT video_id from videos WHERE video_id = ?", [$video['video_id']]))
		{
			echo Color::GREEN, $video['title'] . " by " . $authorName . " (P2 UID: " . $video['author'] . ", SB UID: " . $newAuthorID . ") doesn't exist on squareBracket database... migrating.", Color::RESET, PHP_EOL;
			// $video['videofile'] should be 'media/{random id}.mp4' on poktwo production.
			$p2VideoLocation = $otherFiles . "/" . $video['videofile'];
			$sbVideoLocation = "/videos/" . $video['video_id'] . ".converted.mp4";
			$copyFileTo = $squarebracketFiles . $sbVideoLocation;
			$tags = fetchArray(otherQuery("SELECT * FROM videos"));
			if(!copy($p2VideoLocation,$copyFileTo)){
				echo "failed to copy " . $p2VideoLocation;
			} else {
			// video lengths are fucked due to "Mister Icktee" and well the retarded screechy parrot is making me giving up on this.
			// just make a fucking sb script on that repo that recalculates all videos' length bullshit.
			sbQuery("INSERT INTO videos (video_id, title, description, author, time, most_recent_view, videofile) VALUES (?,?,?,?,?,?,?)", 
				[$video['video_id'],$video['title'],$video['description'],$newAuthorID,$video['time'],$video['most_recent_view'],$sbVideoLocation]);
			}
		} else {
			echo Color::GRAY, $video['title'] . " by " . $authorName . " already exists... skipping.", Color::RESET, PHP_EOL;
		}
		$p2ThumbFile = $otherFiles . "/thumbs/" . $video['VideoID'] . ".2.jpg"; // poktwo generated three (or two if it fucked up) thumbnails for every video due to yt 2005.
		$sbThumbFile = $squarebracketFiles . "/assets/thumb/" . $video['VideoID'] . ".png";
		$img = $manager->make($p2ThumbFile);
		$img->resize(640, 360);
		$img->save($sbThumbFile, 0, 'png');
	}
	
	foreach ($views as $view)
	{
		if (sbFetch("SELECT COUNT(video_id) FROM views WHERE video_id=? AND user=?", [$view['video_id'], $view['user']])['COUNT(video_id)'] < 1) {
			sbQuery("INSERT INTO views (video_id, user) VALUES (?,?)", 
				[$view['video_id'],$view['user']]);
		}
	}
}

if ($migrateComments) {
	echo Color::GREEN, 'COMMENT CONVERSION', Color::RESET, PHP_EOL;
	echo Color::GREEN, '=================================================================', Color::RESET, PHP_EOL;
	
	foreach ($comments as $comment)
	{
		$authorName = otherResult("SELECT name from users WHERE id = ?", [$comment['author']]);
		$newAuthorID = sbResult("SELECT id from users WHERE name = ?", [$authorName]);
		if (sbFetch("SELECT COUNT(id) FROM comments WHERE id=? AND author=? AND comment=?", [$comment['id'], $newAuthorID, $comment['comment']])['COUNT(id)'] < 1) {
			echo Color::GREEN, 'Porting comment from video ' . $comment['id'] . ' by user ' . $authorName . '.', Color::RESET, PHP_EOL;
			sbQuery("INSERT INTO comments (id, comment, author, date) VALUES (?,?,?,?)", 
				[$comment['id'],$comment['comment'],$newAuthorID,$comment['date']]);
		} else {
			echo Color::GRAY, 'Comment from video ' . $comment['id'] . ' by user ' . $authorName . ' already exists or it is spam.', Color::RESET, PHP_EOL;
		}
	}
}