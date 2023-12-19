<?php

define("NUM_SECTORS", 8);
define("MAX_SECTOR", ord("A") + NUM_SECTORS);
define("NUM_RADIUSES", 9);

define("MAP_SCALE", 100);

function vec2_len($vec)
{
    return sqrt($vec["x"] * $vec["x"] + $vec["y"] * $vec["y"]);
}

function vec2_add($a, $b) {
    return [
        "x" => $a["x"] + $b["x"],
        "y" => $a["y"] + $b["y"],
    ];
}

function vec2_sub($a, $b) {
    return [
        "x" => $a["x"] - $b["x"],
        "y" => $a["y"] - $b["y"],
    ];
}

function vec2_mult_scalar($v, $s) {
    return [
        "x" => $v["x"] * $s,
        "y" => $v["y"] * $s,
    ];
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

include_once "utils.php";

function fetch_unit_speed(mysqli $conn, int $unit_id): float|null {
    return exec_sql_scalar(
        $conn,
        "select UB.Speed from Units U join UnitBlueprints UB on UB.Id = U.BlueprintId where U.Id = ?",
        [$unit_id],
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

function fetch_all_last_move_commands($conn, $gameid): array {
    $last_move_commands = exec_sql_all(
        $conn,
        "select main.UnitId, GC.UnitStartXPos, GC.UnitStartYPos, GC.Sector, GC.DatetimeIssued, GC.DatetimeEnd
         from (select UnitId, max(Id) as LastCommandId
               from   GameCommands
               where  CommandType = 'move'
                 and  GameId = ?
               group by UnitId) main
           join GameCommands GC on main.LastCommandId = GC.Id",
        [$gameid],
    );

    $cmds = [];

    foreach ($last_move_commands as $cmd) {
        $cmds[$cmd["UnitId"]] = [
            "unitId" => $cmd["UnitId"],
            "moveFrom" => [
                "x" => $cmd["UnitStartXPos"],
                "y" => $cmd["UnitStartYPos"],
            ],
            "moveTo" => sector_to_xy($cmd["Sector"]),
            "moveStartTime" => new DateTimeImmutable($cmd["DatetimeIssued"]),
            "moveEndTime" => new DateTimeImmutable($cmd["DatetimeEnd"]),
        ];
    }

    return $cmds;
}
