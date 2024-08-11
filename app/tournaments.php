<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un tournoi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
    <?php include 'navbar.php'; ?>
    <form action="add_tournament.php" method="POST" class="container mt-4 p-4 border rounded bg-light shadow-sm">
        <h4 class="mb-4">Ajouter un Tournoi</h4>
        
        <div class="mb-3">
            <label for="tournament_name" class="form-label">Nom du tournoi</label>
            <input type="text" class="form-control" id="tournament_name" name="tournament_name" placeholder="Entrez le nom du tournoi" required>
        </div>
        
        <div class="mb-3">
            <label for="participants" class="form-label">Participants (au nombre de 4, 8 ou 16)</label>
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
        
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-block">Ajouter le tournoi</button>
        </div>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: 'Sélectionnez des participants',
                allowClear: true
            });
        });
    </script>
</body>
</html>
