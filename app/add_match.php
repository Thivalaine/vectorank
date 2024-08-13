<?php include('header.php'); ?>

<div class="container-fluid">
<style>
        .card {
            margin-top: 50px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
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
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h1>Ajouter un match</h1>
                </div>
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
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="player2">Joueur 2</label>
                        <select class="form-control" id="player2" name="player2" required>
                            <?php
                            $result->data_seek(0); // Remise à zéro du pointeur de résultats
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['id']}'>{$row['name']}</option>";
                            }
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
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-block">Ajouter</button>
                        <a href="index.php" class="btn btn-secondary btn-block">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>