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
        .form-select {
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
                <div class="card-header text-center">
                    <h1>Ajouter un tournoi</h1>
                </div>
                <div class="card-body">
                    <form action="add_tournament.php" method="POST">
                        <div class="form-group">
                            <label for="tournament_name">Nom du tournoi</label>
                            <input type="text" class="form-control" id="tournament_name" name="tournament_name" placeholder="Entrez le nom du tournoi" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="participants">Participants (au nombre de 4, 8 ou 16)</label>
                            <select multiple class="form-select select2" id="participants" name="participants[]" required>
                                <?php
                                // Connexion à la base de données
                                include 'db.php';
                                
                                // Récupération des joueurs
                                $result = $conn->query("SELECT id, name FROM players");
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                }
                                ?>
                            </select>
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
</div>

<!-- Script to enhance Select2 styling and behavior -->
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: 'Sélectionnez des participants',
            allowClear: true,
            width: '100%' // To ensure it fits nicely inside the form,
        });
    });
</script>

<?php include('footer.php'); ?>
