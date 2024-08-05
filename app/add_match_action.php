<?php
include 'db.php';

// Récupérer les données du formulaire
$player1 = $_POST['player1'];
$player2 = $_POST['player2'];
$score1 = $_POST['score1'];
$score2 = $_POST['score2'];

// Vérifier que les joueurs sont différents
if ($player1 == $player2) {
    die("Les joueurs doivent être différents.");
}

// Récupérer les MMR actuels des joueurs
$result1 = $conn->query("SELECT mmr FROM players WHERE id = $player1");
$result2 = $conn->query("SELECT mmr FROM players WHERE id = $player2");

if ($result1->num_rows > 0 && $result2->num_rows > 0) {
    $old_mmr1 = $result1->fetch_assoc()['mmr'];
    $old_mmr2 = $result2->fetch_assoc()['mmr'];
} else {
    die("Erreur: Joueur non trouvé.");
}

// Calculer les autres valeurs nécessaires
$observed1 = $score1 > $score2 ? 1 : 0;
$observed2 = $score1 < $score2 ? 1 : 0;
$expected1 = 0.5; // Vous pouvez remplacer cela par votre propre logique
$expected2 = 0.5; // Vous pouvez remplacer cela par votre propre logique
$victory_margin = abs($score1 - $score2);
$victory_factor = 1.1; // Vous pouvez remplacer cela par votre propre logique
$probability1 = 0.5; // Vous pouvez remplacer cela par votre propre logique
$probability2 = 0.5; // Vous pouvez remplacer cela par votre propre logique
$elo_difference = abs($old_mmr1 - $old_mmr2);

// Points supplémentaires en fonction de l'écart de score
$extra_points = $victory_margin;

// Calculer les nouveaux MMR avec l'arrondi supérieur et asymétrique
if ($score1 > $score2) {
    $new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $expected1) * $victory_factor + $extra_points);
    $new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $expected2) * $victory_factor - $extra_points);
} else {
    $new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $expected1) * $victory_factor - $extra_points);
    $new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $expected2) * $victory_factor + $extra_points);
}

// Déterminer les nouveaux rangs en fonction des nouveaux MMR
function getRank($mmr) {
    if ($mmr >= 4000) {
        return "Challenger";
    } elseif ($mmr >= 3000) {
        return "Grandmaster";
    } elseif ($mmr >= 2750) {
        return "Master";
    } elseif ($mmr >= 2500) {
        return "Diamond";
    } elseif ($mmr >= 2250) {
        return "Emerald";
    } elseif ($mmr >= 2000) {
        return "Platinum";
    } elseif ($mmr >= 1500) {
        return "Gold";
    } elseif ($mmr >= 1000) {
        return "Silver";
    } elseif ($mmr >= 500) {
        return "Bronze";
    } else {
        return "Iron";
    }
}

$new_rank1 = getRank($new_mmr1);
$new_rank2 = getRank($new_mmr2);

// Insérer le match dans la base de données
$sql = "INSERT INTO matches (player1, player2, score1, score2, observed1, observed2, expected1, expected2, victory_margin, victory_factor, probability1, probability2, old_mmr1, old_mmr2, new_mmr1, new_mmr2, elo_difference)
VALUES ('$player1', '$player2', '$score1', '$score2', '$observed1', '$observed2', '$expected1', '$expected2', '$victory_margin', '$victory_factor', '$probability1', '$probability2', '$old_mmr1', '$old_mmr2', '$new_mmr1', '$new_mmr2', '$elo_difference')";

if ($conn->query($sql) === TRUE) {
    // Mettre à jour les MMR et les rangs des joueurs
    $conn->query("UPDATE players SET mmr = $new_mmr1, rank = '$new_rank1' WHERE id = $player1");
    $conn->query("UPDATE players SET mmr = $new_mmr2, rank = '$new_rank2' WHERE id = $player2");

    header("Location: index.php");
} else {
    echo "Erreur : " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
