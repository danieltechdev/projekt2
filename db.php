<?php
// Database credentials
$host = 'localhost:3305';
$dbname = 'sklep_internetowy';
$username = 'root';
$password = 'okno'; // adjust based on your MySQL setup

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
?>