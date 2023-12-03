<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
  http_response_code(405);
  exit(-1);
}
session_start();
session_unset();

header("HX-Redirect: index.php");
