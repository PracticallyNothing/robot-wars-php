<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
  http_response_code(405);
  exit(-1);
}

function show_login_error(string $error_text) {
  echo '<p class="col-span-2 mt-2 text-red-700 text-center" id="login-error" hx-swap-oob="true">' .
    $error_text .
    "</p>";
  exit(-1);
}

if (!isset($_POST["login-submit"])) {
  show_login_error("Incorrect format!");
}

$username = trim((string) $_POST["username"]);
$password = (string) $_POST["password"];

if (strlen($username) == 0) {
  show_login_error("Username must not be empty!");
} elseif (strlen($password) == 0) {
  show_login_error("Password must not be empty!");
}

include_once "../connection.php";

$stmt = $conn->prepare("SELECT PASSWORD_HASH from USERS where USERNAME = ?");
$result = $stmt->execute([$username]);

if (!$result) {
  show_login_error("Unknown error while logging in!");
}

$password_hash = $stmt->get_result()->fetch_column();

if (!$password_hash || !password_verify($password, $password_hash)) {
  show_login_error("Incorrect username or password!");
}

session_start();
$_SESSION["USERNAME"] = $username;
$_SESSION["RANK"] = 1;

header("HX-Redirect: lobby.php");
