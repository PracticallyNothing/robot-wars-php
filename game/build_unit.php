<?php

include_once "../utils.php";
post_only();
game_only_endpoint();

include_once "../connection.php";
$gameid = $_SESSION["gameid"];
$stmt = $conn->prepare(
    "insert into GameCommands(GameId, CommandType, UnitBlueprintId)" .
    " values (?, ?, ?)");

$result = $stmt->execute([
    $gameid,
    "build_unit",
    (int)$_POST["blueprintId"]
]);
