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

    if(isset($_SERVER["HTTP-HX-Request"])) {
        header("HX-Redirect: /index.php");
    } else {
        header("Location: /index.php");
    }
    exit(-1);
}
