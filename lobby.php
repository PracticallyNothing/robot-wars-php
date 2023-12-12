<?php
session_start();

if (!isset($_SESSION["username"])) {
  header("Location: index.php");
  return;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/htmx/1.9.9/htmx.min.js" integrity="sha512-FSS62yxqCRMCtm1J+ddRwX8DuCRVt/WMpihCo06P+Je5AG4CV9yoLX53zHaOB5w/eZdG7d/QAyUEJTnHZHrWKg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://unpkg.com/htmx.org/dist/ext/debug.js"></script>

    <title>Robot Wars - Lobby</title>
</head>

<body class="h-screen flex flex-col">
    <div class="flex flex-row mt-2">
        <h1 class="text-5xl font-semibold text-center mb-4 flex-grow">Robot Wars Lobby</h1>

        <button hx-post="/user/do_logout.php"
                hx-confirm="Are you sure?"
                class="text-base bg-blue-600 text-white rounded-xl px-3 m-3 hover:bg-blue-300 border border-cyan-500">
            Log Out
        </button>
    </div>

    <hr class="my-2" />

    <div class="flex flex-col justify-center items-center">
        <div>
            <p class="text-base w-min pointer-events-none">Hello,</p>
            <p class="text-3xl font-extrabold -mt-2 w-min">
                <?= $_SESSION["username"] ?>
            </p>
        </div>
    </div>

    <hr class="my-2" />

    <div class="flex flex-row h-full">
        <a hx-post="/game/start_pve.php"
           hx-indicator="#start-pve-text"
           class="group h-full w-full transition-all flex flex-col items-center justify-center hover:bg-slate-300">
            <p id="start-pve-text"
               class="text-xl transition-all group-hover:text-3xl [&.htmx-request]:animation-spin">
                PvE Singleplayer
                <?php if (isset($_SESSION["gameid"])) { ?>
                    <p class="italic">You already have a game started!</p>
                <?php } ?>
            </p>
        </a>
        <a href="#" class="group h-full w-full transition-all flex items-center justify-center hover:bg-slate-300">
            <p class="text-xl transition-all group-hover:text-3xl">
                PvE Co-op
            </p>
        </a>
        <div class="h-full w-full flex flex-col items-center">
            <h1 class="text-xl">PvP Lobbies</h1>
            <hr class="w-full" />
        </div>
    </div>
</body>

</html>
