#!/usr/bin/php
<?php

// Migrating The Missing Shit !
// Chaziz, June 25th 2024

require('lib/common.php');

global $host, $user, $pass;

$poktubeDB = new Database($host, $user, $pass, "squarebracket_migration_poktube"); // PokTube
$sbOldDB = new Database($host, $user, $pass, "squarebracket_migration_2021db"); // squareBracket DB dump from December 2021
$sbDB = new Database($host, $user, $pass, "squarebracket_migration"); // current database

// Videos on PokTube posted after "Get this man Reddit Gold", which was the newest PokTube video that was migrated to SB.
$poktubeVideos = $poktubeDB->fetchArray($poktubeDB->query("SELECT * FROM videodb WHERE UploadDate > '2021-04-21 19:13:03'"));

// PokTube "Bulletins", a precursor to squareBracket journals.
$poktubeBulletins = $poktubeDB->fetchArray($poktubeDB->query("SELECT * FROM bulletins"));

// PokTube comments. Most of them were migrated, but not all of them.
$poktubeComments = $poktubeDB->fetchArray($poktubeDB->query("SELECT * FROM comments"));

// PokTube users.
$poktubeUsers = $poktubeDB->fetchArray($poktubeDB->query("SELECT * FROM users"));

// Videos on squareBracket posted by icanttellyou between late-April 2021 and mid-November 2021, which were removed
// from the site in December 2021 during drama. The December 2021 database still had these videos, but were "hidden" from
// the site.
$ictyVideos = $sbOldDB->fetchArray($sbOldDB->query("SELECT * FROM videos WHERE author = 0"));

$poktubeBannedUsers = ['killer199', 'MarineAttia2002', 'iGizmo2328', 'blog'];

echo "Copying bulletins into journals.\n";
foreach ($poktubeBulletins as $bulletin) {
    $authorID = $sbDB->result("SELECT id from users WHERE name = ?", [$bulletin['user']]);

    $sbDB->query("INSERT INTO journals (title, post, author, date) VALUES (?,?,?,?)",
        [$bulletin["subject"], $bulletin["body"], $authorID, strtotime($bulletin["date"])]);
}

$usernames = [];

foreach ($poktubeUsers as $user)
{
    $usernames[] = $user['username'];
}

echo "Copying PokTube videos.\n";
// migrate missing poktube videos.
foreach ($poktubeVideos as $video)
{
    $videoIDs[] = $video['VideoID'];
    if (!$sbDB->result("SELECT video_id from videos WHERE video_id = ?", [$video['VideoID']]))
    {
        if (in_array($video['Uploader'], $poktubeBannedUsers)) {
            // Nothing. Real.
        } else {
            $authorID = $sbDB->result("SELECT id from users WHERE name = ?", [$video['Uploader']]);

            $sbDB->query("INSERT INTO videos (video_id, title, description, author, time, videofile, videolength) VALUES (?,?,?,?,?,?,?)",
                [$video['VideoID'], $video['VideoName'], $video['VideoDesc'], $authorID, strtotime($video['UploadDate']), "TODO_BY_SCRIPT", $video['VideoLength']]);
        }
    }
}

$sbVideos = $sbDB->fetchArray($sbDB->query("SELECT video_id FROM videos"));

$sbVideoIDs = [];

foreach ($sbVideos as $video)
{
    $sbVideoIDs[] = $video['video_id'];
}

echo "Copying comments.\n";
foreach ($poktubeComments as $comment)
{
    // migrate channel comments. this was implemented in cattledog's migrate-poktube-to-squarebracket.php but didn't work?
    if (in_array($comment['id'], $usernames)) {
        // poktube account was merged into squarebracket account recently
        if ($comment['id'] == "PokTube") {
            $comment['id'] = "squareBracket";
        }

        if (in_array($comment['id'], $poktubeBannedUsers)) {
            // Nothing. Real.
        } else {
            $userID = $sbDB->result("SELECT id from users WHERE name = ?", [$comment['id']]);
            $authorID = $sbDB->result("SELECT id from users WHERE name = ?", [$comment['user']]);

            $sbDB->query("INSERT INTO channel_comments (id, comment, author, date, deleted) VALUES (?,?,?,?,?)",
                [$userID, $comment['comment'], $authorID, strtotime($comment['date']), 0]);
        }
    }

    if (($comment["date"] > "2021-04-21") && in_array($comment['id'], $sbVideoIDs)) {
        // poktube account was merged into squarebracket account recently
        if ($comment['user'] == "PokTube") {
            $comment['user'] = "squareBracket";
        }

        if (in_array($comment['id'], $poktubeBannedUsers)) {
            // Nothing. Real.
        } else {
            $userID = $sbDB->result("SELECT id from users WHERE name = ?", [$comment['id']]);
            $authorID = $sbDB->result("SELECT id from users WHERE name = ?", [$comment['user']]);

            $sbDB->query("INSERT INTO comments (id, comment, author, date, deleted) VALUES (?,?,?,?,?)",
                [$comment['id'], $comment['comment'], $authorID, strtotime($comment['date']), 0]);
        }
    }
}

// icty's id is higher than it should be. Oops.
$icanttellyouID = 105;

echo "Copying Icanttellyou videos.\n";
foreach ($ictyVideos as $video)
{
    $sbDB->query("INSERT INTO videos (video_id, title, description, author, time, videofile, videolength, tags) VALUES (?,?,?,?,?,?,?,?)",
        [$video['video_id'], $video['title'], $video['description'], $icanttellyouID, $video['time'], "TODO_BY_SCRIPT", $video['videolength'], ($video['tags'] ?? "")]);
}