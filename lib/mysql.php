<?php

$options = [
	PDO::ATTR_ERRMODE				=> PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES		=> false,
];
try {
	$sbSQL = new PDO("mysql:host=$host;dbname=$squarebracketDB;charset=utf8mb4", $user, $pass, $options);
} catch (\PDOException $e) {
	die("Error - Can't connect to squareBracket database. Please try again later.");
}
try {
	$pokSQL = new PDO("mysql:host=$host;dbname=$poktubeDB;charset=utf8mb4", $user, $pass, $options);
} catch (\PDOException $e) {
	die("Error - Can't connect to PokTube database. Please try again later.");
}

function sbQuery($query,$params = []) {
	global $sbSQL;

	$res = $sbSQL->prepare($query);
	$res->execute($params);
	return $res;
}

function sbFetch($query,$params = []) {
	$res = sbQuery($query,$params);
	return $res->fetch();
}

function sbResult($query,$params = []) {
	$res = sbQuery($query,$params);
	return $res->fetchColumn();
}

function sbInsertId() {
	global $sbSQL;
	return $sbSQL->lastInsertId();
}

function pokQuery($query,$params = []) {
	global $pokSQL;

	$res = $pokSQL->prepare($query);
	$res->execute($params);
	return $res;
}

function pokFetch($query,$params = []) {
	$res = pokQuery($query,$params);
	return $res->fetch();
}

function pokResult($query,$params = []) {
	$res = pokQuery($query,$params);
	return $res->fetchColumn();
}

function pokInsertId() {
	global $pokSQL;
	return $pokSQL->lastInsertId();
}

function fetchArray($query) {
	$out = [];
	while ($record = $query->fetch()) {
		$out[] = $record;
	}
	return $out;
}