<?php
include 'db.php';

// Récupérer les données du formulaire
$player1 = $_POST['player1'];
$player2 = $_POST['player2'];
$id = $_POST['id'];
$score1 = $_POST['score1'];
$score2 = $_POST['score2'];
$tournament_id = isset($_POST['tournament_id']) ? $_POST['tournament_id'] : null; // Récupérer l'ID du tournoi

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
} else {
    die("Erreur: Joueur non trouvé.");
}

// Calculer les résultats
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

// Calculer les nouveaux MMR
if ($score1 > $score2) {
    $bonusPoints1 = calculateBonusPoints($current_win_streak1);
    $bonusPoints2 = 0;

    $new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $probability1) * $victory_factor + $victory_margin + $bonusPoints1);
    $new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $probability2) * $victory_factor - $victory_margin);
    
    // Mettre à jour la série de victoires
    $new_current_win_streak1 = $current_win_streak1 + 1;
    $new_current_win_streak2 = 0;
    $winner = $player1; // Le joueur 1 est le gagnant
    $loser = $player2; // Le joueur 2 est le perdant
} else {
    $bonusPoints1 = 0;
    $bonusPoints2 = calculateBonusPoints($current_win_streak2);

    $new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $probability1) * $victory_factor - $victory_margin);
    $new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $probability2) * $victory_factor + $victory_margin + $bonusPoints2);
    
    // Mettre à jour la série de victoires
    $new_current_win_streak1 = 0;
    $new_current_win_streak2 = $current_win_streak2 + 1;
    $winner = $player2; // Le joueur 2 est le gagnant
    $loser = $player1; // Le joueur 1 est le perdant
}

// Points gagnés ou perdus (sans les bonus)
$points1 = $new_mmr1 - $old_mmr1;
$points2 = $new_mmr2 - $old_mmr2;

// Ajouter les points bonus au nouveau MMR
$new_mmr1 += $bonusPoints1;
$new_mmr2 += $bonusPoints2;

// Calculer la différence ELO
$elo_difference = abs($old_mmr1 - $old_mmr2);

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
}

$new_rank1 = getRank($new_mmr1);
$new_rank2 = getRank($new_mmr2);

// Définir le fuseau horaire
date_default_timezone_set('Europe/Paris');

$match_date = (new DateTime())->format('Y-m-d H:i:s');

// Vérifier le nombre total de joueurs dans le tournoi
$totalPlayersResult = $conn->query("SELECT COUNT(*) as total_players FROM tournament_players WHERE tournament_id = $tournament_id");
$totalPlayersData = $totalPlayersResult->fetch_assoc();
$total_players = $totalPlayersData['total_players'];

// Vérifier la phase du tournoi
$tournamentPhase = null;
if ($tournament_id) {
    $tournamentResult = $conn->query("SELECT phase FROM tournaments WHERE id = $tournament_id");
    if ($tournamentResult->num_rows > 0) {
        $tournamentData = $tournamentResult->fetch_assoc();
        $tournamentPhase = $tournamentData['phase'];
    } else {
        die("Erreur: Tournoi non trouvé.");
    }
}

// Calculer les points bonus en fonction du nombre de joueurs
$basePoints = 0; // Points de base pour le gagnant

// Initialiser les points de tournoi
$winner_points = 0;
$loser_points = 0;

switch (true) {
    case ($total_players == 4):
        $basePoints = 6; // Points de base pour 4 joueurs
        break;
    case ($total_players == 8):
        $basePoints = 9; // Points de base pour 8 joueurs
        break;
    case ($total_players == 16):
        $basePoints = 12; // Points de base pour 16 joueurs
        break;
    default:
        $basePoints = 0; // Si aucun nombre valide de joueurs
}

// Calculer des points supplémentaires en fonction de la phase de tournoi
switch ($tournamentPhase) {
    case 'finale':
        $winner_points = $basePoints; // Les points de base pour le gagnant en finale
        $loser_points = $basePoints * 0.75; // 75% des points pour le perdant en finale
        break;
    case 'demi':
        $winner_points = $basePoints * 0.75; // 75% des points de la finale pour le gagnant en demi-finale
        $loser_points = $basePoints * 0.5; // 50% des points de la finale pour le perdant en demi-finale
        break;
    case 'quart':
        $winner_points = $basePoints * 0.5; // 50% des points de la finale pour le gagnant en quart de finale
        $loser_points = $basePoints * 0.25; // 25% des points de la finale pour le perdant en quart de finale
        break;
    case 'huitième':
        $winner_points = $basePoints * 0.25; // 25% des points de la finale pour le gagnant en huitième de finale
        $loser_points = $basePoints * 0.1; // 10% des points de la finale pour le perdant en huitième de finale
        break;
    default:
        $winner_points = $basePoints; // Par défaut, on assume que c'est la finale
        $loser_points = $basePoints * 0.75; // 75% pour le perdant
        break;
}

// Ajouter les points de tournoi pour le gagnant
if ($winner == $player1) {
    $new_mmr1 += $winner_points;
    $new_mmr2 += $loser_points; // Ajouter les points de consolation au perdant
} else {
    $new_mmr2 += $winner_points;
    $new_mmr1 += $loser_points; // Ajouter les points de consolation au perdant
}

// Insérer le match dans la base de données
$sql = "UPDATE matches SET
    score1 = '$score1',
    score2 = '$score2',
    observed1 = '$observed1',
    observed2 = '$observed2',
    expected1 = '$expected1',
    expected2 = '$expected2',
    victory_margin = '$victory_margin',
    victory_factor = '$victory_factor',
    probability1 = '$probability1',
    probability2 = '$probability2',
    old_mmr1 = '$old_mmr1',
    old_mmr2 = '$old_mmr2',
    new_mmr1 = '$new_mmr1',
    new_mmr2 = '$new_mmr2',
    elo_difference = '$elo_difference',
    round = '$tournamentPhase',
    points1 = '$points1',
    points2 = '$points2',
    win_streak_bonus1 = '$bonusPoints1',
    win_streak_bonus2 = '$bonusPoints2',
    consolation_points = '$loser_points',
    winner_points = '$winner_points'
WHERE id = '$id'";

$conn->query($sql);

// Vérifier le nombre de matchs pour la phase actuelle
$matchesCount = $conn->query("SELECT COUNT(*) as match_count FROM matches WHERE tournament_id = $tournament_id AND round = '$tournamentPhase' AND score1 IS NOT NULL AND score2 IS NOT NULL");
$matchesCountResult = $matchesCount->fetch_assoc();
$match_count = $matchesCountResult['match_count'];

// Logique pour passer à la phase suivante
$newPhase = null;

if ($tournamentPhase === 'huitième' && $match_count == 8) {
    $newPhase = 'quart'; // Passer à la phase quart

    // Mettre à jour les gagnants des huitièmes de finale
    $winnersResult = $conn->query("SELECT player1 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'huitième' AND score1 > score2
                                    UNION
                                    SELECT player2 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'huitième' AND score2 > score1");

    while ($row = $winnersResult->fetch_assoc()) {
        $winnerId = $row['winner'];
        $updateWinnerStatus = $conn->prepare("UPDATE tournament_players SET current_round = 'quart' WHERE player_id = ? AND tournament_id = ?");
        $updateWinnerStatus->bind_param("ii", $winnerId, $tournament_id);
        $updateWinnerStatus->execute();
    }

} elseif ($tournamentPhase === 'quart' && $match_count == 4) {
    $newPhase = 'demi'; // Passer à la phase demi-finale

    // Mettre à jour les gagnants des quarts de finale
    $winnersResult = $conn->query("SELECT player1 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'quart' AND score1 > score2
                                    UNION
                                    SELECT player2 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'quart' AND score2 > score1");

    while ($row = $winnersResult->fetch_assoc()) {
        $winnerId = $row['winner'];
        $updateWinnerStatus = $conn->prepare("UPDATE tournament_players SET current_round = 'demi' WHERE player_id = ? AND tournament_id = ?");
        $updateWinnerStatus->bind_param("ii", $winnerId, $tournament_id);
        $updateWinnerStatus->execute();
    }

} elseif ($tournamentPhase === 'demi' && $match_count == 2) {
    $newPhase = 'finale'; // Passer à la phase finale

    // Mettre à jour les gagnants des demi-finales
    $winnersResult = $conn->query("SELECT player1 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'demi' AND score1 > score2
                                    UNION
                                    SELECT player2 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'demi' AND score2 > score1");

    while ($row = $winnersResult->fetch_assoc()) {
        $winnerId = $row['winner'];
        $updateWinnerStatus = $conn->prepare("UPDATE tournament_players SET current_round = 'finale' WHERE player_id = ? AND tournament_id = ?");
        $updateWinnerStatus->bind_param("ii", $winnerId, $tournament_id);
        $updateWinnerStatus->execute();
    }

} elseif ($tournamentPhase === 'finale' && $match_count == 1) {
    $end_date = (new DateTime())->format('Y-m-d H:i:s');

    // Mettre à jour la date de fin du tournoi
    $sql = "UPDATE tournaments SET end_date = '$end_date' WHERE id = $tournament_id";
    $conn->query($sql);

    // Récupérer le gagnant de la finale
    $winnerResult = $conn->query("SELECT player1 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'finale' AND score1 > score2
                                    UNION
                                    SELECT player2 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'finale' AND score2 > score1");
    
    if ($winnerResult->num_rows > 0) {
        $row = $winnerResult->fetch_assoc();
        $winnerId = $row['winner'];

        // Mettre à jour le statut du gagnant
        $won = "UPDATE tournament_players SET is_won = 1 WHERE tournament_id = $tournament_id AND player_id = $winnerId";
        $conn->query($won);
    }
}

// Fonction pour insérer de nouveaux matchs
function insertNewMatches($conn, $tournament_id, $winners, $round) {
    if (count($winners) < 2) return; // S'assurer qu'il y a au moins 2 gagnants

    // Créer les nouveaux matchs en paires
    for ($i = 0; $i < count($winners); $i += 2) {
        if (isset($winners[$i + 1])) {
            $player1 = $winners[$i];
            $player2 = $winners[$i + 1];
            // Préparer la requête d'insertion
            $sql = "INSERT INTO matches (tournament_id, player1, player2, round, match_date) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiiss", $tournament_id, $player1, $player2, $round, (new DateTime())->format('Y-m-d H:i:s'));

            // Exécuter la requête d'insertion
            if (!$stmt->execute()) {
                echo "Erreur lors de l'insertion du match : " . $stmt->error;
            }
        }
    }
}


// Si une nouvelle phase est définie, on met à jour la phase du tournoi
if ($newPhase) {
    $updateTournamentPhase = $conn->prepare("UPDATE tournaments SET phase = ? WHERE id = ?");
    $updateTournamentPhase->bind_param("si", $newPhase, $tournament_id);
    
    if (!$updateTournamentPhase->execute()) {
        echo "Erreur lors de la mise à jour de la phase du tournoi : " . $conn->error;
    } else {     
        // COMPORTEMENT PARTIELLEMENT GERER ATTENTION   
        if ($newPhase === 'huitième') {
            $winnerResult = $conn->query("SELECT player1 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'groupe' AND score1 > score2
                                            UNION
                                            SELECT player2 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'groupe' AND score2 > score1");
            $winners = [];
            while ($winner = $winnerResult->fetch_assoc()) {
                $winners[] = $winner['winner'];
            }
            // Insérer les nouveaux matchs pour les huitièmes
            insertNewMatches($conn, $tournament_id, $winners, 'huitième');
        }

        // Insérer de nouveaux matchs en fonction de la phase suivante
        if ($newPhase === 'quart') {
            // Récupérer les gagnants des phases précédentes pour les quarts
            $winnersResult = $conn->query("SELECT player1 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'huitième' AND score1 > score2
                                            UNION
                                            SELECT player2 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'huitième' AND score2 > score1");
            $winners = [];
            while ($winner = $winnersResult->fetch_assoc()) {
                $winners[] = $winner['winner'];
            }
            // Insérer les nouveaux matchs pour les quarts
            insertNewMatches($conn, $tournament_id, $winners, 'quart');
        } elseif ($newPhase === 'demi') {
            // Récupérer les gagnants des quarts pour les demi-finales
            $winnersResult = $conn->query("SELECT player1 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'quart' AND score1 > score2
                                            UNION
                                            SELECT player2 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'quart' AND score2 > score1");
            $winners = [];
            while ($winner = $winnersResult->fetch_assoc()) {
                $winners[] = $winner['winner'];
            }
            // Insérer les nouveaux matchs pour les demi-finales
            insertNewMatches($conn, $tournament_id, $winners, 'demi');
        } elseif ($newPhase === 'finale') {
            // Récupérer les gagnants des demi-finales pour la finale
            $winnersResult = $conn->query("SELECT player1 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'demi' AND score1 > score2
                                            UNION
                                            SELECT player2 AS winner FROM matches WHERE tournament_id = $tournament_id AND round = 'demi' AND score2 > score1");
            $winners = [];
            while ($winner = $winnersResult->fetch_assoc()) {
                $winners[] = $winner['winner'];
            }
            // Insérer le nouveau match pour la finale
            insertNewMatches($conn, $tournament_id, $winners, 'finale');
        }
    }
}

if ($conn->query($sql) === TRUE) {
    // Mettre à jour les MMR, les rangs et les séries de victoires des joueurs
    $updatePlayer1 = "UPDATE players SET mmr = $new_mmr1, rank = '$new_rank1', current_win_streak = $new_current_win_streak1, best_win_streak = GREATEST(best_win_streak, $new_current_win_streak1), best_mmr = GREATEST(best_mmr, $new_mmr1) WHERE id = $player1";
    $updatePlayer2 = "UPDATE players SET mmr = $new_mmr2, rank = '$new_rank2', current_win_streak = $new_current_win_streak2, best_win_streak = GREATEST(best_win_streak, $new_current_win_streak2), best_mmr = GREATEST(best_mmr, $new_mmr2) WHERE id = $player2";

    $conn->query($updatePlayer1);
    $conn->query($updatePlayer2);

    header("Location: tournament_detail.php?id=$tournament_id");
    exit(); // Arrêter l'exécution après la redirection
} else {
    echo "Erreur : " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
