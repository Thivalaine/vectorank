<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $match_id = intval($_POST['match_id']);

    // Définir le fuseau horaire
    date_default_timezone_set('Europe/Paris');

    // Créer un objet DateTime pour obtenir la date et l'heure actuelles
    $datetime = new DateTime();
    $adjustment_date = $datetime->format('Y-m-d H:i:s');

    // Récupérer les détails du match
    $matchQuery = "SELECT * FROM matches WHERE id = $match_id";
    $matchResult = $conn->query($matchQuery);

    if ($matchResult->num_rows > 0) {
        $matchData = $matchResult->fetch_assoc();

        $player1_id = intval($matchData['player1']);
        $player2_id = intval($matchData['player2']);
        $score1 = intval($matchData['score1']);
        $score2 = intval($matchData['score2']);
        $points1 = intval($matchData['points1']);
        $points2 = intval($matchData['points2']);
        $win_streak_bonus1 = intval($matchData['win_streak_bonus1']);
        $win_streak_bonus2 = intval($matchData['win_streak_bonus2']);

        // Récupérer les MMR actuels des joueurs
        $player1Query = "SELECT id, name, mmr FROM players WHERE id = $player1_id";
        $player2Query = "SELECT id, name, mmr FROM players WHERE id = $player2_id";

        $player1Result = $conn->query($player1Query);
        $player2Result = $conn->query($player2Query);

        if ($player1Result->num_rows > 0 && $player2Result->num_rows > 0) {
            $player1Data = $player1Result->fetch_assoc();
            $player2Data = $player2Result->fetch_assoc();

            $current_mmr1 = intval($player1Data['mmr']);
            $current_mmr2 = intval($player2Data['mmr']);

            // Déterminer le résultat du match
            $is_player1_winner = $score1 > $score2;
            $is_player2_winner = $score2 > $score1;

            // Calculer les MMR après ajustement
            $mmr_after_adjustment1 = $current_mmr1 - $points1 - $win_streak_bonus1;
            $mmr_after_adjustment2 = $current_mmr2 - $points2 - $win_streak_bonus2;

            // Calculer les ajustements
            $adjustment_winner = abs($is_player1_winner ? $points1 + $win_streak_bonus1 : $points2 + $win_streak_bonus2);
            $adjustment_loser = abs($is_player1_winner ? $points2 + $win_streak_bonus2 : $points1 + $win_streak_bonus1);

            $winner_id = $is_player1_winner ? $player1_id : $player2_id;
            $loser_id = $is_player1_winner ? $player2_id : $player1_id;

            $new_mmr_winner = $is_player1_winner ? $mmr_after_adjustment1 : $mmr_after_adjustment2;
            $new_mmr_loser = $is_player1_winner ? $mmr_after_adjustment2 : $mmr_after_adjustment1;

            $success = true;

            // Mettre à jour les MMR des joueurs
            $updatePlayer1 = $conn->prepare("UPDATE players SET mmr = ? WHERE id = ?");
            $updatePlayer1->bind_param("ii", $mmr_after_adjustment1, $player1_id);

            $updatePlayer2 = $conn->prepare("UPDATE players SET mmr = ? WHERE id = ?");
            $updatePlayer2->bind_param("ii", $mmr_after_adjustment2, $player2_id);

            if (!$updatePlayer1->execute()) {
                echo "Erreur lors de la mise à jour du joueur 1 : " . $conn->error . "<br>";
                $success = false;
            }

            if (!$updatePlayer2->execute()) {
                echo "Erreur lors de la mise à jour du joueur 2 : " . $conn->error . "<br>";
                $success = false;
            }

            // Insérer les ajustements
            if ($success) {
                $insertAdjustmentWinner = $conn->prepare("INSERT INTO adjustments (player_id, old_mmr, new_mmr, adjustment_value, match_id, adjustment_date)
                                                        VALUES (?, ?, ?, ?, ?, ?)");
                $old_mmr_winner = ($winner_id == $player1_id) ? $current_mmr1 : $current_mmr2;
                $insertAdjustmentWinner->bind_param("iiiiss", $winner_id, $old_mmr_winner, $new_mmr_winner, $adjustment_winner, $match_id, $adjustment_date);
                if (!$insertAdjustmentWinner->execute()) {
                    echo "Erreur lors de l'insertion de l'ajustement pour le gagnant : " . $conn->error . "<br>";
                    $success = false;
                }

                $insertAdjustmentLoser = $conn->prepare("INSERT INTO adjustments (player_id, old_mmr, new_mmr, adjustment_value, match_id, adjustment_date)
                                                        VALUES (?, ?, ?, ?, ?, ?)");
                $old_mmr_loser = ($loser_id == $player1_id) ? $current_mmr1 : $current_mmr2;
                $insertAdjustmentLoser->bind_param("iiiiss", $loser_id, $old_mmr_loser, $new_mmr_loser, $adjustment_loser, $match_id, $adjustment_date);
                if (!$insertAdjustmentLoser->execute()) {
                    echo "Erreur lors de l'insertion de l'ajustement pour le perdant : " . $conn->error . "<br>";
                    $success = false;
                }
            }

            // Mettre à jour l'état du match
            if ($success) {
                $updateMatch = $conn->prepare("UPDATE matches SET is_adjusted = TRUE WHERE id = ?");
                $updateMatch->bind_param("i", $match_id);
                if ($updateMatch->execute()) {
                    header("Location: index.php");
                } else {
                    echo "Erreur lors de la mise à jour du statut du match : " . $conn->error;
                }
            }
        } else {
            die("Erreur : Joueur non trouvé.");
        }
    } else {
        echo "Erreur : Match non trouvé.";
    }

    $conn->close();
} else {
    echo "Méthode de requête non valide.";
}
?>
