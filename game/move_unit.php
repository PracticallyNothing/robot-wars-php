<?php

include_once "../utils.php";
post_only();
game_only_endpoint();

include_once "../connection.php";
$gameid = $_SESSION["gameid"];
$unitId = (int) $_POST["unitId"];
$sector = (string) $_POST["sector"];

include_once "../gameutils.php";

// TODO(Марио):
//   Въобще не е хубаво, че тези неща са хардкоднати!

$now = new DateTimeImmutable(
    exec_sql_scalar($conn, "select current_timestamp"),
);
$now_str = $now->format(DT_FORMAT);


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
