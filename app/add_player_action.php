<?php
include 'db.php';

$name = $_POST['name'];
$mmr = $_POST['mmr'];

$sql = "INSERT INTO players (name, mmr) VALUES ('$name', '$mmr')";

if ($conn->query($sql) === TRUE) {
    header("Location: index.php");
} else {
    echo "Erreur : " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
