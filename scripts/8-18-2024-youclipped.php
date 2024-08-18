#!/usr/bin/php
<?php

// YouClipped-to-squareBracket
// Chaziz, August 18th 2024

require('lib/common.php');

global $host, $user, $pass;

$ycDB = new Database($host, $user, $pass, "youclipped"); // youclipped database
$sbDB = new Database($host, $user, $pass, "squarebracket_migration"); // squarebracket database

$ycUsers = $ycDB->fetchArray($ycDB->query("SELECT * FROM users"));
$sbUsers = $sbDB->fetchArray($sbDB->query("SELECT * FROM users"));

$ycNames = array_column($ycUsers, 'username');
$sbNames = array_column($sbUsers, 'name');

$matchingUsers = array_intersect($ycNames, $sbNames);
$ycUsernamesToSbIds = [];

// accounts on sb and youclipped that have matching usernames but arent from the same person. i think
$differentAccounts = ['i', 'o', 'xxx', '666', 'a'];

if (!empty($matchingUsers)) {
    echo "Matching users found: " . implode(', ', $matchingUsers);
} else {
    echo "No matching users found.";
}

foreach ($ycUsers as $user)
{
    // squarebracket does not support spaces in usernames due to profile urls
    $user["proper_username"] = str_replace(' ', '_', $user["username"]);
    if (in_array($user['proper_username'], $differentAccounts)) {
        $user['proper_username'] .= '-YC';
        $alreadyOnSB = false;
    } elseif (in_array($user['proper_username'], $matchingUsers)) {
        $alreadyOnSB = true;
    } else {
        $alreadyOnSB = false;
    }
    $timestamp = strtotime($user["date"]);
    $token = bin2hex(random_bytes(32));
    $fucked_username = strtolower($user["username"]);

    if ($alreadyOnSB) {
        $ycUsernamesToSbIds[$fucked_username] = $sbDB->result("SELECT id from users WHERE name = ?", [$user["proper_username"]]);
    } else {
        $sbDB->query("INSERT INTO users (name, email, password, token, joined, lastview, title, about, customcolor, ip, comfortable_rating) 
                        VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [$user['proper_username'], $user['email'], $user['password'], $token, $timestamp, $timestamp, $user["username"], $user['description'], '#354eab', $user["latest_ip"], "general"]);
        //youclipped db is case insensitive???
        //$ycUsernamesToSbIds[$user["username"]] = $sbDB->insertId();
        $ycUsernamesToSbIds[$fucked_username] = $sbDB->insertId();
    }
}

// flowerz account data is missing in the dump i got from her so we have to manually fetch her user id from the sb db
// to avoid breaking the script
$ycUsernamesToSbIds["flowerz"] = $sbDB->result("SELECT id from users WHERE name = 'flowerz'");
// more fucked accounts
$ycUsernamesToSbIds["boxepic"] = $sbDB->result("SELECT id from users WHERE name = 'boxepic'");
$ycUsernamesToSbIds["sparkzii"] = $sbDB->result("SELECT id from users WHERE name = 'UnknownYouClippedUser'");
$ycUsernamesToSbIds["vulntesting"] = $sbDB->result("SELECT id from users WHERE name = 'UnknownYouClippedUser'");

$ycVideos = $ycDB->fetchArray($ycDB->query("SELECT * FROM videos"));

foreach ($ycVideos as $video)
{
    echo "Copying " . $video["videotitle"] . "\n";

    $fucked_username = strtolower($video["author"]);

    $sbDB->query("INSERT INTO videos (video_id, title, description, author, time, videofile, videolength, original_site) VALUES (?,?,?,?,?,?,?,?)",
        [$video['vid'], $video['videotitle'], $video['description'], $ycUsernamesToSbIds[$fucked_username], strtotime($video['date']), "TODO_BY_SCRIPT", $video['duration'], "YouClipped"]);
}

$ycComments = $ycDB->fetchArray($ycDB->query("SELECT * FROM comments"));

foreach ($ycComments as $comment)
{
    $fucked_username = strtolower($comment["author"]);

    $sbDB->query("INSERT INTO comments (id, comment, author, date, deleted) VALUES (?,?,?,?,?)",
        [$comment['tovideoid'], $comment['comment'], $ycUsernamesToSbIds[$fucked_username], strtotime($comment['date']), 0]);
}