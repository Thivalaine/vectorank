<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil du joueur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <?php
    include 'db.php';
    $playerId = $_GET['id'];

    // Récupérer les informations du joueur
    $result = $conn->query("SELECT * FROM players WHERE id = $playerId");
    
    // Vérifier si la requête SQL a réussi
    if (!$result) {
        echo "<h2>Erreur de requête SQL : " . $conn->error . "</h2>";
        exit;
    }

    $player = $result->fetch_assoc();

    // Vérifier si le joueur existe
    if (!$player) {
        echo "<h2>Joueur non trouvé.</h2>";
        exit;
    }

    // Compter les victoires et défaites
    $winsResult = $conn->query("SELECT COUNT(*) as wins FROM matches WHERE (player1 = $playerId AND score1 > score2) OR (player2 = $playerId AND score2 > score1)");
    if (!$winsResult) {
        echo "<h2>Erreur de requête SQL : " . $conn->error . "</h2>";
        exit;
    }
    $winsCount = $winsResult->fetch_assoc()['wins'];

    $lossesResult = $conn->query("SELECT COUNT(*) as losses FROM matches WHERE (player1 = $playerId AND score1 < score2) OR (player2 = $playerId AND score2 < score1)");
    if (!$lossesResult) {
        echo "<h2>Erreur de requête SQL : " . $conn->error . "</h2>";
        exit;
    }
    $lossesCount = $lossesResult->fetch_assoc()['losses'];

    // Calculer le ratio
    $totalGames = $winsCount + $lossesCount;
    $winRatio = $totalGames > 0 ? ($winsCount / $totalGames) * 100 : 0; // pourcentage de victoires

    // Pagination
    $matchesPerPage = isset($_GET['matchesPerPage']) ? (int)$_GET['matchesPerPage'] : 10; // Nombre d'éléments par page
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Page actuelle
    $offset = ($currentPage - 1) * $matchesPerPage; // Décalage pour la requête SQL

    // Compter le nombre total de matchs
    $totalMatchesResult = $conn->query("SELECT COUNT(*) as total FROM matches WHERE player1 = $playerId OR player2 = $playerId");
    if (!$totalMatchesResult) {
        echo "<h2>Erreur de requête SQL : " . $conn->error . "</h2>";
        exit;
    }
    $totalMatches = $totalMatchesResult->fetch_assoc()['total'];
    $totalPages = ceil($totalMatches / $matchesPerPage); // Nombre total de pages
    ?>
    
    <h1>Profil de <?php echo htmlspecialchars($player['name']); ?></h1>
    <p><strong>ID:</strong> <?php echo $player['id']; ?></p>
    <p><strong>MMR Actuel:</strong> <?php echo $player['mmr']; ?></p>
    <p><strong>Rang Actuel:</strong> <?php echo htmlspecialchars($player['rank']); ?></p>
    <p><strong>Victoire(s):</strong> <?php echo $winsCount; ?></p>
    <p><strong>Défaite(s):</strong> <?php echo $lossesCount; ?></p>
    <p><strong>Ratio de Victoires:</strong> <?php echo number_format($winRatio, 2) . '%'; ?></p> <!-- Afficher le ratio -->
    <table class="table table-striped">
        <thead>
        <tr>
            <th>ID Match</th>
            <th>Joueur 1</th>
            <th>Score Joueur 1</th>
            <th>Score Joueur 2</th>
            <th>Joueur 2</th>
            <th>Ancien MMR Joueur 1</th>
            <th>Nouveau MMR Joueur 1</th>
            <th>Ancien MMR Joueur 2</th>
            <th>Nouveau MMR Joueur 2</th>
            <th>Date du match</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $matches = $conn->query("SELECT * FROM matches WHERE player1 = $playerId OR player2 = $playerId LIMIT $offset, $matchesPerPage");
        if (!$matches) {
            echo "<tr><td colspan='10'>Erreur de requête SQL : " . $conn->error . "</td></tr>";
        } else {
            while ($match = $matches->fetch_assoc()) {
                $player1Name = $conn->query("SELECT name FROM players WHERE id = {$match['player1']}")->fetch_assoc()['name'];
                $player2Name = $conn->query("SELECT name FROM players WHERE id = {$match['player2']}")->fetch_assoc()['name'];

                // Vérifier si le joueur a gagné
                if (($match['player1'] == $playerId && $match['score1'] > $match['score2']) || 
                    ($match['player2'] == $playerId && $match['score2'] > $match['score1'])) {
                    $rowClass = "table-success"; // Classe pour victoire
                } else {
                    $rowClass = "table-danger"; // Classe pour défaite
                }

                echo "<tr class='{$rowClass}'>
                    <td>{$match['id']}</td>
                    <td>{$player1Name}</td>
                    <td>{$match['score1']}</td>
                    <td>{$match['score2']}</td>
                    <td>{$player2Name}</td>
                    <td>{$match['old_mmr1']}</td>
                    <td>{$match['new_mmr1']}</td>
                    <td>{$match['old_mmr2']}</td>
                    <td>{$match['new_mmr2']}</td>
                    <td>{$match['match_date']}</td>
                </tr>";
            }
        }
        ?>
        </tbody>
    </table>

    <!-- Navigation de la pagination -->
    <nav aria-label="Page navigation">
        <div class="d-flex justify-content-between align-items-center">
            <ul class="pagination">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?php echo $playerId; ?>&page=<?php echo $currentPage - 1; ?>&matchesPerPage=<?php echo $matchesPerPage; ?>">Précédent</a>
                    </li>
                <?php endif; ?>

                <?php
                // Afficher les numéros de pages avec ellipses
                $maxDisplayPages = 5; // Nombre maximum de pages à afficher

                // Afficher la première page
                if ($totalPages > 1) {
                    echo '<li class="page-item'.($currentPage === 1 ? ' active' : '').'"><a class="page-link" href="?id='.$playerId.'&page=1&matchesPerPage='.$matchesPerPage.'">1</a></li>';
                }

                // Afficher les ellipses si la page actuelle est au-delà de 3
                if ($currentPage > 3) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                // Afficher les pages proches de la page actuelle
                for ($i = max(2, $currentPage - 1); $i <= min($totalPages - 1, $currentPage + 1); $i++) {
                    echo '<li class="page-item'.($i === $currentPage ? ' active' : '').'"><a class="page-link" href="?id='.$playerId.'&page='.$i.'&matchesPerPage='.$matchesPerPage.'">'.$i.'</a></li>';
                }

                // Afficher les ellipses si la page actuelle est près de l'avant-dernière page
                if ($currentPage < $totalPages - 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                // Afficher la dernière page
                if ($totalPages > 1) {
                    echo '<li class="page-item'.($currentPage === $totalPages ? ' active' : '').'"><a class="page-link" href="?id='.$playerId.'&page='.$totalPages.'&matchesPerPage='.$matchesPerPage.'">'.$totalPages.'</a></li>';
                }
                ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?php echo $playerId; ?>&page=<?php echo $currentPage + 1; ?>&matchesPerPage=<?php echo $matchesPerPage; ?>">Suivant</a>
                    </li>
                <?php endif; ?>
            </ul>
            <!-- Formulaire pour sélectionner le nombre d'éléments par page -->
            <form method="GET" class="mb-3 d-flex align-items-center">
                <input type="hidden" name="id" value="<?php echo $playerId; ?>"> <!-- Garder l'ID du joueur -->
                <input type="hidden" name="page" value="<?php echo $currentPage; ?>"> <!-- Garder la page actuelle -->
                <label for="matchesPerPage" class="me-2">Limitations</label>
                <select name="matchesPerPage" id="matchesPerPage" class="form-select" onchange="this.form.submit()">
                    <option value="10" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 10) ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 25) ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 50) ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 100) ? 'selected' : ''; ?>>100</option>
                    <option value="250" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 250) ? 'selected' : ''; ?>>250</option>
                    <option value="500" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 500) ? 'selected' : ''; ?>>500</option>
                </select>
            </form>
        </div>
    </nav>
    
    <a href="index.php" class="btn btn-secondary mt-3">Retour à la liste des joueurs</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
