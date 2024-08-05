<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un match</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <h1>Ajouter un match</h1>
    <form action="add_match_action.php" method="post">
        <div class="form-group">
            <label for="player1">Joueur 1</label>
            <select class="form-control" id="player1" name="player1" required>
                <?php
                include 'db.php';
                $result = $conn->query("SELECT * FROM players");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                $conn->close();
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="player2">Joueur 2</label>
            <select class="form-control" id="player2" name="player2" required>
                <?php
                include 'db.php';
                $result = $conn->query("SELECT * FROM players");
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                }
                $conn->close();
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="score1">Score Joueur 1</label>
            <input type="number" class="form-control" id="score1" name="score1" required>
        </div>
        <div class="form-group">
            <label for="score2">Score Joueur 2</label>
            <input type="number" class="form-control" id="score2" name="score2" required>
        </div>
        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="index.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
