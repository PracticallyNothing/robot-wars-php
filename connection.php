<?php
try {
    $conn = mysqli_connect("localhost", "root", "", "robot_wars_db");
    if (!$conn) {
        echo "=-----------+=[ Нямаш база ]=+-----------=";
    }
} catch (\Throwable $th) {
    echo "=-----------+=[ Нямаш база ]=+-----------=";
}
