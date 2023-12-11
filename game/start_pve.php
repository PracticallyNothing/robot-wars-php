<?php
include_once "../utils.php";

post_only();
protected_endpoint();

if(isset($_SESSION["gameid"])) {
    header("HX-Redirect: /game.php");
    exit();
}

include_once "../connection.php";

$stmt = $conn->prepare(
    "insert into Games(UserId) values (?)");
$stmt->execute([$_SESSION["userid"]]);

$game_id = $conn->query(
    "select LAST_INSERT_ID()")->fetch_column();

$_SESSION["gameid"] = $game_id;

if(isset($_SERVER["HTTP_HX_REQUEST"]))
    header("HX-Redirect: /game.php");
else
    header("Location: /game.php");
