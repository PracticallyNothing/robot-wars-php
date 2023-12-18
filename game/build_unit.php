<?php

include_once "../utils.php";
post_only();
game_only_endpoint();

include_once "../connection.php";
$gameid = $_SESSION["gameid"];
$blueprint_id = (int)$_POST["blueprintId"];
$now = new DateTimeImmutable(
    exec_sql_scalar($conn, "select current_timestamp")
);
$now_str = $now->format(DT_FORMAT);

exec_sql(
    $conn,
    "insert into Units(GameId, BlueprintId, DatetimeDied) values (?, ?, NULL)",
    [$gameid, $blueprint_id]
);

$unit_info = exec_sql_first(
    $conn,
    "select LAST_INSERT_ID() as UnitId, UB.*" .
        " from  UnitBlueprints as UB" .
        " where UB.Id = ?",
    [$blueprint_id]
);

$unit_id = $unit_info["UnitId"];
$seconds_to_build = $unit_info["SecondsToBuild"];

$last_datetime_end = exec_sql_scalar(
    $conn,
    "select DatetimeEnd" .
        " from  GameCommands" .
        " where GameId = ? and DatetimeEnd > ?" .
        " order by DatetimeEnd desc" .
        " limit 1",
    [$gameid, $now_str],
);

if ($last_datetime_end != null) {
    $last_datetime_end = new DateTimeImmutable($last_datetime_end);
} else if ($last_datetime_end == false) {
    $last_datetime_end = null;
}

$end_time = ($last_datetime_end ?? $now)->add(new DateInterval("PT" . $seconds_to_build . "S"));

exec_sql(
    $conn,
    "insert into GameCommands(GameId, CommandType, UnitBlueprintId, UnitId, DatetimeEnd, DatetimeIssued)" .
        " values (?, ?, ?, ?, ?, ?)",
    [
        $gameid,
        "build_unit",
        $blueprint_id,
        $unit_id,
        $end_time->format(DT_FORMAT),
        $now_str,
    ]
);

header("Content-Type: application/json");
echo json_encode([
    "blueprintId" =>  $blueprint_id,
    "startTime" =>  ($last_datetime_end ?? $now)->format(DT_FORMAT),
    "endTime" =>  $end_time->format(DT_FORMAT),
]);
