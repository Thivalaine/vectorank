<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un joueur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container mt-5">
    <h1>Ajouter un joueur</h1>
    <form action="add_player.php" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Nom du joueur</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <button type="submit" class="btn btn-primary">Ajouter le joueur</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        include 'db.php';
        
        $name = $conn->real_escape_string($_POST['name']);
        $initialMmr = 1000; // MMR initial fixé à 1000

        // Insertion du joueur dans la base de données
        $sql = "INSERT INTO players (name, mmr) VALUES ('$name', $initialMmr)";

        if ($conn->query($sql) === TRUE) {
            echo "<div class='alert alert-success mt-3'>Joueur ajouté avec succès.</div>";
        } else {
            echo "<div class='alert alert-danger mt-3'>Erreur : " . $conn->error . "</div>";
        }
        $conn->close();
    }
    ?>
</div>
</body>
</html>
