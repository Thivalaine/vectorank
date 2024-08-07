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

// Récupérer les MMR et les séries de victoires actuelles des joueurs
$result1 = $conn->query("SELECT mmr, current_win_streak, best_win_streak FROM players WHERE id = $player1");
$result2 = $conn->query("SELECT mmr, current_win_streak, best_win_streak FROM players WHERE id = $player2");

if ($result1->num_rows > 0 && $result2->num_rows > 0) {
    $data1 = $result1->fetch_assoc();
    $data2 = $result2->fetch_assoc();

    $old_mmr1 = $data1['mmr'];
    $old_mmr2 = $data2['mmr'];
    $current_win_streak1 = $data1['current_win_streak'];
    $current_win_streak2 = $data2['current_win_streak'];
} else {
    die("Erreur: Joueur non trouvé.");
}

// Calculer les autres valeurs nécessaires
$observed1 = $score1 > $score2 ? 1 : 0;
$observed2 = $score1 < $score2 ? 1 : 0;

// Calculer les probabilités
$probability1 = 1 / (1 + pow(10, ($old_mmr2 - $old_mmr1) / 400));
$probability2 = 1 / (1 + pow(10, ($old_mmr1 - $old_mmr2) / 400));

// Valeurs attendues basées sur les probabilités
$expected1 = 0.5;
$expected2 = 0.5;

// Marge de victoire
$victory_margin = abs($score1 - $score2);

// Facteur de victoire en fonction de la marge de victoire
$victory_factor = 1 + ($victory_margin / 10);

// Points supplémentaires en fonction de l'écart de score
$extra_points = $victory_margin;

// Fonction pour calculer les points bonus basés sur la série de victoires
function calculateBonusPoints($current_streak) {
    if ($current_streak < 5) {
        return 1; // +1 point pour une série de victoires < 5
    } elseif ($current_streak < 10) {
        return 2; // +2 points pour une série de victoires < 10
    } elseif ($current_streak < 15) {
        return 3; // +3 points pour une série de victoires < 15
    } elseif ($current_streak < 20) {
        return 4; // +4 points pour une série de victoires < 20
    } else {
        return 5; // +5 points pour une série de victoires > 20
    }
}

// Calculer les nouveaux MMR avec l'arrondi supérieur et asymétrique
if ($score1 > $score2) {
    $bonusPoints1 = calculateBonusPoints($current_win_streak1);
    $bonusPoints2 = 0; // Pas de points bonus pour le perdant

    $new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $probability1) * $victory_factor + $extra_points + $bonusPoints1);
    $new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $probability2) * $victory_factor - $extra_points);
    
    // Mettre à jour la série de victoires pour le joueur 1
    $new_current_win_streak1 = $current_win_streak1 + 1;
    $new_current_win_streak2 = 0; // Réinitialiser la série de victoires pour le joueur 2
} else {
    $bonusPoints1 = 0; // Pas de points bonus pour le perdant
    $bonusPoints2 = calculateBonusPoints($current_win_streak2);

    $new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $probability1) * $victory_factor - $extra_points);
    $new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $probability2) * $victory_factor + $extra_points + $bonusPoints2);
    
    // Mettre à jour la série de victoires pour le joueur 2
    $new_current_win_streak2 = $current_win_streak2 + 1;
    $new_current_win_streak1 = 0; // Réinitialiser la série de victoires pour le joueur 1
}

// Points gagnés ou perdus (sans les bonus)
$points1 = $new_mmr1 - $old_mmr1;
$points2 = $new_mmr2 - $old_mmr2;

// Ajouter les points bonus au nouveau MMR
if ($score1 > $score2) {
    $new_mmr1 += $bonusPoints1; // Ajout des points bonus pour le joueur 1
} else {
    $new_mmr2 += $bonusPoints2; // Ajout des points bonus pour le joueur 2
}

// Calculer la différence ELO
$elo_difference = abs($old_mmr1 - $old_mmr2);

// Déterminer les nouveaux rangs en fonction des nouveaux MMR
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

$new_rank1 = getRank($new_mmr1);
$new_rank2 = getRank($new_mmr2);

// Définir le fuseau horaire sur Paris
date_default_timezone_set('Europe/Paris');

// Formater la date au format français
$match_date = (new DateTime())->format('Y-m-d H:i:s');

// Insérer le match dans la base de données
$sql = "INSERT INTO matches (player1, player2, score1, score2, observed1, observed2, expected1, expected2, victory_margin, victory_factor, probability1, probability2, old_mmr1, old_mmr2, new_mmr1, new_mmr2, elo_difference, match_date, points1, points2, win_streak_bonus1, win_streak_bonus2)
VALUES ('$player1', '$player2', '$score1', '$score2', '$observed1', '$observed2', '$expected1', '$expected2', '$victory_margin', '$victory_factor', '$probability1', '$probability2', '$old_mmr1', '$old_mmr2', '$new_mmr1', '$new_mmr2', '$elo_difference', '$match_date', '$points1', '$points2', '$bonusPoints1', '$bonusPoints2')";

if ($conn->query($sql) === TRUE) {
    // Mettre à jour les MMR, les rangs et les séries de victoires des joueurs
    $conn->query("UPDATE players SET mmr = $new_mmr1, rank = '$new_rank1', current_win_streak = $new_current_win_streak1, best_win_streak = GREATEST(best_win_streak, $new_current_win_streak1) WHERE id = $player1");
    $conn->query("UPDATE players SET mmr = $new_mmr2, rank = '$new_rank2', current_win_streak = $new_current_win_streak2, best_win_streak = GREATEST(best_win_streak, $new_current_win_streak2) WHERE id = $player2");

    header("Location: index.php");
} else {
    echo "Erreur : " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
