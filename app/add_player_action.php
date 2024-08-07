<?php
include 'db.php';

// Fonction pour déterminer le rang en fonction du MMR
function getRank($mmr) {
    if ($mmr >= 4000) {
        return "Challenger";
    } elseif ($mmr >= 3000) {
        return "Grandmaster";
    } elseif ($mmr >= 2500) {
        return "Master";
    } elseif ($mmr >= 2000) {
        return "Diamond";
    } elseif ($mmr >= 1750) {
        return "Emerald";
    } elseif ($mmr >= 1500) {
        return "Platinum";
    } elseif ($mmr >= 1250) {
        return "Gold";
    } elseif ($mmr >= 1000) {
        return "Silver";
    } elseif ($mmr >= 500) {
        return "Bronze";
    } else {
        return "Iron";
    }
}

// Récupérer les données du formulaire
$name = $_POST['name'];
$initialMmr = 1000; // MMR initial fixé à 1000

// Déterminer le rang basé sur le MMR
$rank = getRank($initialMmr);

// Insertion du joueur dans la base de données
$sql = "INSERT INTO players (name, mmr, rank) VALUES ('$name', '$initialMmr', '$rank')";

if ($conn->query($sql) === TRUE) {
    header("Location: index.php");
} else {
    echo "Erreur : " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
