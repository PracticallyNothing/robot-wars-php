<?php

include_once "../utils.php";
post_only();
game_only_endpoint();

include_once "../connection.php";
$gameid = $_SESSION["gameid"];
$unitId = (int)$_POST["unitId"];
$sector = (string)$_POST["sector"];

$now = new DateTimeImmutable(exec_sql_scalar($conn, "select current_timestamp"));
$now_str = $now->format(DT_FORMAT);

$unit_speed = exec_sql_scalar(
    $conn,
    "select UB.Speed from Units U join UnitBlueprints UB on UB.Id = U.BlueprintId where U.Id = ?",
    [$unitId]
);

sector_to_xy($sector);

function sector_to_xy(string $sectorName)
{
    // Use letter to determine angle
    define("NUM_SECTORS", ord("H"));
    define("NUM_RADIUSES", 9);

    $angleRad = M_PI * 2 * (
        (ord($sectorName[0]) - ord("A")) / (NUM_SECTORS + 1 - ord("A"))
    ) + deg2rad(360 / 16);

    // Use number to determine distance
    $dist = 0.1 + 0.9 * (((float)$sectorName[1] - 0.5) / NUM_RADIUSES);

    $x = cos($angleRad) * $dist;
    $y = sin($angleRad) * $dist;

    header("Content-Type: application/json");
    echo json_encode(["angleRad" => $angleRad, "x" => $x, "y" => $y,]);
}

function calc_unit_position($conn, $unitId, $gameid)
{
    $last_move_command = exec_sql_first($conn, "
      select UnitStartXPos as X, UnitStartYPos as Y, Sector
      from   GameCommands
      where CommandType = 'move'
        and UnitId = ?
        and GameId = ?
      order by DatetimeIssued desc
      limit 1
    ", [$unitId, $gameid]);

    if (!$last_move_command) {
        return ["x" => 0, "y" => 0];
    }

    return ["x" => 1, "y" => 1];
}
