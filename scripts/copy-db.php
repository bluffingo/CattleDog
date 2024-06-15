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

// poktube production db has been lost since around 2023 so the earliest we can go is april 28th 2021
// which is when a pre-alpha version of opensb was first setup on a production instance
if (!$date || $date < DateTime::createFromFormat('Y-m-d', '2021-04-28')) {
    echo "Invalid date limit. The date must be in the format 'YYYY-MM-DD' and no earlier than '2021-04-28'.\n";
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

$database_name = "sb-" . $dateLimit;

$database->executeDirectly("DROP DATABASE `$database_name`;
                CREATE DATABASE `$database_name`;
                GRANT ALL ON `$database_name`.* TO '$user'@'localhost';
                FLUSH PRIVILEGES;");

// kinda stupid
try {
    $databaseToCopyFrom = new Database($host, $user, $pass, $database_name);
    $databaseToCopyTo = new Database($host, $user, $pass, $database_reference);
} catch (Exception $e) {
    echo "Database initialization failure 2.\n";
    exit(1);
}

if ($scheme == 2021) {
    $sql = file_get_contents('dbs/squarebracket-2021.sql');
    $databaseToCopyTo->executeDirectly($sql);
    echo "Initializing database with 2021 schema.\n";
}