<?php

function post_only() {
    if($_SERVER["REQUEST_METHOD"] != "POST") {
        http_response_code(405);
        exit(-1);
    }
}

function protected_endpoint() {
    session_start();

    if(isset($_SESSION["username"]))
        return;

    session_unset();

    if(isset($_SERVER["HTTP_HX_REQUEST"]))
        header("HX-Redirect: /lobby.php");
    elseif(isset($_SERVER["HTTP_JQUERY_REQUEST"]))
        http_response_code(401);
    else
        header("Location: /lobby.php");

    exit(-1);
}

// Only allow following code to complete if the user has started a game.
function game_only_endpoint() {
    // echo "<pre>" . print_r($_SERVER, 1) . "</pre>";
    // http_response_code(403);
    // exit(-1);

    protected_endpoint();

    if(isset($_SESSION["gameid"]))
        return;

    if(isset($_SERVER["HTTP-HX-Request"]))
        header("HX-Redirect: /lobby.php");
    elseif(isset($_SERVER["HTTP_JQUERY_REQUEST"]))
        http_response_code(403);
    else
        header("Location: /lobby.php");

    exit(-1);
}

function exec_sql(mysqli $conn, string $sql, array $params = []) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
}

function exec_sql_scalar(mysqli $conn, string $sql, array $params = []) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->get_result()->fetch_column();
}

function exec_sql_first(mysqli $conn, string $sql, array $params = []) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->get_result()->fetch_assoc();
}

function exec_sql_all(mysqli $conn, string $sql, array $params = []) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $result = $stmt->get_result();
    $rows = array();

    while($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    return $rows;
}

define("DT_FORMAT", "Y-m-d H:i:s");
