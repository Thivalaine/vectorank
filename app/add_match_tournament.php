<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un match</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container mt-5">
    <h1>Ajouter un match de tournoi</h1>
    <form action="add_match_tournament_action.php" method="post">
        <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
        <div class="form-group">
            <label for="player1">Joueur 1</label>
            <select class="form-control" id="player1" name="player1" required disabled>
                <?php
                include 'db.php';

                // Récupérer les paramètres d'URL
                $player1_id = isset($_GET['player1_id']) ? intval($_GET['player1_id']) : null;
                $player2_id = isset($_GET['player2_id']) ? intval($_GET['player2_id']) : null;
                $tournament_id = isset($_GET['tournament_id']) ? intval($_GET['tournament_id']) : null;

                // Requête pour récupérer les joueurs
                $result = $conn->query("SELECT * FROM players");
                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['id'] == $player1_id) ? 'selected' : '';
                    echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                }
                ?>
            </select>
            <input type="hidden" name="player1" value="<?php echo $player1_id; ?>">
        </div>
        <div class="form-group">
            <label for="player2">Joueur 2</label>
            <select class="form-control" id="player2" name="player2" required disabled>
                <?php
                // Réinitialiser le pointeur au début du résultat
                $result->data_seek(0);
                while ($row = $result->fetch_assoc()) {
                    $selected = ($row['id'] == $player2_id) ? 'selected' : '';
                    echo "<option value='{$row['id']}' $selected>{$row['name']}</option>";
                }
                ?>
            </select>
            <input type="hidden" name="player2" value="<?php echo $player2_id; ?>">
        </div>
        <div class="form-group">
            <label for="score1">Score Joueur 1</label>
            <input type="number" class="form-control" id="score1" name="score1" required>
        </div>
        <div class="form-group">
            <label for="score2">Score Joueur 2</label>
            <input type="number" class="form-control" id="score2" name="score2" required>
        </div>

        <div class="form-group">
            <label for="tournament">Tournoi:</label>
            <select class="form-control" id="tournament" name="tournament_id" disabled>
                <option value="">Aucun</option>
                <?php
                // Requête pour récupérer les tournois
                $tournamentsResult = $conn->query("SELECT * FROM tournaments ORDER BY start_date DESC");
                while ($tournament = $tournamentsResult->fetch_assoc()) {
                    $selected = ($tournament['id'] == $tournament_id) ? 'selected' : '';
                    echo "<option value='{$tournament['id']}' $selected>" . htmlspecialchars($tournament['name']) . "</option>";
                }
                ?>
            </select>
            <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
        </div>

        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="tournament_detail.php?id=<?php echo $tournament_id; ?>" class="btn btn-secondary">Annuler</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
