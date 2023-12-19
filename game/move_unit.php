<?php

include_once "../utils.php";
post_only();
game_only_endpoint();

include_once "../connection.php";
$gameid = $_SESSION["gameid"];
$unitId = (int) $_POST["unitId"];
$sector = (string) $_POST["sector"];

// TODO(Марио):
//   Въобще не е хубаво, че тези неща са хардкоднати!
define("NUM_SECTORS", 8);
define("MAX_SECTOR", ord("A") + NUM_SECTORS);
define("NUM_RADIUSES", 9);

define("MAP_SCALE", 100);

$now = new DateTimeImmutable(
    exec_sql_scalar($conn, "select current_timestamp"),
);
$now_str = $now->format(DT_FORMAT);

function fetch_unit_speed(mysqli $conn, int $unit_id): float|null
{
    return exec_sql_scalar(
        $conn,
        "select UB.Speed from Units U join UnitBlueprints UB on UB.Id = U.BlueprintId where U.Id = ?",
        [$unit_id],
    );
}

function sector_to_xy(string $sectorName)
{
    // Use letter to determine angle
    $angleRad =
        M_PI * 2 * ((ord($sectorName[0]) - ord("A")) / (MAX_SECTOR - ord("A"))) +
        deg2rad(360 / NUM_SECTORS / 2);

    // Use number to determine distance
    $dist = 0.1 + 0.9 * (((float) $sectorName[1] - 0.5) / NUM_RADIUSES);

    $x = cos($angleRad) * $dist;
    $y = sin($angleRad) * $dist;

    return ["x" => $x, "y" => $y];
}

function calc_unit_curr_position(
    mysqli $conn,
    int $unitId,
    int $gameid,
    DateTimeImmutable $now,
) {
    $last_move_command = exec_sql_first(
        $conn,
        "select UnitStartXPos, UnitStartYPos, Sector, DatetimeIssued, DatetimeEnd 
         from   GameCommands
         where CommandType = 'move'
           and UnitId = ?
           and GameId = ?
         order by DatetimeIssued desc
         limit 1",
        [$unitId, $gameid],
    );

    if (!$last_move_command) {
        return ["x" => 0, "y" => 0];
    }

    $start = [
        "x" => $last_move_command["UnitStartXPos"],
        "y" => $last_move_command["UnitStartYPos"],
    ];
    $dest = sector_to_xy($last_move_command["Sector"]);

    $startTime = (new DateTime(
        $last_move_command["DatetimeIssued"],
    ))->getTimestamp();
    $endTime = (new DateTimeImmutable(
        $last_move_command["DatetimeEnd"],
    ))->getTimestamp();

    // If the end time has come and gone, we've reached our destination.
    if($endTime <= $now->getTimestamp()) {
        return $dest;
    }

    $currProgress = $now->getTimestamp() - $startTime;
    $totalTime = $endTime - $startTime;

    $progressFraction = ((float) $currProgress) / $totalTime;


    return vec2_add(
        $start,
        vec2_mult_scalar(vec2_sub($dest, $start), $progressFraction),
    );
}

function fetch_unit_move_from(
    mysqli $conn,
    int $unitId,
    int $gameid,
) {
    $last_move_command = exec_sql_first(
        $conn,
        "select UnitStartXPos as x, UnitStartYPos as y 
         from   GameCommands
         where CommandType = 'move'
           and UnitId = ?
           and GameId = ?
         order by DatetimeIssued desc
         limit 1",
        [$unitId, $gameid],
    );

    if (!$last_move_command) {
        return ["x" => 0, "y" => 0];
    }

    return [
        "x" => $last_move_command["x"], 
        "y" => $last_move_command["y"]
    ];
}

function vec2_len($vec)
{
    return sqrt($vec["x"] * $vec["x"] + $vec["y"] * $vec["y"]);
}

function vec2_add($a, $b)
{
    return [
        "x" => $a["x"] + $b["x"],
        "y" => $a["y"] + $b["y"],
    ];
}

function vec2_sub($a, $b)
{
    return [
        "x" => $a["x"] - $b["x"],
        "y" => $a["y"] - $b["y"],
    ];
}

function vec2_mult_scalar($v, $s)
{
    return [
        "x" => $v["x"] * $s,
        "y" => $v["y"] * $s,
    ];
}

$unit_speed = fetch_unit_speed($conn, $unitId);
$curr_pos = calc_unit_curr_position($conn, $unitId, $gameid, $now);
$target = sector_to_xy($sector);
$seconds = (vec2_len($target) * MAP_SCALE) / $unit_speed;
$end_time = $now->add(new DateInterval("PT" . round($seconds) . "S"));

exec_sql(
    $conn,
    "
  insert into GameCommands(
    GameId, CommandType, 
    UnitId, 
    Sector, UnitStartXPos, UnitStartYPos, 
    DatetimeIssued, DatetimeEnd)
  values (
    ?, 'move',
    ?,
    ?, ?, ?,
    ?, ?
  )
",
    [
        $gameid,
        $unitId,
        $sector,
        $curr_pos["x"],
        $curr_pos["y"],
        $now_str,
        $end_time->format(DT_FORMAT),
    ],
);

header("Content-Type: application/json");
echo json_encode([
    "unitId" => $unitId,
    "unitSpeed" => $unit_speed,
    "moveFrom" => fetch_unit_move_from($conn, $unitId, $gameid),
    "moveTo" => sector_to_xy($sector),
    "currPosition" => $curr_pos,
    "moveStartTime" => $now_str,
    "moveEndTime" => $end_time->format(DT_FORMAT),
]);
