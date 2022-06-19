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
	$otherSQL = new PDO("mysql:host=$host;dbname=$otherDB;charset=utf8mb4", $user, $pass, $options);
} catch (\PDOException $e) {
	die("Error - Can't connect to other database. Please try again later.");
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

function otherQuery($query,$params = []) {
	global $otherSQL;

	$res = $otherSQL->prepare($query);
	$res->execute($params);
	return $res;
}

function otherFetch($query,$params = []) {
	$res = otherQuery($query,$params);
	return $res->fetch();
}

function otherResult($query,$params = []) {
	$res = otherQuery($query,$params);
	return $res->fetchColumn();
}

function otherInsertID() {
	global $otherSQL;
	return $otherSQL->lastInsertId();
}

function fetchArray($query) {
	$out = [];
	while ($record = $query->fetch()) {
		$out[] = $record;
	}
	return $out;
}