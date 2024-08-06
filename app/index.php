<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des joueurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <h1>Liste des joueurs</h1>
    <table class="table table-bordered">
        <thead>
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
                <td>{$row['rank']}</td> <!-- Affiche le rang actuel -->
                <td><a href='player_profile.php?id={$row['id']}' class='btn btn-info'>Voir le profil</a></td>
            </tr>";
        }
        $conn->close();
        ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
