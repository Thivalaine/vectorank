<?php
// Connexion à la base de données
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tournamentName = $_POST['tournament_name'];
    
    // Définir le fuseau horaire
    date_default_timezone_set('Europe/Paris');
    $startDate = (new DateTime())->format('Y-m-d H:i:s');
    $endDate = null; // Remplacez ceci si vous avez une logique pour définir la date de fin

    $participants = $_POST['participants'];
    $playerCount = count($participants);

    // Déterminer la phase initiale du tournoi en fonction du nombre de participants
    if ($playerCount == 16) {
        $initialPhase = 'huitième';
    } elseif ($playerCount == 8) {
        $initialPhase = 'quart';
    } elseif ($playerCount == 4) {
        $initialPhase = 'demi';
    } 
    // elseif ($playerCount == 2) {
        // $initialPhase = 'finale';
    //} 
    else {
        echo "Nombre de participants non supporté. Assurez-vous d'avoir 16, 8 ou 4 participants.";
        exit;
    }

    // Insertion du tournoi dans la base de données
    $stmt = $conn->prepare("INSERT INTO tournaments (name, start_date, end_date, phase, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $tournamentName, $startDate, $endDate, $initialPhase, $startDate);

    if ($stmt->execute()) {
        $tournamentId = $stmt->insert_id; // Récupération de l'ID du tournoi inséré

        // Insertion des participants dans la table tournament_players avec des valeurs par défaut
        $stmtPlayer = $conn->prepare("INSERT INTO tournament_players (tournament_id, player_id, current_round, is_won) VALUES (?, ?, ?, ?)");
        
        // Vérification d'erreur pour la préparation de la requête
        if (!$stmtPlayer) {
            die("Erreur de préparation de la requête : " . $conn->error);
        }

        foreach ($participants as $playerId) {
            // Valeurs par défaut pour current_round et is_won
            $currentRound = $initialPhase; // Mettre current_round à la phase initiale
            $isWon = false; // Au départ, personne n'a gagné
            $stmtPlayer->bind_param("iisi", $tournamentId, $playerId, $currentRound, $isWon);
            $stmtPlayer->execute();
        }

        // Génération des matchs pour la phase initiale
        shuffle($participants); // Mélange des participants pour une répartition aléatoire

        for ($i = 0; $i < $playerCount; $i += 2) {
            $stmtMatch = $conn->prepare("INSERT INTO matches (tournament_id, round, player1, player2, match_date) VALUES (?, ?, ?, ?, ?)");
            if (!$stmtMatch) {
                die("Erreur de préparation de la requête : " . $conn->error);
            }
            // Assurez-vous qu'il y a un deuxième joueur avant de le lier
            if (isset($participants[$i + 1])) {
                $player1 = $participants[$i];
                $player2 = $participants[$i + 1];
                $stmtMatch->bind_param("issis", $tournamentId, $initialPhase, $player1, $player2, $startDate);
                $stmtMatch->execute();
            }
        }

        header("Location: tournament_detail.php?id=$tournamentId");
    } else {
        echo "Erreur lors de l'ajout du tournoi: " . $stmt->error;
    }

    // Fermeture des requêtes préparées
    $stmt->close();
    $stmtPlayer->close();
    $stmtMatch->close();
}
?>
