<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil du joueur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-5">
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

    if (!$player) {
        echo "<h2>Joueur non trouvé.</h2>";
        exit;
    }

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

    <h2>Liste des Matchs</h2>

    <form class="d-flex mb-4" method="GET" action="player_profile.php">
        <input type="hidden" name="id" value="<?php echo $playerId; ?>">
        <input class="form-control me-2" type="search" placeholder="Rechercher un joueur" aria-label="Search" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
        <button class="btn btn-outline-success" type="submit">Rechercher</button>
    </form>

    <table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Adversaire</th>
            <th>Score</th>
            <th>MMR</th>
            <th>MMR Adversaire</th>
            <th>Série de victoires</th>
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
                p1.mmr AS player1_mmr, 
                p2.name AS player2_name, 
                p2.mmr AS player2_mmr,
                IF(m.player1 = $playerId, m.points1, m.points2) AS player_points,
                IF(m.player1 = $playerId, m.win_streak_bonus1, m.win_streak_bonus2) AS player_bonus,
                IF(m.player1 = $playerId, m.points2, m.points1) AS opponent_points,
                IF(m.player1 = $playerId, m.win_streak_bonus2, m.win_streak_bonus1) AS opponent_bonus
            FROM matches m
            JOIN players p1 ON m.player1 = p1.id
            JOIN players p2 ON m.player2 = p2.id
            WHERE (m.player1 = $playerId OR m.player2 = $playerId) $searchCondition
            ORDER BY m.match_date DESC 
            LIMIT $matchesPerPage OFFSET $offset
        ";
        $matchesResult = $conn->query($matchesQuery);

        while ($match = $matchesResult->fetch_assoc()) {
            // Vérifiez si le joueur actuel est player1 ou player2
            $isPlayer1 = $match['player1'] == $playerId;
        
            $opponentId = $isPlayer1 ? $match['player2'] : $match['player1'];
            $opponentName = $isPlayer1 ? $match['player2_name'] : $match['player1_name'];
            $score = $isPlayer1 ? "{$match['score1']} - {$match['score2']}" : "{$match['score2']} - {$match['score1']}";
            $playerOldMMR = $isPlayer1 ? $match['old_mmr1'] : $match['old_mmr2'];
            $playerNewMMR = $isPlayer1 ? $match['new_mmr1'] : $match['new_mmr2'];
            $opponentOldMMR = $isPlayer1 ? $match['old_mmr2'] : $match['old_mmr1'];
            $opponentNewMMR = $isPlayer1 ? $match['new_mmr2'] : $match['new_mmr1'];
            $rowColor = ($isPlayer1 && $match['score1'] > $match['score2']) || (!$isPlayer1 && $match['score2'] > $match['score1']) ? 'table-success' : 'table-danger';
        
            // Afficher les points et les bonus de séries de victoires pour le joueur
            $pointsDisplayPlayer = '';
            if ($match['player_points'] != 0) {
                $pointsDisplayPlayer .= ($match['player_points'] > 0 ? "(+{$match['player_points']}" : "({$match['player_points']}");
                if (!empty($match['player_bonus'])) {
                    $pointsDisplayPlayer .= ", +{$match['player_bonus']} WS";
                }
                $pointsDisplayPlayer .= ")";
            }
        
            // Afficher les points et les bonus de séries de victoires pour l'adversaire
            $pointsDisplayOpponent = '';
            if ($match['opponent_points'] != 0) {
                $pointsDisplayOpponent .= ($match['opponent_points'] > 0 ? "(+{$match['opponent_points']}" : "({$match['opponent_points']}");
                if (!empty($match['opponent_bonus'])) {
                    $pointsDisplayOpponent .= ", +{$match['opponent_bonus']} WS";
                }
                $pointsDisplayOpponent .= ")";
            }
        
            // Ne rien afficher si les points sont nuls
            $pointsDisplayPlayer = $pointsDisplayPlayer ? $pointsDisplayPlayer : '';
            $pointsDisplayOpponent = $pointsDisplayOpponent ? $pointsDisplayOpponent : '';

            // Déterminer si le joueur est en série de victoires
            if ($isPlayer1 && $match['score1'] > $match['score2'] || !$isPlayer1 && $match['score2'] > $match['score1']) {
                $winStreak++;
            } else {
                $winStreak = 0; // Réinitialiser si le joueur perd
            }

            // Afficher la série de victoires dans la colonne correspondante
            $winStreakDisplay = $winStreak > 0 ? "<span class='badge bg-warning'><i class='fas fa-fire'></i> $winStreak</span>" : "<span class='badge bg-secondary'>Aucune</span>";

            echo "<tr class='$rowColor'>
                <td>{$match['match_date']}</td>
                <td>" . htmlspecialchars($opponentName) . "</td>
                <td>$score</td>
                <td>$playerOldMMR → <strong>$playerNewMMR</strong> <span>$pointsDisplayPlayer</span></td>
                <td>$opponentOldMMR → <strong>$opponentNewMMR</strong> <span>$pointsDisplayOpponent</span></td>
                <td>$winStreakDisplay</td> <!-- Affichage de la série de victoires -->
            </tr>";
        }
        ?>
    </tbody>
</table>

<nav class="d-flex justify-content-between">
    <ul class="pagination">
        <?php if ($currentPage > 1): ?>
            <li class="page-item">
                <a class="page-link" href="player_profile.php?id=<?php echo $playerId; ?>&page=<?php echo $currentPage - 1; ?>&matchesPerPage=<?php echo $matchesPerPage; ?>&search=<?php echo htmlspecialchars($searchQuery); ?>">Précédent</a>
            </li>
        <?php endif; ?>

        <?php
        // Afficher la première page
        if ($totalPages > 1) {
            echo '<li class="page-item ' . ($currentPage == 1 ? 'active' : '') . '">
                    <a class="page-link" href="player_profile.php?id=' . $playerId . '&page=1&matchesPerPage=' . $matchesPerPage . '&search=' . htmlspecialchars($searchQuery) . '">1</a>
                  </li>';
        }

        // Afficher les ellipses si nécessaire
        if ($currentPage > 3) {
            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        // Calculer les pages à afficher autour de la page actuelle
        $startPage = max(2, $currentPage - 1); // page précédente
        $endPage = min($totalPages - 1, $currentPage + 1); // page suivante

        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?php if ($i == $currentPage) echo 'active'; ?>">
                <a class="page-link" href="player_profile.php?id=<?php echo $playerId; ?>&page=<?php echo $i; ?>&matchesPerPage=<?php echo $matchesPerPage; ?>&search=<?php echo htmlspecialchars($searchQuery); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <?php
        // Afficher les ellipses si nécessaire
        if ($currentPage < $totalPages - 2) {
            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        // Afficher la dernière page
        if ($totalPages > 1) {
            echo '<li class="page-item ' . ($currentPage == $totalPages ? 'active' : '') . '">
                    <a class="page-link" href="player_profile.php?id=' . $playerId . '&page=' . $totalPages . '&matchesPerPage=' . $matchesPerPage . '&search=' . htmlspecialchars($searchQuery) . '">' . $totalPages . '</a>
                  </li>';
        }
        ?>

        <?php if ($currentPage < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="player_profile.php?id=<?php echo $playerId; ?>&page=<?php echo $currentPage + 1; ?>&matchesPerPage=<?php echo $matchesPerPage; ?>&search=<?php echo htmlspecialchars($searchQuery); ?>">Suivant</a>
            </li>
        <?php endif; ?>
    </ul>

    <form class="mb-3" method="GET" action="player_profile.php">
        <input type="hidden" name="id" value="<?php echo $playerId; ?>">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
        <select name="matchesPerPage" class="form-select" onchange="this.form.submit()">
            <option value="10" <?php if ($matchesPerPage == 10) echo 'selected'; ?>>10</option>
            <option value="20" <?php if ($matchesPerPage == 20) echo 'selected'; ?>>20</option>
            <option value="50" <?php if ($matchesPerPage == 50) echo 'selected'; ?>>50</option>
        </select>
    </form>
</nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
