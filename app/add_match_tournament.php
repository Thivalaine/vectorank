<?php include('header.php'); ?>

<div class="container-fluid">
    <style>
        .card {
            margin-top: 15px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 30px;
            color: #343a40;
            text-align: center;
        }
        label {
            font-weight: bold;
            color: #495057;
        }
        .form-control {
            border-radius: 10px;
            padding: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
    </style>
    
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <h1>Ajouter un match</h1>
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
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-block">Créer</button>
                        <a href="tournament_detail.php?id=<?php echo $tournament_id; ?>" class="btn btn-secondary btn-block">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
