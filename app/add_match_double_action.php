<?php
include 'db.php';

// Récupérer les données du formulaire pour plusieurs matchs
$team1_player1_array = $_POST['team1_player1'];
$team1_player2_array = $_POST['team1_player2'];
$team2_player1_array = $_POST['team2_player1'];
$team2_player2_array = $_POST['team2_player2'];
$team1_score_array = $_POST['team1_score'];
$team2_score_array = $_POST['team2_score'];
$lastModified_array = $_POST['lastModified']; // Récupérer les dates de modification

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

for ($i = 0; $i < count($team1_player1_array); $i++) {
    $team1_player1 = $team1_player1_array[$i];
    $team1_player2 = $team1_player2_array[$i];
    $team2_player1 = $team2_player1_array[$i];
    $team2_player2 = $team2_player2_array[$i];
    $team1_score = $team1_score_array[$i];
    $team2_score = $team2_score_array[$i];
    $lastModified = $lastModified_array[$i]; // Date de modification du match

    // Récupérer les MMR et les séries de victoires actuelles des joueurs des deux équipes
    $results_team1_player1 = $conn->query("SELECT * FROM players WHERE id = $team1_player1");
    $results_team1_player2 = $conn->query("SELECT * FROM players WHERE id = $team1_player2");
    $results_team2_player1 = $conn->query("SELECT * FROM players WHERE id = $team2_player1");
    $results_team2_player2 = $conn->query("SELECT * FROM players WHERE id = $team2_player2");

    if ($results_team1_player1->num_rows > 0 && $results_team1_player2->num_rows > 0 &&
        $results_team2_player1->num_rows > 0 && $results_team2_player2->num_rows > 0) {

        $data_team1_player1 = $results_team1_player1->fetch_assoc();
        $data_team1_player2 = $results_team1_player2->fetch_assoc();
        $data_team2_player1 = $results_team2_player1->fetch_assoc();
        $data_team2_player2 = $results_team2_player2->fetch_assoc();

        // Calculer les anciens MMR et les séries de victoires
        $old_mmr_team1_player1 = $data_team1_player1['mmr'];
        $old_mmr_team1_player2 = $data_team1_player2['mmr'];
        $old_mmr_team2_player1 = $data_team2_player1['mmr'];
        $old_mmr_team2_player2 = $data_team2_player2['mmr'];
        
        $current_win_streak_team1_player1 = $data_team1_player1['current_win_streak'];
        $current_win_streak_team1_player2 = $data_team1_player2['current_win_streak'];
        $current_win_streak_team2_player1 = $data_team2_player1['current_win_streak'];
        $current_win_streak_team2_player2 = $data_team2_player2['current_win_streak'];

        $current_win_streak_team1 = max($current_win_streak_team1_player1, $current_win_streak_team1_player2);
        $current_win_streak_team2 = max($current_win_streak_team2_player1, $current_win_streak_team2_player2);
        
    } else {
        die("Erreur: Un ou plusieurs joueurs non trouvés.");
    }

    // Calculer les résultats
    $observed_team1 = $team1_score > $team2_score ? 1 : 0;
    $observed_team2 = $team1_score < $team2_score ? 1 : 0;
    
    // Calculer les probabilités
    $probability_team1 = 1 / (1 + pow(10, ($old_mmr_team2_player1 + $old_mmr_team2_player2 - $old_mmr_team1_player1 - $old_mmr_team1_player2) / 400));
    $probability_team2 = 1 / (1 + pow(10, ($old_mmr_team1_player1 + $old_mmr_team1_player2 - $old_mmr_team2_player1 - $old_mmr_team2_player2) / 400));
    
    // Marge de victoire
    $victory_margin = abs($team1_score - $team2_score);
    
    // Facteur de victoire en fonction de la marge de victoire
    $victory_factor = 1 + ($victory_margin / 10);
    
    // Différence d'ELO
    $elo_difference = abs(($old_mmr_team1_player1 + $old_mmr_team1_player2) / 2 - ($old_mmr_team2_player1 + $old_mmr_team2_player2) / 2);
    
    // Calcul du coefficient en fonction de la différence d'ELO
    $elo_difference_factor = log(1 + $elo_difference / 400);
    
    // Calculer les nouveaux MMR pour chaque joueur
    if ($team1_score > $team2_score) {
        $bonusPoints1 = calculateBonusPoints($current_win_streak_team1);
        $bonusPoints2 = 0;
    
        $new_mmr_team1_player1 = ceil($old_mmr_team1_player1 + 10 * ($observed_team1 - $probability_team1) * $victory_factor * $elo_difference_factor + $victory_margin + $bonusPoints1);
        $new_mmr_team1_player2 = ceil($old_mmr_team1_player2 + 10 * ($observed_team1 - $probability_team1) * $victory_factor * $elo_difference_factor + $victory_margin + $bonusPoints1);
    
        $new_mmr_team2_player1 = ceil($old_mmr_team2_player1 + 10 * ($observed_team2 - $probability_team2) * $victory_factor * $elo_difference_factor - $victory_margin);
        $new_mmr_team2_player2 = ceil($old_mmr_team2_player2 + 10 * ($observed_team2 - $probability_team2) * $victory_factor * $elo_difference_factor - $victory_margin);
    
        // Mettre à jour la série de victoires
        $new_current_win_streak_team1 = $current_win_streak_team1 + 1;
        $new_current_win_streak_team2 = 0;
    } else {
        $bonusPoints1 = 0;
        $bonusPoints2 = calculateBonusPoints($current_win_streak_team2);
    
        $new_mmr_team1_player1 = ceil($old_mmr_team1_player1 + 10 * ($observed_team1 - $probability_team1) * $victory_factor * $elo_difference_factor - $victory_margin);
        $new_mmr_team1_player2 = ceil($old_mmr_team1_player2 + 10 * ($observed_team1 - $probability_team1) * $victory_factor * $elo_difference_factor - $victory_margin);
    
        $new_mmr_team2_player1 = ceil($old_mmr_team2_player1 + 10 * ($observed_team2 - $probability_team2) * $victory_factor * $elo_difference_factor + $victory_margin + $bonusPoints2);
        $new_mmr_team2_player2 = ceil($old_mmr_team2_player2 + 10 * ($observed_team2 - $probability_team2) * $victory_factor * $elo_difference_factor + $victory_margin + $bonusPoints2);
    
        // Mettre à jour la série de victoires
        $new_current_win_streak_team1 = 0;
        $new_current_win_streak_team2 = $current_win_streak_team2 + 1;
    }
    
    // Points gagnés ou perdus (sans les bonus)
    $points_team1 = ($new_mmr_team1_player1 + $new_mmr_team1_player2) - ($old_mmr_team1_player1 + $old_mmr_team1_player2);
    $points_team2 = ($new_mmr_team2_player1 + $new_mmr_team2_player2) - ($old_mmr_team2_player1 + $old_mmr_team2_player2);
    
    // Ajouter les points bonus au nouveau MMR
    $new_mmr_team1_player1 += $bonusPoints1;
    $new_mmr_team1_player2 += $bonusPoints1;
    $new_mmr_team2_player1 += $bonusPoints2;
    $new_mmr_team2_player2 += $bonusPoints2;
    
    // Déterminer les nouveaux rangs
    $new_rank_team1_player1 = getRank($new_mmr_team1_player1);
    $new_rank_team1_player2 = getRank($new_mmr_team1_player2);
    $new_rank_team2_player1 = getRank($new_mmr_team2_player1);
    $new_rank_team2_player2 = getRank($new_mmr_team2_player2);
    
    // Insérer le match dans la base de données
    $sql = "INSERT INTO duo_matches (team1_player1, team1_player2, team2_player1, team2_player2, team1_score, team2_score, observed_team1, observed_team2, victory_margin, victory_factor, probability_team1, probability_team2, old_mmr_team1_player1, old_mmr_team1_player2, old_mmr_team2_player1, old_mmr_team2_player2, new_mmr_team1_player1, new_mmr_team1_player2, new_mmr_team2_player1, new_mmr_team2_player2, elo_difference, match_date, points_team1, points_team2, win_streak_bonus_team1, win_streak_bonus_team2)
    VALUES ('$team1_player1', '$team1_player2', '$team2_player1', '$team2_player2', '$team1_score', '$team2_score', '$observed_team1', '$observed_team2', '$victory_margin', '$victory_factor', '$probability_team1', '$probability_team2', '$old_mmr_team1_player1', '$old_mmr_team1_player2', '$old_mmr_team2_player1', '$old_mmr_team2_player2', '$new_mmr_team1_player1', '$new_mmr_team1_player2', '$new_mmr_team2_player1', '$new_mmr_team2_player2', '$elo_difference', '$lastModified', '$points_team1', '$points_team2', '$bonusPoints1', '$bonusPoints2')";

    if ($conn->query($sql) === TRUE) {
        // Mettre à jour les MMR et rangs des joueurs de la première équipe
        $updateTeam1Player1 = "UPDATE players SET mmr = $new_mmr_team1_player1, old_mmr = $old_mmr_team1_player1, rank = '$new_rank_team1_player1', current_win_streak = $new_current_win_streak_team1, best_win_streak = GREATEST(best_win_streak, $new_current_win_streak_team1), best_mmr = GREATEST(best_mmr, $new_mmr_team1_player1) WHERE id = $team1_player1";
        $conn->query($updateTeam1Player1);

        $updateTeam1Player2 = "UPDATE players SET mmr = $new_mmr_team1_player2, old_mmr = $old_mmr_team1_player2, rank = '$new_rank_team1_player2', current_win_streak = $new_current_win_streak_team1, best_win_streak = GREATEST(best_win_streak, $new_current_win_streak_team1), best_mmr = GREATEST(best_mmr, $new_mmr_team1_player2) WHERE id = $team1_player2";
        $conn->query($updateTeam1Player2);

        // Mettre à jour les MMR et rangs des joueurs de la deuxième équipe
        $updateTeam2Player1 = "UPDATE players SET mmr = $new_mmr_team2_player1, old_mmr = $old_mmr_team2_player1, rank = '$new_rank_team2_player1', current_win_streak = $new_current_win_streak_team2, best_win_streak = GREATEST(best_win_streak, $new_current_win_streak_team2), best_mmr = GREATEST(best_mmr, $new_mmr_team2_player1) WHERE id = $team2_player1";
        $conn->query($updateTeam2Player1);

        $updateTeam2Player2 = "UPDATE players SET mmr = $new_mmr_team2_player2, old_mmr = $old_mmr_team2_player2, rank = '$new_rank_team2_player2', current_win_streak = $new_current_win_streak_team2, best_win_streak = GREATEST(best_win_streak, $new_current_win_streak_team2), best_mmr = GREATEST(best_mmr, $new_mmr_team2_player2) WHERE id = $team2_player2";
        $conn->query($updateTeam2Player2);
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

// Redirection vers la page de confirmation
header("Location: match_confirmation.php");
exit();
?>
