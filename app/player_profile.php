<?php
    include 'db.php';
    $playerId = $_GET['id'];

    // Récupérer les informations du joueur
    $result = $conn->query("SELECT * FROM players WHERE id = $playerId");
    
    if (!$result) {
        echo "<h2>Erreur de requête SQL : " . $conn->error . "</h2>";
        exit;
    }

    $player = $result->fetch_assoc();

    if (!$player || $player['is_anonymized']) {
        echo "<h2>Joueur non trouvé.</h2>";
        exit;
    }

    $pageTitle = "Profil de " . $player['name']; 
    include('header.php'); 


    function buildUrl($playerId, $page, $matchesPerPage, $searchQuery, $tab) {
        return "player_profile.php?id=" . urlencode($playerId) .
            "&page=" . $page .
            "&matchesPerPage=" . $matchesPerPage .
            "&search=" . urlencode($searchQuery) .
            "&tab=" . urlencode($tab);
    }
?>

    <div class="container">
        <?php
        // Compter les victoires et défaites
        $winsResult = $conn->query("SELECT COUNT(*) as wins FROM matches WHERE (player1 = $playerId AND score1 > score2) OR (player2 = $playerId AND score2 > score1)");
        $winsCount = $winsResult ? $winsResult->fetch_assoc()['wins'] : 0;

        $lossesResult = $conn->query("SELECT COUNT(*) as losses FROM matches WHERE (player1 = $playerId AND score1 < score2) OR (player2 = $playerId AND score2 < score1)");
        $lossesCount = $lossesResult ? $lossesResult->fetch_assoc()['losses'] : 0;

        // Compter les buts marqués et encaissés
        $goalsScoredResult = $conn->query("SELECT SUM(CASE WHEN player1 = $playerId THEN score1 ELSE score2 END) as goals_scored FROM matches WHERE player1 = $playerId OR player2 = $playerId");
        $goalsScored = $goalsScoredResult ? $goalsScoredResult->fetch_assoc()['goals_scored'] : 0;

        $goalsConcededResult = $conn->query("SELECT SUM(CASE WHEN player1 = $playerId THEN score2 ELSE score1 END) as goals_conceded FROM matches WHERE player1 = $playerId OR player2 = $playerId");
        $goalsConceded = $goalsConcededResult ? $goalsConcededResult->fetch_assoc()['goals_conceded'] : 0;

        // Calculer le ratio
        $totalGames = $winsCount + $lossesCount;
        $winRatio = $totalGames > 0 ? ($winsCount / $totalGames) * 100 : 0;

        // Pagination
        $matchesPerPage = isset($_GET['matchesPerPage']) ? (int)$_GET['matchesPerPage'] : 10;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($currentPage - 1) * $matchesPerPage;

        // Rechercher les matchs par critère
        $searchQuery = '';
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $searchQuery = $conn->real_escape_string($_GET['search']);
            $searchCondition = " AND (player1 IN (SELECT id FROM players WHERE name LIKE '%$searchQuery%') OR player2 IN (SELECT id FROM players WHERE name LIKE '%$searchQuery%'))";
        } else {
            $searchCondition = '';
        }

        // Compter le nombre total de matchs
        $totalMatchesResult = $conn->query("SELECT COUNT(*) as total FROM matches WHERE (player1 = $playerId OR player2 = $playerId) $searchCondition");
        $totalMatches = $totalMatchesResult ? $totalMatchesResult->fetch_assoc()['total'] : 0;
        $totalPages = ceil($totalMatches / $matchesPerPage);

        // Obtenir le joueur contre qui il perd le plus souvent
        $mostLostAgainstResult = $conn->query("
            SELECT CASE 
                WHEN player1 = $playerId THEN player2
                ELSE player1
            END as opponent_id, COUNT(*) as losses
            FROM matches
            WHERE (player1 = $playerId AND score1 < score2) OR (player2 = $playerId AND score2 < score1)
            GROUP BY opponent_id
            ORDER BY losses DESC
            LIMIT 1
        ");
        $mostLostAgainst = $mostLostAgainstResult ? $mostLostAgainstResult->fetch_assoc() : null;
        $mostLostAgainstName = $mostLostAgainst ? $conn->query("SELECT name FROM players WHERE id = {$mostLostAgainst['opponent_id']}")->fetch_assoc()['name'] : 'N/A';

        // Obtenir le joueur contre qui il gagne le plus souvent
        $mostWonAgainstResult = $conn->query("
            SELECT CASE 
                WHEN player1 = $playerId THEN player2
                ELSE player1
            END as opponent_id, COUNT(*) as wins
            FROM matches
            WHERE (player1 = $playerId AND score1 > score2) OR (player2 = $playerId AND score2 > score1)
            GROUP BY opponent_id
            ORDER BY wins DESC
            LIMIT 1
        ");
        $mostWonAgainst = $mostWonAgainstResult ? $mostWonAgainstResult->fetch_assoc() : null;
        $mostWonAgainstName = $mostWonAgainst ? $conn->query("SELECT name FROM players WHERE id = {$mostWonAgainst['opponent_id']}")->fetch_assoc()['name'] : 'N/A';
        ?>

        <h1>Profil de <?php echo htmlspecialchars($player['name']); ?></h1>
        <p><strong>ID:</strong> <?php echo $player['id']; ?></p>
        <p><strong>MMR Actuel:</strong> <?php echo $player['mmr']; ?></p>
        <p><strong>Rang Actuel:</strong> <?php echo htmlspecialchars($player['rank']); ?></p>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Statistiques</h5>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group">
                            <li class="list-group-item"><i class="fas fa-futbol"></i> <strong>Matchs disputés:</strong> <?php echo $totalGames; ?></li>
                            <li class="list-group-item">
                                <i class="fas fa-trophy"></i> 
                                <strong>Ratio V/D :</strong> 
                                <?php 
                                    echo $winsCount . '/' . $lossesCount; 
                                ?>
                            </li>
                            <li class="list-group-item"><strong><i class="fas fa-percent"></i> Pourcentage de victoires :</strong> <?php echo number_format($winRatio, 2) . '%'; ?></li>
                            <li class="list-group-item"><i class="fas fas fa-fire"></i> <strong>WS Actuel :</strong> <?php echo $player['current_win_streak']; ?></li>
                            <li class="list-group-item"><i class="fas fa-star"></i> <strong>Plus Grande Série de victoires:</strong> <?php echo $player['best_win_streak']; ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-group">
                            <li class="list-group-item"><i class="fas fa-arrow-up"></i> <strong>Buts Marqués:</strong> <?php echo $goalsScored; ?></li>
                            <li class="list-group-item"><i class="fas fa-arrow-down"></i> <strong>Buts Encaissés:</strong> <?php echo $goalsConceded; ?></li>
                            <li class="list-group-item"><i class="fas fa-user-friends"></i> <strong>L'Opposant à éviter :</strong> <?php echo htmlspecialchars($mostLostAgainstName); ?></li>
                            <li class="list-group-item"><i class="fas fa-user-check"></i> <strong>L'Opposant favori :</strong> <?php echo htmlspecialchars($mostWonAgainstName); ?></li>
                            <li class="list-group-item"><i class="fas fa-chart-line"></i> <strong>Meilleur MMR :</strong> <?php echo $player['best_mmr']; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <h2>Historique des matchs</h2>

        <form class="d-flex mb-4" method="GET" action="player_profile.php">
            <input type="hidden" name="id" value="<?php echo $playerId; ?>">
            <input class="form-control me-2" type="search" placeholder="Rechercher un joueur" aria-label="Search" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button class="btn btn-outline-success" type="submit">Rechercher</button>
        </form>


        <!-- Nav tabs -->
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab1-tab" data-bs-toggle="tab" href="#tab1" role="tab" aria-controls="tab1" aria-selected="true">Matchs classés</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab2-tab" data-bs-toggle="tab" href="#tab2" role="tab" aria-controls="tab2" aria-selected="false">Tournois</a>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
                <div class="collapse show" id="collapseTab1">
                    <div class="card card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Adversaire</th>
                                        <th>Score</th>
                                        <th>MMR</th>
                                        <th>MMR Adversaire</th>
                                        <th>Série de victoires</th>
                                        <th>Action</th> <!-- Nouvelle colonne pour le bouton d'ajustement -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Initialisation de la variable de série de victoires
                                    $winStreak = 0;

                                    $matchesQuery = "
                                        SELECT 
                                            m.*, 
                                            p1.name AS player1_name, 
                                            p2.name AS player2_name, 
                                            p1.id AS player1_id,
                                            p2.id AS player2_id,
                                            p1.is_anonymized AS p1_anonymized,
                                            p2.is_anonymized AS p2_anonymized,
                                            IF(m.player1 = $playerId, m.points1, m.points2) AS player_points,
                                            IF(m.player1 = $playerId, m.win_streak_bonus1, m.win_streak_bonus2) AS player_bonus,
                                            IF(m.player1 = $playerId, m.points2, m.points1) AS opponent_points,
                                            IF(m.player1 = $playerId, m.win_streak_bonus2, m.win_streak_bonus1) AS opponent_bonus,
                                            m.old_mmr1, m.new_mmr1, m.old_mmr2, m.new_mmr2,
                                            m.is_adjusted
                                        FROM matches m
                                        JOIN players p1 ON m.player1 = p1.id
                                        JOIN players p2 ON m.player2 = p2.id
                                        WHERE (m.player1 = $playerId OR m.player2 = $playerId) 
                                        AND m.tournament_id IS NULL  -- Exclure les matchs de tournois
                                        ORDER BY m.match_date DESC 
                                        LIMIT $matchesPerPage OFFSET $offset
                                    ";

                                    $matchesResult = $conn->query($matchesQuery);

                                    if (!$matchesResult) {
                                        die("Error: " . $conn->error);
                                    }

                                    while ($match = $matchesResult->fetch_assoc()) {
                                        // Vérifiez si le joueur actuel est player1 ou player2
                                        $isPlayer1 = $match['player1'] == $playerId;
                                        $isOpponentAnonymized = $isPlayer1 ? $match['p2_anonymized'] : $match['p1_anonymized'];

                                        // Préparer les données pour l'affichage
                                        $score = $isPlayer1 ? "{$match['score1']} - {$match['score2']}" : "{$match['score2']} - {$match['score1']}";
                                        $playerOldMMR = $isPlayer1 ? $match['old_mmr1'] : $match['old_mmr2'];
                                        $playerNewMMR = $isPlayer1 ? $match['new_mmr1'] : $match['new_mmr2'];
                                        $opponentOldMMR = $isPlayer1 ? $match['old_mmr2'] : $match['old_mmr1'];
                                        $opponentNewMMR = $isPlayer1 ? $match['new_mmr2'] : $match['new_mmr1'];

                                        // Déterminer la couleur de la ligne en fonction de l'état du match
                                        $rowColor = $match['is_adjusted'] ? 'table-secondary' : ($isPlayer1 && $match['score1'] > $match['score2'] || !$isPlayer1 && $match['score2'] > $match['score1'] ? 'table-success' : 'table-danger');

                                        // Points et bonus du joueur
                                        $pointsDisplayPlayer = '';
                                        if ($match['player_points'] != 0) {
                                            $pointsDisplayPlayer .= ($match['player_points'] > 0 ? "(+{$match['player_points']}" : "({$match['player_points']}");
                                            if (!empty($match['player_bonus'])) {
                                                $pointsDisplayPlayer .= ", +{$match['player_bonus']} WS";
                                            }
                                            $pointsDisplayPlayer .= ")";
                                        }

                                        // Points et bonus de l'adversaire
                                        $pointsDisplayOpponent = '';
                                        if ($match['opponent_points'] != 0) {
                                            $pointsDisplayOpponent .= ($match['opponent_points'] > 0 ? "(+{$match['opponent_points']}" : "({$match['opponent_points']}");
                                            if (!empty($match['opponent_bonus'])) {
                                                $pointsDisplayOpponent .= ", +{$match['opponent_bonus']} WS";
                                            }
                                            $pointsDisplayOpponent .= ")";
                                        }

                                        // Déterminer si le joueur est en série de victoires
                                        if (($isPlayer1 && $match['score1'] > $match['score2']) || (!$isPlayer1 && $match['score2'] > $match['score1'])) {
                                            $winStreak++;
                                        } else {
                                            $winStreak = 0; // Réinitialiser si le joueur perd
                                        }

                                        // Afficher la série de victoires dans la colonne correspondante
                                        $winStreakDisplay = $winStreak > 0 ? "<span class='badge bg-warning'><i class='fas fa-fire'></i> $winStreak</span>" : "<span class='badge bg-secondary'>Aucune</span>";

                                        // Gérer l'affichage des informations en fonction de l'anonymat de l'adversaire
                                        if ($isOpponentAnonymized) {
                                            $opponentName = 'Anonyme';
                                            $opponentMMRDisplay = '****';
                                            $pointsDisplayOpponent = ''; // Ne pas afficher les points de l'adversaire
                                            $opponentProfileLink = 'Anonyme'; // Pas de lien pour un adversaire anonymisé
                                        } else {
                                            $opponentName = $isPlayer1 ? $match['player2_name'] : $match['player1_name'];
                                            $opponentId = $isPlayer1 ? $match['player2_id'] : $match['player1_id'];
                                            $opponentMMRDisplay = $isPlayer1 ? "{$match['old_mmr2']} → <strong>{$match['new_mmr2']}</strong>" : "{$match['old_mmr1']} → <strong>{$match['new_mmr1']}</strong>";
                                            $opponentProfileLink = "<a href='/player_profile.php?id=$opponentId' class='text-decoration-none text-primary fw-bold' style='border-bottom: 1px dashed; transition: all 0.3s;'>".htmlspecialchars($opponentName)."</a>";
                                        }

                                        // Ajouter un bouton de signalement avec une icône FontAwesome seulement si le match n'a pas encore été ajusté
                                        $adjustmentButton = $match['is_adjusted'] ? '' : "<a href='add_adjustment.php?match_id={$match['id']}' class='btn btn-danger btn-sm'><i class='fas fa-exclamation-triangle'></i></a>";

                                        // Affichage du tableau
                                        echo "<tr class='$rowColor'>
                                            <td>{$match['match_date']}</td>
                                            <td>$opponentProfileLink</td> <!-- Afficher le nom de l'adversaire comme lien ou 'Anonyme' -->
                                            <td>$score</td>
                                            <td>$playerOldMMR → <strong>$playerNewMMR</strong> <span>$pointsDisplayPlayer</span></td>
                                            <td>$opponentMMRDisplay <span>$pointsDisplayOpponent</span></td> <!-- Afficher le MMR de l'adversaire ou '****' -->
                                            <td>$winStreakDisplay</td> <!-- Affichage de la série de victoires -->
                                            <td>$adjustmentButton</td> <!-- Affichage du bouton de signalement -->
                                        </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
        <div class="collapse show" id="collapseTab2">
            <div class="card card-body">
                <!-- Formulaire de sélection de tournoi -->
                <form method="GET" action="player_profile.php" class="mb-4">
                    <input type="hidden" name="id" value="<?php echo $playerId; ?>">
                    <label for="tournament_id">Sélectionner un tournoi:</label>
                    <select name="tournament_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous les tournois</option>
                        <?php
                        // Assurez-vous que $player_id contient l'ID du joueur actuel
                        $player_id = $playerId;

                        // Récupérer les tournois où le joueur a participé
                        $tournamentsResult = $conn->query("
                            SELECT t.*
                            FROM tournaments t
                            INNER JOIN tournament_players tp ON t.id = tp.tournament_id
                            WHERE tp.player_id = $player_id
                            ORDER BY t.start_date DESC
                        ");

                        // Générer les options du menu déroulant
                        while ($tournament = $tournamentsResult->fetch_assoc()) {
                            $selected = (isset($_GET['tournament_id']) && $_GET['tournament_id'] == $tournament['id']) ? 'selected' : '';
                            echo "<option value=\"{$tournament['id']}\" $selected>{$tournament['name']} ({$tournament['start_date']} - {$tournament['end_date']})</option>";
                        }
                        ?>
                    </select>
                </form>
                <div class="table-responsive">
                <table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Date</th>
            <th>Adversaire</th>
            <th>Score</th>
            <th>MMR</th>
            <th>MMR Adversaire</th>
            <th>Tournament</th>
            <th>WS</th>
            <th>Détails</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Récupération des matchs de tournois selon la sélection
        $tournamentIdCondition = isset($_GET['tournament_id']) && !empty($_GET['tournament_id']) ? "AND m.tournament_id = " . (int)$_GET['tournament_id'] : "";

        $matchesQuery = "
            SELECT 
                m.*, 
                p1.name AS player1_name, 
                p2.name AS player2_name,
                p1.is_anonymized AS p1_anonymized,
                p2.is_anonymized AS p2_anonymized,
                IF(m.player1 = $playerId, m.old_mmr1, m.old_mmr2) AS player_old_mmr,
                IF(m.player1 = $playerId, m.new_mmr1, m.new_mmr2) AS player_new_mmr,
                IF(m.player1 = $playerId, m.old_mmr2, m.old_mmr1) AS opponent_old_mmr,
                IF(m.player1 = $playerId, m.new_mmr2, m.new_mmr1) AS opponent_new_mmr,
                t.name AS tournament_name,
                IF(m.player1 = $playerId, m.win_streak_bonus1, m.win_streak_bonus2) AS player_bonus,
                IF(m.player1 = $playerId, m.points1, m.points2) AS player_points,
                IF(m.player1 = $playerId, m.points2, m.points1) AS opponent_points,
                IF(m.player1 = $playerId, m.win_streak_bonus2, m.win_streak_bonus1) AS opponent_bonus
            FROM matches m
            JOIN players p1 ON m.player1 = p1.id
            JOIN players p2 ON m.player2 = p2.id
            LEFT JOIN tournaments t ON m.tournament_id = t.id
            WHERE (m.player1 = $playerId OR m.player2 = $playerId) 
            AND m.tournament_id IS NOT NULL 
            $tournamentIdCondition
            ORDER BY m.match_date DESC 
            LIMIT $matchesPerPage OFFSET $offset
        ";

        $matchesResult = $conn->query($matchesQuery);
        $winStreak = 0; // Initialisation de la variable de série de victoires
        $opponentWinStreak = 0; // Initialisation de la série de victoires de l'adversaire

        while ($match = $matchesResult->fetch_assoc()) {
            $isPlayer1 = $match['player1'] == $playerId;
            $isOpponentAnonymized = $isPlayer1 ? $match['p2_anonymized'] : $match['p1_anonymized'];
            $opponentName = $isPlayer1 ? $match['player2_name'] : $match['player1_name'];
            $opponentId = $isPlayer1 ? $match['player2'] : $match['player1']; // Récupérer l'ID de l'adversaire
            $score = $isPlayer1 ? "{$match['score1']} - {$match['score2']}" : "{$match['score2']} - {$match['score1']}";

            // Déterminer la couleur de la ligne
            $rowColor = ($isPlayer1 && $match['score1'] > $match['score2']) || (!$isPlayer1 && $match['score2'] > $match['score1']) ? 'table-success' : 'table-danger';

            // Préparer les données MMR
            $playerOldMMR = $isPlayer1 ? $match['old_mmr1'] : $match['old_mmr2'];
            $playerNewMMR = $isPlayer1 ? $match['new_mmr1'] : $match['new_mmr2'];
            $opponentOldMMR = $isPlayer1 ? $match['old_mmr2'] : $match['old_mmr1'];
            $opponentNewMMR = $isPlayer1 ? $match['new_mmr2'] : $match['new_mmr1'];

            // Points et bonus du joueur
            $pointsDisplayPlayer = '';
            if ($match['player_points'] != 0) {
                $pointsDisplayPlayer .= ($match['player_points'] > 0 ? "(+{$match['player_points']}" : "({$match['player_points']}");
                if (!empty($match['player_bonus'])) {
                    $pointsDisplayPlayer .= ", +{$match['player_bonus']} WS";
                }
                $pointsDisplayPlayer .= ")";
            }

            // Points et bonus de l'adversaire
            $pointsDisplayOpponent = '';
            if ($match['opponent_points'] != 0) {
                $pointsDisplayOpponent .= ($match['opponent_points'] > 0 ? "(+{$match['opponent_points']}" : "({$match['opponent_points']}");
                if (!empty($match['opponent_bonus'])) {
                    $pointsDisplayOpponent .= ", +{$match['opponent_bonus']} WS";
                }
                $pointsDisplayOpponent .= ")";
            }

            // Ajouter les points du vainqueur ou du perdant pour le joueur
            if ($isPlayer1) {
                $pointsDisplayPlayer .= $match['score1'] > $match['score2'] ? " +{$match['winner_points']} TP" : " +{$match['consolation_points']} TP";
                $pointsDisplayOpponent .= $match['score2'] > $match['score1'] ? " +{$match['winner_points']} TP" : " +{$match['consolation_points']} TP";
            } else {
                $pointsDisplayPlayer .= $match['score2'] > $match['score1'] ? " +{$match['winner_points']} TP" : " +{$match['consolation_points']} TP";
                $pointsDisplayOpponent .= $match['score1'] > $match['score2'] ? " +{$match['winner_points']} TP" : " +{$match['consolation_points']} TP";
            }

            // Ne rien afficher si les points sont nuls
            $pointsDisplayPlayer = $pointsDisplayPlayer ?: '';
            $pointsDisplayOpponent = $pointsDisplayOpponent ?: '';

            // Déterminer si le joueur est en série de victoires
            if (($isPlayer1 && $match['score1'] > $match['score2']) || (!$isPlayer1 && $match['score2'] > $match['score1'])) {
                $winStreak++;
                $opponentWinStreak = 0; // Réinitialiser si l'adversaire perd
            } else {
                $opponentWinStreak++; // Incrémenter la série de victoires de l'adversaire
                $winStreak = 0; // Réinitialiser si le joueur perd
            }

            // Afficher la série de victoires dans la colonne correspondante
            $winStreakDisplay = $winStreak > 0 ? "<span class='badge bg-warning'><i class='fas fa-fire'></i> $winStreak</span>" : "<span class='badge bg-secondary'>Aucune</span>";
            $opponentWinStreakDisplay = $opponentWinStreak > 0 ? "<span class='badge bg-warning'><i class='fas fa-fire'></i> $opponentWinStreak</span>" : "<span class='badge bg-secondary'>Aucune</span>";

            // Gérer l'affichage des informations en fonction de l'anonymat de l'adversaire
            if ($isOpponentAnonymized) {
                $opponentName = 'Anonyme';
                $opponentMMRDisplay = '****';
                $pointsDisplayOpponent = ''; // Ne pas afficher les points de l'adversaire
                $opponentProfileLink = 'Anonyme'; // Pas de lien pour un adversaire anonymisé
            } else {
                $opponentMMRDisplay = $isPlayer1 ? "{$match['old_mmr2']} → <strong>{$match['new_mmr2']}</strong>" : "{$match['old_mmr1']} → <strong>{$match['new_mmr1']}</strong>";
                $opponentProfileLink = "<a href='player_profile.php?id=$opponentId&tab=tab2' class='text-decoration-none text-primary fw-bold' style='border-bottom: 1px dashed; transition: all 0.3s;'>".htmlspecialchars($opponentName)."</a>";
            }

            // Affichage du tableau
            echo "<tr class='$rowColor'>
                <td>{$match['match_date']}</td>
                <td>$opponentProfileLink</td>
                <td>$score</td>
                <td>$playerOldMMR → <strong>$playerNewMMR</strong> <span>$pointsDisplayPlayer</span></td>
                <td>$opponentMMRDisplay <span>$pointsDisplayOpponent</span></td>
                <td>" . htmlspecialchars($match['tournament_name'] ?? 'Aucun') . "</td>
                <td>$winStreakDisplay / $opponentWinStreakDisplay</td>
                <td><a href='tournament_detail.php?id={$match['tournament_id']}' class='btn btn-primary btn-sm'><i class='fa-solid fa-circle-info'></i></a></td>
            </tr>";
        }
        ?>
    </tbody>
</table>

                </div>
            </div>
        </div>
    </div>

<nav class="d-flex justify-content-between flex-column flex-sm-row align-items-center mt-2">
    <ul class="pagination">
        <?php if ($currentPage > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo buildUrl($playerId, $currentPage - 1, $matchesPerPage, $searchQuery, $currentTab); ?>">Précédent</a>
            </li>
        <?php endif; ?>

        <?php
        // Afficher la première page
        if ($totalPages > 1): ?>
            <li class="page-item <?php echo ($currentPage == 1 ? 'active' : ''); ?>">
                <a class="page-link" href="<?php echo buildUrl($playerId, 1, $matchesPerPage, $searchQuery, $_GET['tab']); ?>">1</a>
            </li>
        <?php endif; ?>

        <?php if ($currentPage > 3): ?>
            <li class="page-item disabled d-none d-sm-inline"><span class="page-link">...</span></li>
        <?php endif; ?>

        <?php 
        $startPage = max(2, $currentPage - 1); // page précédente
        $endPage = min($totalPages - 1, $currentPage + 1); // page suivante

        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?php echo ($i == $currentPage ? 'active' : ''); ?>">
                <a class="page-link" href="<?php echo buildUrl($playerId, $i, $matchesPerPage, $searchQuery, $_GET['tab']); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <!-- Afficher les ellipses si nécessaire (sauf sur mobile) -->
        <?php if ($currentPage < $totalPages - 2): ?>
            <li class="page-item disabled d-none d-sm-inline"><span class="page-link">...</span></li>
        <?php endif; ?>

        <!-- Afficher la dernière page -->
        <?php if ($totalPages > 1): ?>
            <li class="page-item <?php echo ($currentPage == $totalPages ? 'active' : ''); ?>">
                <a class="page-link" href="<?php echo buildUrl($playerId, $totalPages, $matchesPerPage, $searchQuery, $_GET['tab']); ?>"><?php echo $totalPages; ?></a>
            </li>
        <?php endif; ?>

        <?php if ($currentPage < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo buildUrl($playerId, $currentPage + 1, $matchesPerPage, $searchQuery, $_GET['tab']); ?>">Suivant</a>
            </li>
        <?php endif; ?>
    </ul>

    <form class="mb-3 mt-3 mt-sm-0" method="GET" action="player_profile.php">
        <input type="hidden" name="id" value="<?php echo $playerId; ?>">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>"> <!-- Champ caché pour le tab -->
        <select name="matchesPerPage" class="form-select" onchange="this.form.submit()">
            <option value="10" <?php if ($matchesPerPage == 10) echo 'selected'; ?>>10</option>
            <option value="20" <?php if ($matchesPerPage == 20) echo 'selected'; ?>>20</option>
                <option value="50" <?php if ($matchesPerPage == 50) echo 'selected'; ?>>50</option>
            </select>
        </form>
    </nav>
    </div>
</div>
<script>
    document.querySelectorAll('.nav-link').forEach(function(tab) {
        tab.addEventListener('click', function(event) {
            event.preventDefault();
            
            const selectedTab = tab.getAttribute('href').substring(1); // Récupère l'ID de l'onglet sans le '#'
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('tab', selectedTab); // Ajoute ou met à jour le paramètre 'tab'
            
            // Change l'URL sans recharger la page
            history.pushState({}, '', currentUrl);
            
            // Active l'onglet correspondant
            const targetTab = new bootstrap.Tab(tab);
            targetTab.show();
        });
    });

    // Ouvre l'onglet correspondant au chargement de la page
    window.addEventListener('load', function() {
        const currentUrl = new URL(window.location.href);
        const selectedTab = currentUrl.searchParams.get('tab');
        
        if (selectedTab) {
            const targetLink = document.querySelector(`a[href="#${selectedTab}"]`);
            if (targetLink) {
                const targetTab = new bootstrap.Tab(targetLink);
                targetTab.show();
            }
        }
    });
</script>

<?php include('footer.php'); ?>