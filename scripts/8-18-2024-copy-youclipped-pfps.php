#!/usr/bin/php
<?php

// YouClipped-to-squareBracket (Files)
// Chaziz, August 18th 2024

require('lib/common.php');

global $host, $user, $pass;

$ycDB = new Database($host, $user, $pass, "youclipped"); // youclipped database
$sbDB = new Database($host, $user, $pass, "squarebracket_migration"); // squarebracket database

$ycLocation = realpath("C:/content/pfp");
$sbLocation = realpath("C:/OpenSB/dynamic/pfp");

$ycUsers = $ycDB->fetchArray($ycDB->query("SELECT * FROM users"));
$sbUsers = $sbDB->fetchArray($sbDB->query("SELECT * FROM users"));

$ycNames = array_column($ycUsers, 'username');
$sbNames = array_column($sbUsers, 'name');

$matchingUsers = array_intersect($ycNames, $sbNames);
$ycIdsToSbIds = [];

// accounts on sb and youclipped that have matching usernames but arent from the same person. i think
$differentAccounts = ['i', 'o', 'xxx', '666', 'a'];

foreach ($ycUsers as $user)
{
    // squarebracket does not support spaces in usernames due to profile urls
    $user["proper_username"] = str_replace(' ', '_', $user["username"]);
    if (in_array($user['proper_username'], $differentAccounts)) {
        $user['proper_username'] .= '-YC';
        //$alreadyOnSB = false;
    } /* elseif (in_array($user['proper_username'], $matchingUsers)) {
        $alreadyOnSB = true;
    } else {
        $alreadyOnSB = false;
    } */

    $ycIdsToSbIds[$user["id"]] = $sbDB->result("SELECT id from users WHERE name = ?", [$user["proper_username"]]);
}

$files = scandir($ycLocation);

foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        $ycFilePath = $ycLocation . DIRECTORY_SEPARATOR . $file;
        
        $ycId = pathinfo($file, PATHINFO_FILENAME);

        if (is_numeric($ycId) && isset($ycIdsToSbIds[$ycId])) {
            $sbId = $ycIdsToSbIds[$ycId];
            $sbFilePath = $sbLocation . DIRECTORY_SEPARATOR . $sbId . '.' . pathinfo($file, PATHINFO_EXTENSION);

            if (is_file($ycFilePath)) {
                if (!file_exists($sbFilePath)) {
                    if (copy($ycFilePath, $sbFilePath)) {
                        echo "Copied: $ycFilePath to $sbFilePath\n";
                    } else {
                        echo "Failed to copy: $ycFilePath\n";
                    }
                } else {
                    echo "File already exists: $sbFilePath\n";
                }
            }
        } else {
            echo "Invalid or unmapped ID for file: $file\n";
        }
    }
}