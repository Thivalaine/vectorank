<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des joueurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
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
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <h1 class="header-title">Liste des joueurs</h1>
    <table class="table table-hover table-bordered">
        <thead class="thead-dark">
        <tr>
            <th>ID</th>
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
                    WHEN mmr >= 5000 THEN 'Challenger' 
                    WHEN mmr >= 4000 THEN 'Grandmaster' 
                    WHEN mmr >= 3500 THEN 'Master' 
                    WHEN mmr >= 3000 THEN 'Diamond' 
                    WHEN mmr >= 2500 THEN 'Emerald' 
                    WHEN mmr >= 2000 THEN 'Platinum' 
                    WHEN mmr >= 1500 THEN 'Gold' 
                    WHEN mmr >= 1000 THEN 'Silver' 
                    WHEN mmr >= 500 THEN 'Bronze' 
                    ELSE 'Iron' 
                END AS rank FROM players ORDER BY mmr DESC");

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['name']}</td>
                <td>{$row['mmr']}</td>
                <td>
                    <img class='rank-img' src='assets/{$row['rank']}.svg' alt='{$row['rank']}' onerror='handleImageError(this)' />
                    <span class='rank-text'>{$row['rank']}</span>
                </td>
                <td><a href='player_profile.php?id={$row['id']}' class='btn btn-info'>Voir le profil</a></td>
            </tr>";
        }
        $conn->close();
        ?>
        </tbody>
    </table>

    <button class="btn btn-secondary mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#mmrDetails" aria-expanded="false" aria-controls="mmrDetails">
        Détails du calcul du MMR et des rangs
    </button>
    <div class="collapse collapse-content" id="mmrDetails">
        <div class="card card-body">
            <h2>Calcul du MMR</h2>
            <p>Le MMR (Matchmaking Rating) est calculé en fonction des résultats des matchs et de la différence de MMR entre les joueurs.</p>
            <h3>Étapes de calcul du MMR</h3>
            <ol>
                <li>**Récupération des MMR actuels des joueurs** :
                    <ul>
                        <li>Ancien MMR du joueur 1 : <code>$old_mmr1</code></li>
                        <li>Ancien MMR du joueur 2 : <code>$old_mmr2</code></li>
                    </ul>
                </li>
                <li>**Détermination des valeurs observées** :
                    <ul>
                        <li>Joueur 1 gagne : <code>$observed1 = 1</code></li>
                        <li>Joueur 1 perd : <code>$observed1 = 0</code></li>
                        <li>Joueur 2 gagne : <code>$observed2 = 1</code></li>
                        <li>Joueur 2 perd : <code>$observed2 = 0</code></li>
                    </ul>
                </li>
                <li>**Calcul des probabilités de victoire** :
                    <ul>
                        <li>Probabilité de victoire du joueur 1 : <code>$probability1 = 1 / (1 + 10 ^ (($old_mmr2 - $old_mmr1) / 400))</code></li>
                        <li>Probabilité de victoire du joueur 2 : <code>$probability2 = 1 / (1 + 10 ^ (($old_mmr1 - $old_mmr2) / 400))</code></li>
                    </ul>
                </li>
                <li>**Calcul des valeurs attendues** :
                    <ul>
                        <li>Valeur attendue pour le joueur 1 : <code>$expected1 = 0.5</code></li>
                        <li>Valeur attendue pour le joueur 2 : <code>$expected2 = 0.5</code></li>
                    </ul>
                </li>
                <li>**Marge de victoire** :
                    <ul>
                        <li>Marge de victoire : <code>$victory_margin = abs($score1 - $score2)</code></li>
                    </ul>
                </li>
                <li>**Facteur de victoire** :
                    <ul>
                        <li>Facteur de victoire : <code>$victory_factor = 1 + ($victory_margin / 10)</code></li>
                    </ul>
                </li>
                <li>**Points supplémentaires** :
                    <ul>
                        <li>Points supplémentaires : <code>$extra_points = $victory_margin</code></li>
                    </ul>
                </li>
                <li>**Calcul des nouveaux MMR** :
                    <ul>
                        <li>Si le joueur 1 gagne : <code>$new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $probability1) * $victory_factor + $extra_points)</code></li>
                        <li>Si le joueur 1 perd : <code>$new_mmr1 = ceil($old_mmr1 + 10 * ($observed1 - $probability1) * $victory_factor - $extra_points)</code></li>
                        <li>Si le joueur 2 gagne : <code>$new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $probability2) * $victory_factor + $extra_points)</code></li>
                        <li>Si le joueur 2 perd : <code>$new_mmr2 = ceil($old_mmr2 + 10 * ($observed2 - $probability2) * $victory_factor - $extra_points)</code></li>
                    </ul>
                </li>
            </ol>

            <h2>Rangs</h2>
            <p>Les rangs sont attribués en fonction du MMR comme suit :</p>
            <ul>
                <li><img src="assets/Challenger.svg" alt="Challenger"> Challenger: MMR >= 5000</li>
                <li><img src="assets/Grandmaster.svg" alt="Grandmaster"> Grandmaster: MMR >= 4000</li>
                <li><img src="assets/Master.svg" alt="Master"> Master: MMR >= 3500</li>
                <li><img src="assets/Diamond.svg" alt="Diamond"> Diamond: MMR >= 3000</li>
                <li><img src="assets/Emerald.svg" alt="Emerald"> Emerald: MMR >= 2500</li>
                <li><img src="assets/Platinum.svg" alt="Platinum"> Platinum: MMR >= 2000</li>
                <li><img src="assets/Gold.svg" alt="Gold"> Gold: MMR >= 1500</li>
                <li><img src="assets/Silver.svg" alt="Silver"> Silver: MMR >= 1000</li>
                <li><img src="assets/Bronze.svg" alt="Bronze"> Bronze: MMR >= 500</li>
                <li><img src="assets/Iron.svg" alt="Iron"> Iron: MMR < 500</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
