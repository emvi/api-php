<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
//session_destroy();
require_once("emvi.php");

$client = new EmviClient("", "", "");

try {
	//$client->refreshToken();
	$result = $client->getArticle("");
	print "<h1>".$result->content->title."</h1>";
	print $result->content->content;
}
catch(Exception $e) {
	print "<p>Error reading article!</p>";
}
?>
