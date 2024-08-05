<?php
$servername = "db";
$username = "user";
$password = "user_password";
$dbname = "elo_system";

// Créer une connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
