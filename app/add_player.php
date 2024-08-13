<?php include('header.php'); ?>

<div class="container-fluid">
    <style>
        body {
            background-color: #f8f9fa;
        }
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
                    <h1>Ajouter un joueur</h1>
                </div>
                <div class="card-body">
                    <form action="add_player.php" method="POST">
                        <div class="form-group">
                            <label for="name">Nom du joueur</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="Entrez le nom du joueur" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-block">Ajouter</button>
                            <a href="index.php" class="btn btn-secondary btn-block">Annuler</a>
                        </div>
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
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
