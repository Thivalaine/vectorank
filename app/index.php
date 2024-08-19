<?php
    $pageTitle = "Liste des joueurs";
    include('header.php'); 
?>

<div class="container">
    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mb-3">
        <h1 class="header-title">Liste des joueurs</h1>
        <div class="d-flex flex-row flex-md-row align-items-center justify-content-end gap-2">
            <a href="tournaments.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> Tournoi
            </a>
            <a href="add_match.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> Match
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered">
            <thead class="thead-dark">
            <tr>
                <th>Classement</th>
                <th>Nom</th>
                <th>MMR Actuel</th>
                <th>Rang Actuel</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php
            include 'db.php';
            // Modifiez la requête pour trier par MMR
            $result = $conn->query("SELECT *, CASE 
                        WHEN mmr >= 4000 THEN 'Challenger' 
                        WHEN mmr >= 3000 THEN 'Grandmaster' 
                        WHEN mmr >= 2500 THEN 'Master' 
                        WHEN mmr >= 2000 THEN 'Diamond' 
                        WHEN mmr >= 1750 THEN 'Emerald' 
                        WHEN mmr >= 1500 THEN 'Platinum' 
                        WHEN mmr >= 1250 THEN 'Gold' 
                        WHEN mmr >= 1000 THEN 'Silver' 
                        WHEN mmr >= 500 THEN 'Bronze' 
                        ELSE 'Iron' 
                    END AS rank FROM players ORDER BY mmr DESC");

            while ($row = $result->fetch_assoc()) {
                // Calcul de la différence de MMR
                $mmrDifference = $row['mmr'] - $row['old_mmr'];
                $trendIcon = '';
                $trendBadgeClass = ''; // Classe de base pour le badge
                $formattedDifference = '';

                // Vérification des différences
                if (!is_null($row['old_mmr']) && !is_null($row['mmr'])) {
                    if ($mmrDifference > 0) {
                        $trendIcon = '<i class="fas fa-arrow-up"></i>';
                        $trendBadgeClass = 'badge bg-success'; // Badge vert pour montante
                        $formattedDifference = "+$mmrDifference"; // Différence positive avec un signe +
                    } elseif ($mmrDifference < 0) {
                        $trendIcon = '<i class="fas fa-arrow-down"></i>';
                        $trendBadgeClass = 'badge bg-danger'; // Badge rouge pour descendante
                        $formattedDifference = "$mmrDifference"; // Différence négative sans le signe
                    }
                }

                // Préparation de l'affichage de MMR
                $mmrDisplay = "{$row['mmr']}"; // Valeur par défaut
                // Vérification si MMR actuel n'est pas égal à l'ancien MMR
                if ($mmrDifference !== 0 && intval($mmrDifference) !== intval($row['mmr'])) { // Afficher le badge seulement si la différence est non nulle
                    $mmrDisplay .= " <span class='$trendBadgeClass'>$trendIcon $formattedDifference</span>";
                }

                // Calcul de la différence de classement
                if (is_null($row['old_ranking']) || is_null($row['new_ranking'])) {
                    $rankingDisplay = "{$row['new_ranking']}"; // Pas d'affichage de badge si l'un des classements est nul
                } else {
                    $rankingDifference = $row['new_ranking'] - $row['old_ranking'];

                    if ($rankingDifference < 0) { // Si old_ranking est supérieur à new_ranking
                        $rankingTrendIcon = '<i class="fas fa-arrow-up"></i>'; // Icône pour un gain de classement
                        $rankingBadgeClass = 'badge bg-success'; // Badge vert pour un gain
                        $formattedRankingDifference = "+".abs($rankingDifference); // Affichage avec signe + et valeur positive
                    } elseif ($rankingDifference > 0) { // Si old_ranking est inférieur à new_ranking
                        $rankingTrendIcon = '<i class="fas fa-arrow-down"></i>'; // Icône pour une perte de classement
                        $rankingBadgeClass = 'badge bg-danger'; // Badge rouge pour une perte
                        $formattedRankingDifference = "-".abs($rankingDifference); // Affichage sans signe
                    } else {
                        $rankingTrendIcon = ''; // Aucun changement
                        $rankingBadgeClass = ''; // Pas de badge
                        $formattedRankingDifference = ''; // Pas d'affichage
                    }

                    // Préparation de la cellule de classement
                    $rankingDisplay = "{$row['new_ranking']}"; // Valeur par défaut
                    // Afficher le badge seulement si la différence est non nulle
                    if ($rankingDifference !== 0 && intval($rankingDifference) !== intval($row['new_ranking'])) {
                        $rankingDisplay .= " <span class='$rankingBadgeClass'>$rankingTrendIcon $formattedRankingDifference</span>";
                    }
                }


                    // Affichage des données avec l'icône de tendance et le badge de différence de MMR et de classement
                    echo "<tr>
                        <td>$rankingDisplay</td>
                        <td>{$row['name']}</td>
                        <td>$mmrDisplay</td>
                        <td>
                            <img class='rank-img' src='assets/{$row['rank']}.svg' alt='{$row['rank']}' onerror='handleImageError(this)' />
                            <span class='rank-text'>{$row['rank']}</span>
                        </td>
                        <td><a href='player_profile.php?id={$row['id']}' class='btn btn-info'><i class='fa-regular fa-address-card'></i></a></td>
                    </tr>";
                }

            $conn->close();
            ?>
            </tbody>
        </table>
    </div>

    <button class="btn btn-secondary mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#mmrDetails" aria-expanded="false" aria-controls="mmrDetails">
        Détails du calcul du MMR et des rangs
    </button>
    <div class="collapse collapse-content" id="mmrDetails">
        <div class="card card-body">
            <h2>Calcul du MMR</h2>
            <p>Le MMR (Matchmaking Rating) est calculé en fonction des résultats des matchs, de la différence de MMR entre les joueurs, et d'un facteur supplémentaire basé sur cette différence. Voici comment cela fonctionne :</p>
            
            <h3>Étapes de calcul du MMR</h3>
            <ol>
                <li><strong>Récupération des MMR actuels des joueurs</strong> :
                    <ul>
                        <li>Ancien MMR du joueur 1 : <code>$old_mmr1</code></li>
                        <li>Ancien MMR du joueur 2 : <code>$old_mmr2</code></li>
                    </ul>
                </li>
                <li><strong>Détermination des valeurs observées</strong> :
                    <ul>
                        <li>Si le joueur 1 gagne : <code>$observed1 = 1</code></li>
                        <li>Si le joueur 1 perd : <code>$observed1 = 0</code></li>
                        <li>Si le joueur 2 gagne : <code>$observed2 = 1</code></li>
                        <li>Si le joueur 2 perd : <code>$observed2 = 0</code></li>
                    </ul>
                </li>
                <li><strong>Calcul des probabilités de victoire</strong> :
                    <ul>
                        <li>Probabilité de victoire du joueur 1 : <code>$probability1 = 1 / (1 + 10 ^ (($old_mmr2 - $old_mmr1) / 400))</code></li>
                        <li>Probabilité de victoire du joueur 2 : <code>$probability2 = 1 / (1 + 10 ^ (($old_mmr1 - $old_mmr2) / 400))</code></li>
                    </ul>
                </li>
                <li><strong>Marge de victoire</strong> :
                    <ul>
                        <li>Marge de victoire : <code>$victory_margin = abs($score1 - $score2)</code></li>
                    </ul>
                </li>
                <li><strong>Facteur de victoire</strong> :
                    <ul>
                        <li>Facteur de victoire : <code>$victory_factor = 1 + ($victory_margin / 10)</code></li>
                    </ul>
                </li>
                <li>
                    <strong>Différence d'ELO</strong> :
                    <ul>
                        <li>Différence d'ELO : <code>$elo_difference = abs($old_mmr1 - $old_mmr2)</code></li>
                    </ul>
                </li>
                <li><strong>Facteur de différence d'ELO</strong> :
                    <ul>
                        <li>Coefficient basé sur la différence d'ELO : <code>$elo_difference_factor = log(1 + $elo_difference / 400)</code></li>
                    </ul>
                </li>
                <li><strong>Points supplémentaires</strong> :
                    <ul>
                        <li>Points supplémentaires : <code>$extra_points = $victory_margin</code></li>
                    </ul>
                </li>
                <li><strong>Calcul des nouveaux MMR</strong> :
                    <ul>
                        <li>Si le joueur 1 gagne : <code>$new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $probability1) * $victory_factor * $elo_difference_factor + $extra_points + WS + TP (si Tournoi))</code></li>
                        <li>Si le joueur 1 perd : <code>$new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $probability1) * $victory_factor * $elo_difference_factor - $extra_points + TP (si Tournoi))</code></li>
                        <li>Si le joueur 2 gagne : <code>$new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $probability2) * $victory_factor * $elo_difference_factor + $extra_points + WS + TP)</code></li>
                        <li>Si le joueur 2 perd : <code>$new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $probability2) * $victory_factor * $elo_difference_factor - $extra_points + TP (si Tournoi))</code></li>
                    </ul>
                </li>
            </ol>

            <h2>Séries de victoires (Win Streaks)</h2>
            <p>Une série de victoires (ou WS) est définie comme une série consécutive de matchs gagnés par un joueur. Les win streaks affectent les calculs de MMR de manière significative :</p>
            <strong>Pour chaque victoire consécutive, le joueur peut recevoir un bonus supplémentaire de points comme suit :</strong>
            <ul>
                <li>1 point pour une série de victoires < 5</li>
                <li>2 points pour une série de victoires < 10</li>
                <li>3 points pour une série de victoires < 15</li>
                <li>4 points pour une série de victoires < 20</li>
                <li>5 points pour une série de victoires > 20</li>
            </ul>

            <h2>Tournois (Tournament Points)</h2>
            <p>Les points de tournoi (ou TP) sont déterminés en fonction du nombre total de joueurs dans le tournoi et de la phase dans laquelle se déroule le match. Cela affecte le nombre de points attribués au gagnant et au perdant.</p>
            <strong>Points de base attribués selon le nombre de joueurs :</strong>
            <ul>
                <li>6 points pour un tournoi à 4 joueurs</li>
                <li>9 points pour un tournoi à 8 joueurs</li>
                <li>12 points pour un tournoi à 16 joueurs</li>
            </ul>
            <strong>Points en fonction de la phase du tournoi :</strong>
            <ul>
                <li>Huitième de finale : le gagnant reçoit 25% des points de base, et le perdant en reçoit 10%</li>
                <li>Quart de finale : le gagnant reçoit 50% des points de base, et le perdant en reçoit 25%</li>
                <li>Demi-finale : le gagnant reçoit 75% des points de base, et le perdant en reçoit 50%</li>
                <li>Finale : le gagnant reçoit les points de base, et le perdant en reçoit 75%</li>
            </ul>

            <h2>Rangs</h2>
            <p>Les rangs sont attribués en fonction du MMR comme suit :</p>
            <ul>
                <li><img src="assets/Challenger.svg" alt="Challenger"> Challenger: MMR >= 4000</li>
                <li><img src="assets/Grandmaster.svg" alt="Grandmaster"> Grandmaster: MMR >= 3000</li>
                <li><img src="assets/Master.svg" alt="Master"> Master: MMR >= 2500</li>
                <li><img src="assets/Diamond.svg" alt="Diamond"> Diamond: MMR >= 2000</li>
                <li><img src="assets/Emerald.svg" alt="Emerald"> Emerald: MMR >= 1750</li>
                <li><img src="assets/Platinum.svg" alt="Platinum"> Platinum: MMR >= 1500</li>
                <li><img src="assets/Gold.svg" alt="Gold"> Gold: MMR >= 1250</li>
                <li><img src="assets/Silver.svg" alt="Silver"> Silver: MMR >= 1000</li>
                <li><img src="assets/Bronze.svg" alt="Bronze"> Bronze: MMR >= 500</li>
                <li><img src="assets/Iron.svg" alt="Iron"> Iron: MMR &lt; 500</li>
            </ul>
        </div>
    </div>

    <style>
        .table {
            margin-top: 20px;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
        .rank-img {
            max-width: 50px;
        }
        .rank-text {
            display: none;
        }
        .btn-info {
            background-color: #17a2b8;
            border: none;
        }
        .btn-info:hover {
            background-color: #138496;
        }
        .header-title {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #343a40;
        }
        .collapse-content img {
            max-width: 50px;
        }
        .collapse-content {
            margin-top: 20px;
        }
    </style>
    <script>
        function handleImageError(img) {
            var rankText = img.nextElementSibling;
            img.style.display = 'none';
            rankText.style.display = 'inline';
        }
    </script>
</div>

<?php include('footer.php'); ?>
