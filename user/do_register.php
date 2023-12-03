<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    http_response_code(405);
    exit(-1);
}

function show_register_error(string $error_text)
{
    echo '<p class="col-span-2 mt-2 text-red-700 text-center" id="register-error" hx-swap-oob="true">' . $error_text . '</p>';
    exit(-1);
}

if (!isset($_POST["register-submit"]))
    show_register_error("Incorrect format!");

$username = trim((string)$_POST["username"]);
$password = (string)$_POST["password"];
$email = trim((string)$_POST["email"]);

if (strlen($username) == 0)
    show_register_error("Username must not be empty!");
else if (strlen($password) == 0)
    show_register_error("Password must not be empty!");
else if (strlen($email) == 0)
    show_register_error("Email must not be empty!");

include_once "../connection.php";

$stmt = $conn->prepare(
    "INSERT into USERS(USERNAME, PASSWORD_HASH, EMAIL, RANK) values (?, ?, ?, 1)"
);

try {
    $result = $stmt->execute([
        $username,
        password_hash($password, PASSWORD_DEFAULT),
        $email
    ]);
} catch (\mysqli_sql_exception $e) {
    if (strstr($e->getMessage(), "USERNAME")) {
        show_register_error("Username already exists!");
        exit(-1);
    }
}

session_start();
$_SESSION["USERNAME"] = $username;
$_SESSION["RANK"] = 1;

header("HX-Redirect: lobby.php");
