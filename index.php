<?php
require('lib/common.php');

$twig = twigloader();

$sbVideos = sbResult("SELECT COUNT(id) FROM videos");
$pokVideos = pokResult("SELECT COUNT(VideoID) FROM videodb");
$sbUsers = sbResult("SELECT COUNT(id) FROM users");
$pokUsers = pokResult("SELECT COUNT(id) FROM users");
$sbComments = sbResult("SELECT COUNT(id) FROM comments");
$pokComments = pokResult("SELECT COUNT(commentid) FROM comments");

echo $twig->render('index.twig', [
	'sbVideos' => $sbVideos,
	'pokVideos' => $pokVideos,
	'sbUsers' => $sbUsers,
	'pokUsers' => $pokUsers,
	'sbComments' => $sbComments,
	'pokComments' => $pokComments,
]);