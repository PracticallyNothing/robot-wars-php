<?php
session_start();

if (!isset($_SESSION["USERNAME"])) {
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
    <div class="flex flex-row">
        <h1 class="text-5xl font-semibold text-center mb-4 flex-grow">
            Robot Wars Lobby
        </h1>
        <button hx-post="/user/do_logout.php">
            Log Out
        </button>
    </div>

    <hr class="w-full" />
</body>

</html>