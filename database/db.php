<?php
$conn = new mysqli("localhost", "root", "", "jobapplication");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>