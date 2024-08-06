<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil du joueur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

    // Obtenir le plus haut MMR atteint par le joueur
    $highestMMRResult = $conn->query("
        SELECT MAX(GREATEST(
            IF(player1 = $playerId, old_mmr1, 0), 
            IF(player2 = $playerId, old_mmr2, 0), 
            IF(player1 = $playerId, new_mmr1, 0), 
            IF(player2 = $playerId, new_mmr2, 0)
        )) as highest_mmr 
        FROM matches 
        WHERE player1 = $playerId OR player2 = $playerId
    ");
    $highestMMR = $highestMMRResult ? $highestMMRResult->fetch_assoc()['highest_mmr'] : 0;

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

    // Calculer la plus grande série de victoires
    $matchesResult = $conn->query("
        SELECT * 
        FROM matches 
        WHERE player1 = $playerId OR player2 = $playerId
        ORDER BY match_date ASC
    ");
    $currentStreak = 0;
    $maxStreak = 0;
    if ($matchesResult) {
        while ($match = $matchesResult->fetch_assoc()) {
            if (($match['player1'] == $playerId && $match['score1'] > $match['score2']) || 
                ($match['player2'] == $playerId && $match['score2'] > $match['score1']) ) {
                $currentStreak++;
                if ($currentStreak > $maxStreak) {
                    $maxStreak = $currentStreak;
                }
            } else {
                $currentStreak = 0;
            }
        }
    }
    ?>

    <h1>Profil de <?php echo htmlspecialchars($player['name']); ?></h1>
    <p><strong>ID:</strong> <?php echo $player['id']; ?></p>
    <p><strong>MMR Actuel:</strong> <?php echo $player['mmr']; ?></p>
    <p><strong>Rang Actuel:</strong> <?php echo htmlspecialchars($player['rank']); ?></p>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Statistiques</h5>
            <ul class="list-group">
                <li class="list-group-item"><i class="fas fa-trophy"></i> <strong>Victoire(s):</strong> <?php echo $winsCount; ?></li>
                <li class="list-group-item"><i class="fas fa-times-circle"></i> <strong>Défaite(s):</strong> <?php echo $lossesCount; ?></li>
                <li class="list-group-item"><i class="fas fa-chart-pie"></i> <strong>Ratio de Victoires:</strong> <?php echo number_format($winRatio, 2) . '%'; ?></li>
                <li class="list-group-item"><i class="fas fa-futbol"></i> <strong>Matchs Disputés:</strong> <?php echo $totalGames; ?></li>
                <li class="list-group-item"><i class="fas fa-star"></i> <strong>Plus Grande Série de Victoires:</strong> <?php echo $maxStreak; ?></li>
                <li class="list-group-item"><i class="fas fa-trophy"></i> <strong>Plus Haut MMR Atteint:</strong> <?php echo $highestMMR; ?></li>
                <li class="list-group-item"><i class="fas fa-user-friends"></i> <strong>L'Opposant à éviter :</strong> <?php echo htmlspecialchars($mostLostAgainstName); ?></li>
                <li class="list-group-item"><i class="fas fa-user-check"></i> <strong>L'Opposant favori :</strong> <?php echo htmlspecialchars($mostWonAgainstName); ?></li>
            </ul>
        </div>
    </div>

    <h2>Liste des Matchs</h2>

    <form class="d-flex mb-3" method="GET" action="player_profile.php">
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
                <th>MMR (Ancien → <strong>Nouveau</strong>)</th>
                <th>MMR Adversaire (Ancien → <strong>Nouveau</strong>)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $matchesQuery = "SELECT * FROM matches WHERE (player1 = $playerId OR player2 = $playerId) $searchCondition ORDER BY match_date DESC LIMIT $matchesPerPage OFFSET $offset";
            $matchesResult = $conn->query($matchesQuery);

            while ($match = $matchesResult->fetch_assoc()) {
                $opponentId = $match['player1'] == $playerId ? $match['player2'] : $match['player1'];
                $opponentResult = $conn->query("SELECT name, mmr FROM players WHERE id = $opponentId");
                $opponent = $opponentResult->fetch_assoc();
                $score = ($match['player1'] == $playerId) ? "{$match['score1']} - {$match['score2']}" : "{$match['score2']} - {$match['score1']}";
                $playerOldMMR = $match['player1'] == $playerId ? $match['old_mmr1'] : $match['old_mmr2'];
                $playerNewMMR = $match['player1'] == $playerId ? $match['new_mmr1'] : $match['new_mmr2'];
                $opponentOldMMR = $match['player1'] != $playerId ? $match['old_mmr1'] : $match['old_mmr2'];
                $opponentNewMMR = $match['player1'] != $playerId ? $match['new_mmr1'] : $match['new_mmr2'];
                $rowColor = ($match['player1'] == $playerId && $match['score1'] > $match['score2']) || ($match['player2'] == $playerId && $match['score2'] > $match['score1']) ? 'table-success' : 'table-danger';
                echo "<tr class='$rowColor'>
                    <td>{$match['match_date']}</td>
                    <td>" . htmlspecialchars($opponent['name']) . "</td>
                    <td>$score</td>
                    <td>$playerOldMMR → <strong>$playerNewMMR</strong></td>
                    <td>$opponentOldMMR → <strong>$opponentNewMMR</strong></td>
                </tr>";
            }
            ?>
        </tbody>
    </table>

    <nav class="d-flex justify-content-between">
        <ul class="pagination">
            <?php if ($currentPage > 1): ?>
                <li class="page-item"><a class="page-link" href="player_profile.php?id=<?php echo $playerId; ?>&page=<?php echo $currentPage - 1; ?>&matchesPerPage=<?php echo $matchesPerPage; ?>&search=<?php echo htmlspecialchars($searchQuery); ?>">Précédent</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php if ($i == $currentPage) echo 'active'; ?>">
                    <a class="page-link" href="player_profile.php?id=<?php echo $playerId; ?>&page=<?php echo $i; ?>&matchesPerPage=<?php echo $matchesPerPage; ?>&search=<?php echo htmlspecialchars($searchQuery); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
                <li class="page-item"><a class="page-link" href="player_profile.php?id=<?php echo $playerId; ?>&page=<?php echo $currentPage + 1; ?>&matchesPerPage=<?php echo $matchesPerPage; ?>&search=<?php echo htmlspecialchars($searchQuery); ?>">Suivant</a></li>
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
