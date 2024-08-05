<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil du joueur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container mt-5">
    <?php
    include 'db.php';
    $playerId = $_GET['id'];
    
    // Récupérer les informations du joueur
    $result = $conn->query("SELECT * FROM players WHERE id = $playerId");
    $player = $result->fetch_assoc();

    // Compter les victoires et défaites
    $winsResult = $conn->query("SELECT COUNT(*) as wins FROM matches WHERE (player1 = $playerId AND score1 > score2) OR (player2 = $playerId AND score2 > score1)");
    $winsCount = $winsResult->fetch_assoc()['wins'];

    $lossesResult = $conn->query("SELECT COUNT(*) as losses FROM matches WHERE (player1 = $playerId AND score1 < score2) OR (player2 = $playerId AND score2 < score1)");
    $lossesCount = $lossesResult->fetch_assoc()['losses'];

    // Calculer le ratio
    $totalGames = $winsCount + $lossesCount;
    $winRatio = $totalGames > 0 ? ($winsCount / $totalGames) * 100 : 0; // pourcentage de victoires

    ?>
    <h1>Profil de <?php echo htmlspecialchars($player['name']); ?></h1>
    <p><strong>ID:</strong> <?php echo $player['id']; ?></p>
    <p><strong>MMR Actuel:</strong> <?php echo $player['mmr']; ?></p>
    <p><strong>Rang Actuel:</strong> <?php echo htmlspecialchars($player['rank']); ?></p>
    <p><strong>Victoire(s):</strong> <?php echo $winsCount; ?></p>
    <p><strong>Défaite(s):</strong> <?php echo $lossesCount; ?></p>
    <p><strong>Ratio de Victoires:</strong> <?php echo number_format($winRatio, 2) . '%'; ?></p> <!-- Afficher le ratio -->

    <h2>Matchs</h2>
    <table class="table table-bordered">
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
        </tr>
        </thead>
        <tbody>
        <?php
        $matches = $conn->query("SELECT * FROM matches WHERE player1 = $playerId OR player2 = $playerId");
        while ($match = $matches->fetch_assoc()) {
            $player1Name = $conn->query("SELECT name FROM players WHERE id = {$match['player1']}")->fetch_assoc()['name'];
            $player2Name = $conn->query("SELECT name FROM players WHERE id = {$match['player2']}")->fetch_assoc()['name'];
            echo "<tr>
                <td>{$match['id']}</td>
                <td>{$player1Name}</td>
                <td>{$match['score1']}</td>
                <td>{$match['score2']}</td>
                <td>{$player2Name}</td>
                <td>{$match['old_mmr1']}</td>
                <td>{$match['new_mmr1']}</td>
                <td>{$match['old_mmr2']}</td>
                <td>{$match['new_mmr2']}</td>
            </tr>";
        }
        ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Retour à la liste des joueurs</a>
</div>
</body>
</html>
