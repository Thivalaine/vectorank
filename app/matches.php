<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des matchs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <h1>Liste des matchs</h1>

    <!-- Formulaire pour sélectionner le tri -->
    <form method="GET" class="mb-3 d-flex align-items-center">
        <label for="orderBy" class="me-2">Trier par</label>
        <select name="orderBy" id="orderBy" class="form-select me-2" onchange="this.form.submit()">
            <option value="match_date" <?php echo (isset($_GET['orderBy']) && $_GET['orderBy'] == 'match_date') ? 'selected' : ''; ?>>Date du match</option>
            <option value="score1" <?php echo (isset($_GET['orderBy']) && $_GET['orderBy'] == 'score1') ? 'selected' : ''; ?>>Score Joueur 1</option>
            <option value="score2" <?php echo (isset($_GET['orderBy']) && $_GET['orderBy'] == 'score2') ? 'selected' : ''; ?>>Score Joueur 2</option>
            <option value="rank1" <?php echo (isset($_GET['orderBy']) && $_GET['orderBy'] == 'rank1') ? 'selected' : ''; ?>>Rang Joueur 1</option>
            <option value="rank2" <?php echo (isset($_GET['orderBy']) && $_GET['orderBy'] == 'rank2') ? 'selected' : ''; ?>>Rang Joueur 2</option>
        </select>
        
        <label for="orderDir" class="me-2">Direction</label>
        <select name="orderDir" id="orderDir" class="form-select" onchange="this.form.submit()">
            <option value="ASC" <?php echo (isset($_GET['orderDir']) && $_GET['orderDir'] == 'ASC') ? 'selected' : ''; ?>>Ascendant</option>
            <option value="DESC" <?php echo (isset($_GET['orderDir']) && $_GET['orderDir'] == 'DESC') ? 'selected' : ''; ?>>Descendant</option>
        </select>
    </form>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>ID Match</th>
            <th>Joueur 1</th>
            <th>Rang Joueur 1</th>
            <th>Score Joueur 1</th>
            <th>Joueur 2</th>
            <th>Rang Joueur 2</th>
            <th>Score Joueur 2</th>
            <th>Ancien MMR Joueur 1</th>
            <th>Nouveau MMR Joueur 1</th>
            <th>Ancien MMR Joueur 2</th>
            <th>Nouveau MMR Joueur 2</th>
            <th>Date du match</th>
        </tr>
        </thead>
        <tbody>
        <?php
        include 'db.php';

        // Pagination
        $matchesPerPage = isset($_GET['matchesPerPage']) ? (int)$_GET['matchesPerPage'] : 10; // Nombre d'éléments par page
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Page actuelle
        $offset = ($currentPage - 1) * $matchesPerPage; // Décalage pour la requête SQL

        // Critère de tri
        $orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'match_date'; // Critère de tri par défaut
        $orderDir = isset($_GET['orderDir']) ? $_GET['orderDir'] : 'DESC'; // Direction de tri par défaut

        // Récupérer le nombre total de matchs
        $totalMatchesResult = $conn->query("SELECT COUNT(*) as total FROM matches");
        $totalMatches = $totalMatchesResult->fetch_assoc()['total'];
        $totalPages = ceil($totalMatches / $matchesPerPage); // Nombre total de pages

        // Construire la requête avec ORDER BY selon le choix de l'utilisateur
        $orderByColumn = $orderBy;

        // Si l'ordre est par rang, le MMR doit être calculé dans une sous-requête
        if ($orderBy === 'rank1') {
            $orderByColumn = "(CASE 
                                WHEN p1.mmr >= 5000 THEN 'Challenger'
                                WHEN p1.mmr >= 4000 THEN 'Grandmaster'
                                WHEN p1.mmr >= 3500 THEN 'Master'
                                WHEN p1.mmr >= 3000 THEN 'Diamond'
                                WHEN p1.mmr >= 2500 THEN 'Emerald'
                                WHEN p1.mmr >= 2000 THEN 'Platinum'
                                WHEN p1.mmr >= 1500 THEN 'Gold'
                                WHEN p1.mmr >= 1000 THEN 'Silver'
                                WHEN p1.mmr >= 500 THEN 'Bronze'
                                ELSE 'Iron'
                             END)";
        } elseif ($orderBy === 'rank2') {
            $orderByColumn = "(CASE 
                                WHEN p2.mmr >= 5000 THEN 'Challenger'
                                WHEN p2.mmr >= 4000 THEN 'Grandmaster'
                                WHEN p2.mmr >= 3500 THEN 'Master'
                                WHEN p2.mmr >= 3000 THEN 'Diamond'
                                WHEN p2.mmr >= 2500 THEN 'Emerald'
                                WHEN p2.mmr >= 2000 THEN 'Platinum'
                                WHEN p2.mmr >= 1500 THEN 'Gold'
                                WHEN p2.mmr >= 1000 THEN 'Silver'
                                WHEN p2.mmr >= 500 THEN 'Bronze'
                                ELSE 'Iron'
                             END)";
        }

        // Récupérer les matchs avec LIMIT, OFFSET et ORDER BY
        $matches = $conn->query("SELECT m.*, p1.name AS player1_name, p1.mmr AS mmr1, p2.name AS player2_name, p2.mmr AS mmr2 
                                  FROM matches m 
                                  JOIN players p1 ON m.player1 = p1.id 
                                  JOIN players p2 ON m.player2 = p2.id
                                  ORDER BY $orderByColumn $orderDir, m.match_date $orderDir
                                  LIMIT $offset, $matchesPerPage");

        if (!$matches) {
            die("Erreur de requête SQL : " . $conn->error);
        }

        while ($match = $matches->fetch_assoc()) {
            // Calculer le rang des joueurs
            $rank1 = getRank($match['mmr1']);
            $rank2 = getRank($match['mmr2']);
            echo "<tr>
                <td>{$match['id']}</td>
                <td>{$match['player1_name']}</td>
                <td>{$rank1}</td>
                <td>{$match['score1']}</td>
                <td>{$match['player2_name']}</td>
                <td>{$rank2}</td>
                <td>{$match['score2']}</td>
                <td>{$match['old_mmr1']}</td>
                <td>{$match['new_mmr1']}</td>
                <td>{$match['old_mmr2']}</td>
                <td>{$match['new_mmr2']}</td>
                <td>{$match['match_date']}</td>
            </tr>";
        }
        $conn->close();

        // Fonction pour déterminer le rang en fonction du MMR
        function getRank($mmr) {
            if ($mmr >= 5000) {
                return "Challenger";
            } elseif ($mmr >= 4000) {
                return "Grandmaster";
            } elseif ($mmr >= 3500) {
                return "Master";
            } elseif ($mmr >= 3000) {
                return "Diamond";
            } elseif ($mmr >= 2500) {
                return "Emerald";
            } elseif ($mmr >= 2000) {
                return "Platinum";
            } elseif ($mmr >= 1500) {
                return "Gold";
            } elseif ($mmr >= 1000) {
                return "Silver";
            } elseif ($mmr >= 500) {
                return "Bronze";
            } else {
                return "Iron";
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
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&matchesPerPage=<?php echo $matchesPerPage; ?>&orderBy=<?php echo $orderBy; ?>&orderDir=<?php echo $orderDir; ?>">Précédent</a>
                    </li>
                <?php endif; ?>

                <?php
                // Afficher les numéros de pages avec ellipses
                $maxDisplayPages = 5; // Nombre maximum de pages à afficher

                // Afficher la première page
                if ($totalPages > 1) {
                    echo '<li class="page-item'.($currentPage === 1 ? ' active' : '').'"><a class="page-link" href="?page=1&matchesPerPage='.$matchesPerPage.'&orderBy='.$orderBy.'&orderDir='.$orderDir.'">1</a></li>';
                }

                // Afficher les ellipses si la page actuelle est au-delà de 3
                if ($currentPage > 3) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                // Afficher les pages proches de la page actuelle
                for ($i = max(2, $currentPage - 1); $i <= min($totalPages - 1, $currentPage + 1); $i++) {
                    echo '<li class="page-item'.($i === $currentPage ? ' active' : '').'"><a class="page-link" href="?page='.$i.'&matchesPerPage='.$matchesPerPage.'&orderBy='.$orderBy.'&orderDir='.$orderDir.'">'.$i.'</a></li>';
                }

                // Afficher les ellipses si la page actuelle est près de l'avant-dernière page
                if ($currentPage < $totalPages - 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }

                // Afficher la dernière page
                if ($totalPages > 1) {
                    echo '<li class="page-item'.($currentPage === $totalPages ? ' active' : '').'"><a class="page-link" href="?page='.$totalPages.'&matchesPerPage='.$matchesPerPage.'&orderBy='.$orderBy.'&orderDir='.$orderDir.'">'.$totalPages.'</a></li>';
                }
                ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&matchesPerPage=<?php echo $matchesPerPage; ?>&orderBy=<?php echo $orderBy; ?>&orderDir=<?php echo $orderDir; ?>">Suivant</a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Formulaire pour sélectionner le nombre d'éléments par page -->
            <form method="GET" class="d-flex align-items-center">
                <label for="matchesPerPage" class="me-2">Limitations</label>
                <select name="matchesPerPage" id="matchesPerPage" class="form-select" onchange="this.form.submit()">
                    <option value="5" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 5) ? 'selected' : ''; ?>>5</option>
                    <option value="10" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 10) ? 'selected' : ''; ?>>10</option>
                    <option value="25" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 25) ? 'selected' : ''; ?>>25</option>
                    <option value="50" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 50) ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 100) ? 'selected' : ''; ?>>100</option>
                    <option value="250" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 250) ? 'selected' : ''; ?>>250</option>
                    <option value="500" <?php echo (isset($_GET['matchesPerPage']) && $_GET['matchesPerPage'] == 500) ? 'selected' : ''; ?>>500</option>
                </select>
                <input type="hidden" name="page" value="<?php echo $currentPage; ?>"> <!-- Ajout du paramètre de page -->
                <input type="hidden" name="orderBy" value="<?php echo $orderBy; ?>"> <!-- Maintien du paramètre de tri -->
                <input type="hidden" name="orderDir" value="<?php echo $orderDir; ?>"> <!-- Maintien de la direction de tri -->
            </form>
        </div>
    </nav>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
