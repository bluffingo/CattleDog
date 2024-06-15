#!/usr/bin/php
<?php

require('lib/common.php');

global $host, $user, $pass;

// squareRewinder DB copier
// This copies the contents of an OpenSB database into another database, primarily intended for a timelapse video.
// Note that for now, this has only been tested with a squareBracket DB backup from early-2022. -Chaziz 6/14/2024

$shortopts = "";
$longopts = array(
    "db:",
    "date:"
);

$options = getopt($shortopts, $longopts);

if (!isset($options['db']) || !isset($options['date'])) {
    echo "Usage: php copy-db.php --db=<db> --date=<date>\n";
    exit(1);
}

$database_reference = $options['db'];
$dateLimit = $options['date'];

$date = DateTime::createFromFormat('Y-m-d', $dateLimit);

// poktube production db has been lost since around 2023 so the earliest we can go is april 27th 2021
// which is when a pre-alpha version of opensb was first setup on a production instance
if (!$date || $date < DateTime::createFromFormat('Y-m-d', '2021-04-27')) {
    echo "Invalid date limit. The date must be in the format 'YYYY-MM-DD' and no earlier than '2021-04-27'.\n";
    exit(1);
}

echo "Reference Database: $database_reference\n";
echo "Date: $dateLimit\n";

$scheme = null;

if ($date < DateTime::createFromFormat('Y-m-d', '2021-10-09')) {
    $scheme = 2021;
}

if ($scheme == null) {
    echo "Currently unsupported.'.\n";
    exit(1);
}

try {
    $database = new Database($host, $user, $pass);
} catch (Exception $e) {
    echo "Database initialization failure 1.\n";
    exit(1);
}

$database_name = "sb-rewinder_" . $dateLimit;

$database->executeDirectly("DROP DATABASE IF EXISTS `$database_name`;
                CREATE DATABASE `$database_name`;
                GRANT ALL ON `$database_name`.* TO '$user'@'localhost';
                FLUSH PRIVILEGES;");

// kinda stupid
try {
    $databaseToCopyFrom = new Database($host, $user, $pass, $database_reference); // REF DB
    $databaseToCopyTo = new Database($host, $user, $pass, $database_name); // DB TO COPY STUFF INTO
} catch (Exception $e) {
    echo "Database initialization failure 2.\n";
    exit(1);
}

if ($scheme == 2021) {
    $sql = file_get_contents('dbs/squarebracket-2021.sql');
    $databaseToCopyTo->executeDirectly($sql);
    echo "Initializing database with 2021 schema.\n";
}

$date->setTime(12, 0, 0);
$unixTimestamp = $date->getTimestamp();

$comments = $databaseToCopyFrom->fetchArray($databaseToCopyFrom->query("SELECT * FROM comments WHERE date < ?", [$unixTimestamp]));
$ratings = $databaseToCopyFrom->fetchArray($databaseToCopyFrom->query("SELECT * FROM rating")); // ratings don't have any timestamps
$followers = $databaseToCopyFrom->fetchArray($databaseToCopyFrom->query("SELECT * FROM subscriptions")); // followers don't have any timestamps
$users = $databaseToCopyFrom->fetchArray($databaseToCopyFrom->query("SELECT * FROM users WHERE joined < ?", [$unixTimestamp]));
$submissions = $databaseToCopyFrom->fetchArray($databaseToCopyFrom->query("SELECT * FROM videos WHERE time < ?", [$unixTimestamp]));
$views = $databaseToCopyFrom->fetchArray($databaseToCopyFrom->query("SELECT * FROM subscriptions")); // views didn't have any timestamps until 2024

echo "Copying comments.\n";
foreach ($comments as $comment) {
    if ($scheme == 2021) {
        $databaseToCopyTo->query("INSERT INTO comments (id, comment, author, date, deleted) VALUES (?,?,?,?,?)",
            [$comment["id"], $comment["comment"], $comment["author"], $comment["date"], $comment["deleted"]]);
    }
}

echo "Copying ratings.\n";
foreach ($ratings as $rating) {
    $databaseToCopyTo->query("INSERT INTO rating (user, video, rating) VALUES (?,?,?)",
        [$rating["user"], $rating["video"], $rating["rating"]]);
}

echo "Copying followers.\n";
foreach ($followers as $follower) {
    $databaseToCopyTo->query("INSERT INTO subscriptions (id, user) VALUES (?,?)",
        [$follower["id"], $follower["user"]]);
}

echo "Copying submissions.\n";
foreach ($submissions as $submission) {
    if ($submission["title"] == "squareBracket: March 2021 updates overview") {
        echo "Skipping march 2021 video.\n";
    } else {
        $submission["views"] = 0;

        $databaseToCopyTo->query("INSERT INTO videos (id, video_id, title, description, author, time, views, flags, category_id, tags, videofile, videolength) 
VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [$submission["id"], $submission["video_id"], $submission["title"], $submission["description"], $submission["author"], $submission["time"], $submission["views"], $submission["flags"], $submission["category_id"], $submission["tags"], $submission["videofile"], $submission["videolength"]]);
    }
}

echo "Copying users.\n";
foreach ($users as $user) {
    if ($user["name"] == "Gamerappa") {
        $user["name"] = "chaziz";
        $user["title"] = "chaziz";
    }
    if ($user["name"] == "cheeseRox") {
        $user["name"] = "squareBracket";
        $user["title"] = "squareBracket";
    }

    $databaseToCopyTo->query("INSERT INTO users (id, username, email, password, token, joined, lastview, display_name, description, color, language, u_flags, powerlevel) 
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
        [$user["id"], $user["name"], ($user["email"] ?? "dummy@dummy.com"), $user["password"], $user["token"], $user["joined"], $user["lastview"], $user["title"], $user["about"], $user["customcolor"], $user["language"], $user["u_flags"], $user["powerlevel"]]);
}

echo "Done!\n";