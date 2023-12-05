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
    function draw_line(int $rotation) {
      echo '<div style="transform: rotate(' .
        $rotation .
        'deg)"' .
        ' class="absolute inset-0 m-auto flex flex-row items-center justify-between">' .
        '  <hr class="w-[42.5%] border z-10 border-green-200"/>' .
        '  <hr class="w-[42.5%] border z-10 border-green-200"/>' .
        "</div>";
    }

    function draw_circle(int $percent) {
      echo "<div style=\"width: $percent%; height: $percent%\"" .
        " class=\"m-auto absolute z-10 inset-0 border border-green-300 rounded-full\">" .
        "</div>";
    }
    ?>

    <body class="h-screen w-screen overflow-y-hidden">
        <div id="map" class="w-[95vmin] h-[95vmin] relative m-auto">
            <?= draw_line(15) ?>
            <?= draw_line(60) ?>
            <?= draw_line(105) ?>
            <?= draw_line(150) ?>

            <div id="map-scanner"
                 style="background: conic-gradient(#0000 300deg, #0f05 345deg, #0f05 358deg, #0000)"
                 class="w-full h-full
                        animate-spin-30s
                        absolute inset-0
                        border-2 border-transparent rounded-full">
            </div>

            <div class="w-full h-full m-auto absolute inset-0
                        border-4 border-green-300 rounded-full"></div>
            <?= draw_circle(75) ?>
            <?= draw_circle(55) ?>
            <?= draw_circle(35) ?>
            <?= draw_circle(15) ?>
    </body>
</html>
