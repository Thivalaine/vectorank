<?php
include 'db.php';

// Récupérer les données du formulaire pour plusieurs matchs
$player1_array = $_POST['player1'];
$player2_array = $_POST['player2'];
$score1_array = $_POST['score1'];
$score2_array = $_POST['score2'];

// Fonction pour calculer les points bonus basés sur la série de victoires
function calculateBonusPoints($current_streak) {
    if ($current_streak < 5) {
        return 1;
    } elseif ($current_streak < 10) {
        return 2;
    } elseif ($current_streak < 15) {
        return 3;
    } elseif ($current_streak < 20) {
        return 4;
    } else {
        return 5;
    }
}

// Déterminer les nouveaux rangs
function getRank($mmr) {
    if ($mmr >= 4000) return "Challenger";
    if ($mmr >= 3000) return "Grandmaster";
    if ($mmr >= 2500) return "Master";
    if ($mmr >= 2000) return "Diamond";
    if ($mmr >= 1750) return "Emerald";
    if ($mmr >= 1500) return "Platinum";
    if ($mmr >= 1250) return "Gold";
    if ($mmr >= 1000) return "Silver";
    if ($mmr >= 500) return "Bronze";
    return "Iron";
}

// Définir le fuseau horaire
date_default_timezone_set('Europe/Paris');

// Créer un objet DateTime pour l'heure actuelle
$datetime = new DateTime();

for ($i = 0; $i < count($player1_array); $i++) {
    $player1 = $player1_array[$i];
    $player2 = $player2_array[$i];
    $score1 = $score1_array[$i];
    $score2 = $score2_array[$i];

    // Vérifier que les joueurs sont différents
    if ($player1 == $player2) {
        die("Les joueurs doivent être différents.");
    }

    // Récupérer les MMR et les séries de victoires actuelles des joueurs
    $result1 = $conn->query("SELECT * FROM players WHERE id = $player1");
    $result2 = $conn->query("SELECT * FROM players WHERE id = $player2");

    if ($result1->num_rows > 0 && $result2->num_rows > 0) {
        $data1 = $result1->fetch_assoc();
        $data2 = $result2->fetch_assoc();
    
        $old_mmr1 = $data1['mmr'];
        $old_mmr2 = $data2['mmr'];
        $current_win_streak1 = $data1['current_win_streak'];
        $current_win_streak2 = $data2['current_win_streak'];
        $old_ranking1 = $data1['new_ranking'];
        $old_ranking2 = $data2['new_ranking'];
    } else {
        die("Erreur: Joueur non trouvé.");
    }
    
    // Calculer les résultats
    $observed1 = $score1 > $score2 ? 1 : 0;
    $observed2 = $score1 < $score2 ? 1 : 0;
    
    // Calculer les probabilités
    $probability1 = 1 / (1 + pow(10, ($old_mmr2 - $old_mmr1) / 400));
    $probability2 = 1 / (1 + pow(10, ($old_mmr1 - $old_mmr2) / 400));
    
    // Marge de victoire
    $victory_margin = abs($score1 - $score2);
    
    // Facteur de victoire en fonction de la marge de victoire
    $victory_factor = 1 + ($victory_margin / 10);
    
    // Différence d'ELO
    $elo_difference = abs($old_mmr1 - $old_mmr2);
    
    // Calcul du coefficient en fonction de la différence d'ELO
    $elo_difference_factor = log(1 + $elo_difference / 400);
    
    // Calculer les nouveaux MMR
    if ($score1 > $score2) {
        $bonusPoints1 = calculateBonusPoints($current_win_streak1);
        $bonusPoints2 = 0;
    
        $new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $probability1) * $victory_factor * $elo_difference_factor + $victory_margin + $bonusPoints1);
        $new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $probability2) * $victory_factor * $elo_difference_factor - $victory_margin);
    
        // Mettre à jour la série de victoires
        $new_current_win_streak1 = $current_win_streak1 + 1;
        $new_current_win_streak2 = 0;
    } else {
        $bonusPoints1 = 0;
        $bonusPoints2 = calculateBonusPoints($current_win_streak2);
    
        $new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $probability1) * $victory_factor * $elo_difference_factor - $victory_margin);
        $new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $probability2) * $victory_factor * $elo_difference_factor + $victory_margin + $bonusPoints2);
    
        // Mettre à jour la série de victoires
        $new_current_win_streak1 = 0;
        $new_current_win_streak2 = $current_win_streak2 + 1;
    }
    
    // Points gagnés ou perdus (sans les bonus)
    $points1 = $new_mmr1 - $old_mmr1;
    $points2 = $new_mmr2 - $old_mmr2;
    
    // Ajouter les points bonus au nouveau MMR
    $new_mmr1 += $bonusPoints1;
    $new_mmr2 += $bonusPoints2;
    
    // Déterminer les nouveaux rangs
    $new_rank1 = getRank($new_mmr1);
    $new_rank2 = getRank($new_mmr2);    

    // On ajoute 1s car la requête de listage des matchs est basé sur la date du match et on ajoute 1s pour différencier les matchs ajoutés de manière multiples
    if ($i > 0) {
        $datetime->modify('+1 second');
    }

    // Formater la date pour l'insertion dans la base de données
    $match_date = $datetime->format('Y-m-d H:i:s');

    // Insérer le match dans la base de données
    $sql = "INSERT INTO matches (player1, player2, score1, score2, observed1, observed2, victory_margin, victory_factor, probability1, probability2, old_mmr1, old_mmr2, new_mmr1, new_mmr2, elo_difference, match_date, points1, points2, win_streak_bonus1, win_streak_bonus2)
    VALUES ('$player1', '$player2', '$score1', '$score2', '$observed1', '$observed2', '$victory_margin', '$victory_factor', '$probability1', '$probability2', '$old_mmr1', '$old_mmr2', '$new_mmr1', '$new_mmr2', '$elo_difference', '$match_date', '$points1', '$points2', '$bonusPoints1', '$bonusPoints2')";

    if ($conn->query($sql) === TRUE) {
        // Mettre à jour les MMR et rangs des joueurs
        $updatePlayer1 = "UPDATE players SET mmr = $new_mmr1, old_mmr = $old_mmr1, rank = '$new_rank1', old_ranking = $old_ranking1, current_win_streak = $new_current_win_streak1, best_win_streak = GREATEST(best_win_streak, $new_current_win_streak1), best_mmr = GREATEST(best_mmr, $new_mmr1) WHERE id = $player1";
        $conn->query($updatePlayer1);

        $updatePlayer2 = "UPDATE players SET mmr = $new_mmr2, old_mmr = $old_mmr2, rank = '$new_rank2', old_ranking = $old_ranking2, current_win_streak = $new_current_win_streak2, best_win_streak = GREATEST(best_win_streak, $new_current_win_streak2), best_mmr = GREATEST(best_mmr, $new_mmr2) WHERE id = $player2";
        $conn->query($updatePlayer2);
    } else {
        echo "Erreur : " . $sql . "<br>" . $conn->error;
    }
}

// Mettre à jour le classement des joueurs
$rankingQuery = "SELECT id FROM players ORDER BY mmr DESC";
$rankingResult = $conn->query($rankingQuery);

$ranking = 1;
while ($player = $rankingResult->fetch_assoc()) {
    $playerId = $player['id'];
    $updateRanking = "UPDATE players SET new_ranking = $ranking WHERE id = $playerId";
    $conn->query($updateRanking);
    $ranking++;
}

$conn->close();

header("Location: index.php");
exit();
?>
