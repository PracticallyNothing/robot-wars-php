<?php
include_once "utils.php";
protected_endpoint();

include_once "connection.php";
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="/style.css" />
        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/htmx/1.9.9/htmx.min.js"
            integrity="sha512-FSS62yxqCRMCtm1J+ddRwX8DuCRVt/WMpihCo06P+Je5AG4CV9yoLX53zHaOB5w/eZdG7d/QAyUEJTnHZHrWKg=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer">
        </script>

        <title>
            Robot Wars - Game
        </title>
    </head>

    <?php
    function draw_line(float $rotation) {
      echo '<div style="transform: rotate(' .
        $rotation .
        'deg)"' .
        ' class="absolute inset-0 m-auto flex flex-row items-center justify-between">' .
        '  <hr data-angle="' .
        $rotation +
        180.0 .
        '" class="w-[42.5%] border z-10 border-green-200"/>' .
        '  <hr data-angle="' .
        $rotation .
        '" class="w-[42.5%] border z-10 border-green-200"/>' .
        "</div>";
    }

    function draw_circle(float $percent) {
      echo "<div data-radius=\"$percent\" style=\"width: $percent%; height: $percent%\"" .
        " class=\"game-circle m-auto absolute z-10 inset-0 border border-green-300 rounded-full\">" .
        "</div>";
    }
    ?>

    <body class="h-screen w-screen flex justify-center items-center overflow-hidden overflow-hidden">
        <div id="map" class="w-[95vmin] h-[95vmin] relative m-auto">
            <?= draw_line(15) ?>
            <?= draw_line(15 + 45 / 2) ?>
            <?= draw_line(60) ?>
            <?= draw_line(60 + 45 / 2) ?>
            <?= draw_line(105) ?>
            <?= draw_line(105 + 45 / 2) ?>
            <?= draw_line(150) ?>
            <?= draw_line(150 + 45 / 2) ?>

            <div id="map-scanner"
                 style="background: conic-gradient(#0000 300deg, #0f05 345deg, #0f05 358deg, #0000)"
                 class="w-full h-full
                     animate-spin-30s
                     absolute inset-0
                     border-2 border-transparent rounded-full">
            </div>

            <div class="w-full h-full m-auto absolute inset-0
                        border-4 border-green-300 rounded-full"></div>
            <?= draw_circle(85) ?>
            <?= draw_circle(75) ?>
            <?= draw_circle(65) ?>
            <?= draw_circle(55) ?>
            <?= draw_circle(45) ?>
            <?= draw_circle(35) ?>
            <?= draw_circle(25) ?>
            <?= draw_circle(15) ?>

            <canvas class="w-full h-full z-20"></canvas>
        </div>
    </body>

    <script>
     let canvas = document.querySelector("canvas");
     let ctx = canvas.getContext("2d");

     let lineAngles = []
     document.querySelectorAll("hr[data-angle]").forEach((line) => {
         let angleDegrees = parseFloat(line.attributes["data-angle"].nodeValue);
         lineAngles.push(angleDegrees * (Math.PI / 180) - Math.PI / 2);
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

     document.body.addEventListener("mousemove", (e) =>  {
         let rect = canvas.getBoundingClientRect();
         mouseX = e.clientX - rect.left;
         mouseY = e.clientY - rect.top;
     });

     function draw(timestamp) {
         canvas.width = canvas.parentNode.clientWidth;
         canvas.height = canvas.parentNode.clientHeight;

         ctx.clearRect(0, 0, canvas.width, canvas.height);

         let radiuses = circleRadiuses.map((x) => (x / 100) * (canvas.width / 2))

         let arcX = canvas.width  / 2
         let arcY = canvas.height / 2

         let radialMouseX = mouseX - arcX
         let radialMouseY = mouseY - arcY

         let upVec = [0, 1]
         let mouseVecLen = Math.sqrt(radialMouseX * radialMouseX + radialMouseY * radialMouseY)
         let mouseVecNorm = [radialMouseX, radialMouseY].map((x) =>  x/mouseVecLen)
         let angle = Math.atan2(1, 0) - Math.atan2(mouseVecNorm[0], mouseVecNorm[1])


         // ctx.beginPath()
         // ctx.moveTo(arcX, arcY)
         // ctx.arc(arcX, arcY, canvas.height * (mouseVecLen / canvas.height), 0, angle)
         // ctx.closePath();
         // ctx.stroke();

         // Default case: the angles of the lines are the last and first angles.
         let minAngle = lineAngles[lineAngles.length - 1]
         let maxAngle = lineAngles[0] + Math.PI * 2

         // Find the two angles that the mouse's angle fits between.
         for(let i = 1; i < lineAngles.length; i++) {
             if(lineAngles[i] > angle) {
                 minAngle = lineAngles[i-1];
                 maxAngle = lineAngles[i];
                 break;
             }
         }

         let minRadius = radiuses[radiuses.length - 1]
         let maxRadius = canvas.width / 2

         for(let i = 1; i < radiuses.length; i++) {
             if(radiuses[i] > mouseVecLen) {
                 minRadius = radiuses[i-1];
                 maxRadius = radiuses[i];
                 break;
             }
         }

         ctx.lineWidth = 5;
         ctx.strokeStyle = "rgb(0, 100, 200)";
         ctx.fillStyle = "rgb(0, 100, 200)";
         ctx.moveTo(arcX, arcY)
         ctx.beginPath()
         ctx.arc(arcX, arcY, minRadius, minAngle, maxAngle)
         ctx.arc(arcX, arcY, maxRadius, maxAngle, minAngle, true)
         ctx.closePath();
         ctx.fill();

         window.requestAnimationFrame(draw);
     }

     window.requestAnimationFrame(draw);
    </script>
</html>
