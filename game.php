<?php
include_once "utils.php";
game_only_endpoint();
$gameid = $_SESSION["gameid"];

include_once "connection.php";
$game_info = exec_sql_first($conn, "select * from Games where Id = ?", [
    $gameid,
]);

$units_in_queue = exec_sql_all(
    $conn,
    "select * from GameCommands" .
        " where GameId = ?" .
        "   and CommandType = 'build_unit'" .
        "   and DatetimeEnd > current_timestamp" .
        " order by DatetimeEnd asc",
    [$gameid],
);

function get_living_units($conn, $gameid)
{
    return exec_sql_all(
        $conn,
        "select *" .
            " from   Units U" .
            "   join UnitBlueprints UB on U.BlueprintId = UB.Id" .
            "   join GameCommands GC on U.Id = GC.UnitId and CommandType = 'build_unit'" .
            " where U.DatetimeDied is NULL" .
            "   and U.GameId = ?" .
            "   and GC.DatetimeEnd <= current_timestamp",
        [$gameid],
    );
}

function get_num_dead_units($conn, $gameid)
{
    return exec_sql_scalar(
        $conn,
        "select Count(*) from Units where current_timestamp > DatetimeDied and GameId = ?",
        [$gameid],
    );
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="/style.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/htmx/1.9.9/htmx.min.js" integrity="sha512-FSS62yxqCRMCtm1J+ddRwX8DuCRVt/WMpihCo06P+Je5AG4CV9yoLX53zHaOB5w/eZdG7d/QAyUEJTnHZHrWKg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    </script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous">
    </script>

    <title>
        Robot Wars - Game
    </title>
</head>

<body class="h-screen w-screen flex justify-center items-center overflow-hidden bg-gray-800 text-white">
    <!-- <pre class="w-[40ch]">
             Mouse Position: <span id="mouse-pos"></span>
             Angle: <span id="angle"></span>
             Min/Max Angle: <span id="min-max-angle"></span>
             </pre> -->
    <div id="side-panel" class="w-96 h-full border-r border-teal-600 bg-gray-900">
        <div id="side-panel-tabs" class="flex flex-row justify-evenly items-center">
            <button class="current flex-grow basis-full px-2 py-2 [&.current]:bg-emerald-600 hover:bg-blue-600 active:bg-blue-700" onclick="showTab(event, 'unit-panel')">
                Build
            </button>

            <button id="side-panel-queue-button" class="px-2 flex-grow basis-full py-2 [&.current]:bg-emerald-600 hover:bg-blue-600 active:bg-blue-700" onclick="showTab(event, 'queue-panel')">
                Queue
            </button>

            <button class="px-2 flex-grow basis-full py-2 [&.current]:bg-emerald-600 hover:bg-blue-600 active:bg-blue-700" onclick="showTab(event, 'score-panel')">
                Score
            </button>
        </div>
        <hr class="mb-2 border-teal-500" />
        <div id="unit-panel" class="current-tab h-full hidden [&.current-tab]:block">
            <?php
            $result = $conn->query("select * from UnitBlueprints");

            if (!$result) {
                echo "ERROR: Failed to download blueprints!";
            }

            $blueprints = [];

            while ($blueprint = $result->fetch_assoc()) {

                $blueprints[] = $blueprint;

                $id = (int) $blueprint["Id"];
                $name = (string) $blueprint["Name"];
                $caption = (string) $blueprint["Caption"];
                $cost = (float) $blueprint["Cost"];
                $secondsToBuild = (float) $blueprint["SecondsToBuild"];
            ?>
                <button id="build-<?= $id ?>" onclick="buildUnit(<?= $id ?>)" class="flex flex-row items-center w-full hover:bg-slate-600 active:bg-slate-700 px-2 py-2">
                    <div class="w-20 h-20 flex justify-center items-center">
                        <img class="m-auto" src="/<?= $name ?>-icon.svg" />
                    </div>

                    <div class="flex flex-col flex-grow justify-center ml-4">
                        <p class="text-xl text-left"><?= $caption ?></p>
                        <pre class="text-left italic text-sm">🗲 <?= $cost ?></pre>
                    </div>

                    <div class="h-full flex flex-col justify-center items-center">
                        <p class="text-right">
                            <?= number_format(
                                $secondsToBuild,
                                2,
                                ".",
                                "",
                            ) ?> sec. </p>
                    </div>
                </button>
            <?php
            }
            ?>
        </div>
        <div id="queue-panel" class="w-full flex-col gap-2 hidden [&.current-tab]:flex">
        </div>
        <div id="score-panel" class="px-4 h-full w-full flex-col hidden [&.current-tab]:flex justify-center">
            <div class="grid grid-cols-2 gap-y-4">
                <div>
                    <p>You've lasted for:</p>
                    <h2 id="score-lasted-for-time" class="text-2xl" data-dt-game-started="<?= $game_info["DatetimeCreated"] ?>">
                        <?php
                        $now = new DateTimeImmutable();
                        $now = $now->add(new DateInterval("PT1H"));

                        $start = new DateTimeImmutable(
                            $game_info["DatetimeCreated"],
                        );

                        echo $now->diff($start)->format("%H:%I:%S");
                        ?>
                    </h2>
                </div>
                <div>
                    <p>You've mined:</p>
                    <h2 id="score-minerals-mined" class="text-2xl"> 123456 minerals</h2>
                </div>
                <div>
                    <p>You've killed:</p>
                    <h2 id="score-aliens-killed" class="text-2xl">280000 aliens</h2>
                </div>
                <div>
                    <p>You've lost:</p>
                    <h2 id="score-units-lost" class="text-2xl"><?= get_num_dead_units(
                                                                    $conn,
                                                                    $gameid,
                                                                ) ?> units</h2>
                </div>
            </div>
            <button class="border border-red-400 py-4 bg-red-800 hover:bg-red-700 active:bg-red-600 font-bold my-2" hx-post="/game/end-game.php" hx-confirm="Are you sure you want to end the game?">End game</button>
        </div>
    </div>

    <?php
    function draw_line(float $rotation)
    {
        return '<div style="transform: rotate(' .
            $rotation .
            'deg)"' .
            ' class="absolute inset-0 m-auto flex flex-row items-center justify-between">' .
            '  <hr data-angle="' .
            $rotation +
            180.0 .
            '" class="w-[45%] border-2 z-10 border-green-200"/>' .
            '  <hr data-angle="' .
            $rotation .
            '" class="w-[45%] border-2 z-10 border-green-200"/>' .
            "</div>";
    }

    function draw_circle(float $percent)
    {
        return "<div data-radius=\"$percent\" style=\"width: $percent%; height: $percent%\"" .
            " class=\"game-circle m-auto absolute z-10 inset-0 border-2 border-green-300 rounded-full pointer-events-none\">" .
            "</div>";
    }
    ?>

    <div id="map" class="w-[95vmin] h-[95vmin] relative m-auto">
        <?= draw_line(0) ?>
        <?= draw_line(45) ?>
        <?= draw_line(90) ?>
        <?= draw_line(135) ?>

        <div id="map-scanner" style="background: conic-gradient(#0000 300deg, #0f08 345deg, #0f08 358deg, #0000)" class="w-full h-full
        pointer-events-none
                     animate-spin-30s
                     absolute inset-0
                     border-2 border-transparent rounded-full">
        </div>

        <div class="w-full h-full m-auto absolute inset-0 pointer-events-none
                        border-4 border-green-300 rounded-full"></div>
        <?= draw_circle(90) ?>
        <?= draw_circle(80) ?>
        <?= draw_circle(70) ?>
        <?= draw_circle(60) ?>
        <?= draw_circle(50) ?>
        <?= draw_circle(40) ?>
        <?= draw_circle(30) ?>
        <?= draw_circle(20) ?>
        <?= draw_circle(10) ?>

        <canvas class="w-full h-full z-20"></canvas>
    </div>
</body>

<script id="game-code">
    let queueButton = document.getElementById("side-panel-queue-button")
    let queuePanel = document.getElementById("queue-panel")

    let gameStart = new Date("<?= $game_info["DatetimeCreated"] ?>")
    let gameScoreText = document.getElementById("score-lasted-for-time")

    setInterval(() => {
        let diff = new Date(new Date() - gameStart);

        let hours = diff.getUTCHours().toString().padStart(2, "0")
        let minutes = diff.getUTCMinutes().toString().padStart(2, "0")
        let seconds = diff.getUTCSeconds().toString().padStart(2, "0")

        gameScoreText.innerText = `${hours}:${minutes}:${seconds}`
    }, 1000)

    let unitBlueprints = {
        <?php foreach ($blueprints as $bp) {
            $id = (int) $bp["Id"];
            $name = $bp["Name"];
            $cost = $bp["Cost"];
            $speed = $bp["Speed"];
            $buildTime = $bp["SecondsToBuild"];
        ?>
            <?= $id ?>: {
                name: "<?= $name ?>",
                cost: <?= $cost ?>,
                speed: <?= $speed ?>,
                secondsToBuild: <?= $buildTime ?>,
            },
        <?php
        } ?>
    }

    let buildQueue = [
        <?php foreach ($units_in_queue as $unit) { ?> {
                unitId: <?= (int)$unit["UnitId"] ?>,
                blueprintId: <?= $unit["UnitBlueprintId"] ?>,
                startTime: new Date("<?= $unit["DatetimeIssued"] ?>"),
                endTime: new Date("<?= $unit["DatetimeEnd"] ?>")
            },
        <?php } ?>
    ];

    <?php
    $living_units = get_living_units($conn, $gameid);
    $living_unit_data = [];

    foreach ($living_units as $unit) {
        $living_unit_data[] = [
            "id" => $unit["Id"],
            "name" => $unit["Name"],
            "currPosition" => ["x" => 0, "y" => 0],
            "movingTo" => null,
        ];
    }
    ?>

    let livingUnits = {};

    function renderUnitInQueue(unit) {
        return `<div class="flex flex-row gap-2 justify-evenly px-2">
             <pre class="w-[14ch] text-left">${unit.name}</pre>
             <progress value="0"></progress>
             <pre id="time-left" class="w-[6ch] text-right">${unit.secondsToBuild.toFixed(2)}</pre>
         </div>`;
    }

    if (buildQueue.length > 0) {
        queueButton.innerText = `Queue (${buildQueue.length})`
        for (let unit of buildQueue) {
            queuePanel.innerHTML += renderUnitInQueue(unitBlueprints[unit.blueprintId])
        }
    }

    function showTab(event, tab) {
        if (event.target.classList.contains("current"))
            return;

        document.querySelector(".current").classList.remove("current")
        event.target.classList.add("current")

        document.querySelector(".current-tab").classList.remove("current-tab")
        document.getElementById(tab).classList.add("current-tab")
    }

    let selectedUnitId = null
    let selectedUnitImg = null

    function spawnUnit(unit) {
        if (livingUnits[unit.id] != null) return;

        livingUnits[unit.id] = unit;

        let unitIcon = document.createElement("img")
        unitIcon.classList.add("rounded-full")
        unitIcon.classList.add("border")
        unitIcon.classList.add("px-2")
        unitIcon.classList.add("py-2")
        unitIcon.classList.add("border-transparent")
        unitIcon.classList.add("border-dashed")
        unitIcon.classList.add("hover:border-gray-300")
        unitIcon.classList.add("[&.selected-unit]:border-solid")
        unitIcon.classList.add("[&.selected-unit]:border-white")
        unitIcon.classList.add("[&.selected-unit]:bg-orange-600/75")

        unitIcon.src = `/${unit.name}-icon.svg`;
        unitIcon.style.position = "absolute";
        unitIcon.style.top = "50%";
        unitIcon.style.left = "50%";
        unitIcon.style.width = "7%";
        unitIcon.style.height = "7%";
        unitIcon.style.transform = "translate(-50%, -50%)";
        unitIcon.addEventListener("click", (event) => {
            if (selectedUnitId != null) {
                selectedUnitImg.classList.remove("selected-unit")
            }

            if (selectedUnitId == unit.id) {
                selectedUnitId = null;
                selectedUnitImg = null;
            } else {
                selectedUnitId = unit.id;
                selectedUnitImg = unitIcon;
                unitIcon.classList.add("selected-unit")
            }
        })

        console.log(unitIcon);

        document.getElementById("map").appendChild(unitIcon)
    }
    let initialUnitsToSpawn = <?= json_encode($living_unit_data) ?>;
    for (let unit of initialUnitsToSpawn) {
        console.log("Spawning", unit)
        spawnUnit(unit)
    }

    function tick() {
        if (buildQueue.length != 0) {
            let currentlyBuildingUnit = buildQueue[0];
            let unitInfo = unitBlueprints[currentlyBuildingUnit.blueprintId];

            // Update progress bar:
            let timeLeft = queuePanel.children[0].querySelector("#time-left")
            let progressBar = queuePanel.children[0].querySelector("progress");

            let diffMs = new Date() - currentlyBuildingUnit.startTime;
            let totalTimeMs = currentlyBuildingUnit.endTime - currentlyBuildingUnit.startTime;

            timeLeft.innerText = ((totalTimeMs - diffMs) / 1000).toFixed(2);
            progressBar.value = diffMs / totalTimeMs;

            // Remove unit from queue if it's done building.
            if (diffMs >= totalTimeMs) {
                queuePanel.removeChild(queuePanel.children[0])
                buildQueue.splice(0, 1);

                let bp = unitBlueprints[currentlyBuildingUnit.blueprintId]
                spawnUnit({
                    id: currentlyBuildingUnit.unitId,
                    name: bp.name,
                    currPosition: {
                        x: 0,
                        y: 0
                    },
                    movingTo: null,
                })

                if (buildQueue.length > 0) {
                    queueButton.innerText = `Queue (${buildQueue.length})`
                } else {
                    queueButton.innerText = `Queue`
                }
            }
        }

        window.requestAnimationFrame(tick);
    }
    window.requestAnimationFrame(tick);

    function buildUnit(blueprintId) {
        queuePanel.innerHTML += renderUnitInQueue(unitBlueprints[blueprintId])
        queueButton.innerText = `Queue (${buildQueue.length + 1})`

        let json = $.ajax({
            async: false,
            method: "POST",
            url: "/game/build_unit.php",
            data: {
                blueprintId: blueprintId
            },
            headers: {
                "JQuery-Request": "1"
            },
            statusCode: {
                401: () => window.location.href = "/index.php",
                403: () => window.location.href = "/lobby.php"
            }
        }).responseJSON;

        buildQueue.push({
            unitId: json.unitId,
            blueprintId: json.blueprintId,
            startTime: new Date(json.startTime),
            endTime: new Date(json.endTime),
        });
    }

    let currSelectedPos = {
        x: 0,
        y: 0
    }

    

    function sendUnitToSector(unitId, sector) {
        console.log(`Sending unit ${unitId} to sector ${sector}!`)
        let json = $.ajax({
            async: false,
            method: "POST",
            url: "/game/move_unit.php",
            data: {
                unitId: unitId,
                sector: sector,
            },
            headers: {
                "JQuery-Request": "1"
            },
            statusCode: {
                401: () => window.location.href = "/index.php",
                403: () => window.location.href = "/lobby.php"
            }
        }).responseJSON;

        let canvas = document.querySelector("canvas");
        currSelectedPos = json;
        console.log(currSelectedPos,
            (currSelectedPos.x + 1) * canvas.width,
            (1 - currSelectedPos.y) * canvas.height)

    }
</script>

<script id="rendering-code">
    let canvas = document.querySelector("canvas");
    let ctx = canvas.getContext("2d");

    let lineAngles = []
    document.querySelectorAll("hr[data-angle]").forEach((line) => {
        let angleDegrees = parseFloat(line.attributes["data-angle"].nodeValue);
        lineAngles.push(angleDegrees * (Math.PI / 180));
    });
    lineAngles.sort((a, b) => a - b)

    let circleRadiuses = []
    document.querySelectorAll("div.game-circle[data-radius]").forEach((circleDiv) => {
        let radiusPercent = parseFloat(circleDiv.attributes["data-radius"].nodeValue);
        circleRadiuses.push(radiusPercent);
    });
    circleRadiuses.sort((a, b) => a - b);

    let mouseX = null;
    let mouseY = null;
    let mouseDown = false;

    let map = document.getElementById("map")

    map.addEventListener("mousemove", (e) => {
        let rect = canvas.getBoundingClientRect();
        mouseX = e.clientX - rect.left;
        mouseY = e.clientY - rect.top;
    });

    function xyToSector(x, y) {
        let arcX = canvas.width / 2
        let arcY = canvas.height / 2
        let radialX = x - arcX
        let radialY = y - arcY

        let upVec = [0, 1]
        let vecLen = Math.sqrt(radialX * radialX + radialY * radialY)
        let vecNorm = [radialX, radialY].map((x) => x / vecLen)
        let angle = Math.atan2(1, 0) - Math.atan2(vecNorm[0], vecNorm[1])

        if (angle < 0) {
            angle = (Math.PI / 2 + angle) + 1.5 * Math.PI
        }

        let radiuses = circleRadiuses.map((x) => (x / 100) * (canvas.width / 2))
        let sectorNumber = radiuses.length

        if (vecLen >= radiuses[0] && vecLen <= canvas.width / 2) {
            for (let i = 1; i < radiuses.length; i++) {
                if (radiuses[i] > vecLen) {
                    sectorNumber = i;
                    break;
                }
            }
        }

        let sectorLetter;

        if (angle < lineAngles[0] || angle > lineAngles[lineAngles.length - 1]) {
            sectorLetter = String.fromCharCode("A".charCodeAt(0) + lineAngles.length - 1)
        } else {
            for (let i = 1; i < lineAngles.length; i++) {
                if (lineAngles[i] > angle) {
                    sectorLetter = String.fromCharCode("A".charCodeAt(0) + i - 1)
                    break;
                }
            }
        }

        return `${sectorLetter}${sectorNumber}`
    }

    map.addEventListener("contextmenu", (e) => {
        e.preventDefault();
        if (selectedUnitId == null) {
            return;
        }

        sendUnitToSector(selectedUnitId, xyToSector(mouseX, mouseY))
    });

    function draw(timestamp) {
        canvas.width = canvas.parentNode.clientWidth;
        canvas.height = canvas.parentNode.clientHeight;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        let radiuses = circleRadiuses.map((x) => (x / 100) * (canvas.width / 2))

        let arcX = canvas.width / 2
        let arcY = canvas.height / 2

        let radialMouseX = mouseX - arcX
        let radialMouseY = mouseY - arcY

        let upVec = [0, 1]
        let mouseVecLen = Math.sqrt(radialMouseX * radialMouseX + radialMouseY * radialMouseY)
        let mouseVecNorm = [radialMouseX, radialMouseY].map((x) => x / mouseVecLen)
        let angle = Math.atan2(1, 0) - Math.atan2(mouseVecNorm[0], mouseVecNorm[1])

        if (angle < 0) {
            angle = (Math.PI / 2 + angle) + 1.5 * Math.PI
        }

        // ctx.strokeStyle = "rgb(200, 200, 200)";
        // ctx.beginPath()
        // ctx.moveTo(arcX, arcY)
        // ctx.arc(arcX, arcY, canvas.height * (mouseVecLen / canvas.height), 0, angle)
        // ctx.closePath();
        // ctx.stroke();

        let minAngle = null
        let maxAngle = null

        // If the mouse is between the first and the last lines,
        // highlight that specific sector.
        if (angle < lineAngles[0] || angle > lineAngles[lineAngles.length - 1]) {
            minAngle = lineAngles[lineAngles.length - 1]
            maxAngle = lineAngles[0] + Math.PI * 2
        } else {
            // Otherwise, find the two lines that the mouse's angle fits between.
            for (let i = 1; i < lineAngles.length; i++) {
                if (lineAngles[i] > angle) {
                    minAngle = lineAngles[i - 1];
                    maxAngle = lineAngles[i];
                    break;
                }
            }
        }

        const degToRad = (deg) => deg * (Math.PI / 180.0);
        const radToDeg = (rad) => rad * (180.0 / Math.PI);

        // Useful debugging code - shows where the mouse is:
        // document.getElementById("mouse-pos").innerText = `(${radialMouseX.toFixed(2)}, ${radialMouseY.toFixed(2)})`
        // document.getElementById("angle").innerText = radToDeg(angle).toFixed(2)
        // document.getElementById("min-max-angle").innerText = `${radToDeg(minAngle).toFixed(2)} / ${radToDeg(maxAngle).toFixed(2)}`

        ctx.strokeStyle = "rgb(200, 200, 200)";
        ctx.beginPath()
        ctx.moveTo(arcX, arcY)
        ctx.lineTo(
            (currSelectedPos.x + 1) / 2 * canvas.width,
            (currSelectedPos.y + 1) / 2 * canvas.height)
        ctx.closePath();
        ctx.stroke();


        // Only draw a segment if the mouse is within the map.
        if (mouseVecLen >= radiuses[0] && mouseVecLen <= canvas.width / 2) {
            let minRadius = radiuses[radiuses.length - 1]
            let maxRadius = canvas.width / 2

            for (let i = 1; i < radiuses.length; i++) {
                if (radiuses[i] > mouseVecLen) {
                    minRadius = radiuses[i - 1];
                    maxRadius = radiuses[i];
                    break;
                }
            }

            ctx.lineWidth = 5;
            // ctx.strokeStyle = "rgb(200, 200, 200)";
            if (mouseDown)
                ctx.fillStyle = "rgba(0, 100, 200, 0.8)";
            else
                ctx.fillStyle = "rgba(150, 150, 255, 0.5)";
            ctx.moveTo(arcX, arcY)
            ctx.beginPath()
            ctx.arc(arcX, arcY, minRadius, minAngle, maxAngle)
            ctx.arc(arcX, arcY, maxRadius, maxAngle, minAngle, true)
            ctx.closePath();
            // ctx.stroke();
            ctx.fill();
        }

        window.requestAnimationFrame(draw);
    }

    window.requestAnimationFrame(draw);
</script>

</html>