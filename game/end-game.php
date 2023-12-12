<?php
include_once "../utils.php";
post_only();
game_only_endpoint();

$gameid = $_SESSION["gameid"];

include_once "../connection.php";
$stmt = $conn->prepare("update Games set DatetimeEnded = current_timestamp where Id = ?");
$stmt->execute([$gameid]);

$_SESSION["gameid"] = null;

header("HX-Redirect: lobby.php");
