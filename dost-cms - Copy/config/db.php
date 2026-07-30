<?php
$host = "localhost";
$user = "root";
$pass = ""; // default is blank in XAMPP
$db = "dost_cms";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>