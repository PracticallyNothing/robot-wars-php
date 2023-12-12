<?php
include_once "../utils.php";
post_only();
game_only_endpoint();

$gameid = $_SESSION["gameid"];

include_once "../connection.php";
// TODO: End the actual game in the DB.

$_SESSION["gameid"] = null;

header("HX-Redirect: lobby.php");
