<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des matchs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#">Vectorank</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link" href="index.php">Liste des joueurs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="add_player.php">Ajouter un joueur</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="add_match.php">Ajouter un match</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="matches.php">Liste des matchs</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container mt-5">
    <h1>Liste des matchs</h1>
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
        </tr>
        </thead>
        <tbody>
        <?php
        include 'db.php';
        $matches = $conn->query("SELECT m.*, p1.name AS player1_name, p1.mmr AS mmr1, p2.name AS player2_name, p2.mmr AS mmr2 
                                  FROM matches m 
                                  JOIN players p1 ON m.player1 = p1.id 
                                  JOIN players p2 ON m.player2 = p2.id");

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
            </tr>";
        }
        $conn->close();

        // Fonction pour déterminer le rang en fonction du MMR
        function getRank($mmr) {
            if ($mmr >= 4000) {
                return "Challenger";
            } elseif ($mmr >= 3000) {
                return "Grandmaster";
            } elseif ($mmr >= 2750) {
                return "Master";
            } elseif ($mmr >= 2500) {
                return "Emerald";
            } elseif ($mmr >= 2250) {
                return "Diamond";
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
