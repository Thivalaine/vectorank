<?php
// db.php : Assurez-vous que ce fichier contient la connexion PDO à votre base de données
include 'db.php';

try {
    // Récupération des tournois
    $sql = "SELECT * FROM tournaments ORDER BY created_at DESC";
    $stmt = $conn->query($sql);
    // Initialiser un tableau pour stocker les tournois
    $tournaments = [];
    // Boucle pour récupérer chaque tournoi un par un
    while ($row = $stmt->fetch_assoc()) {
        $tournaments[] = $row;
    }
} catch (PDOException $e) {
    // Gérer l'erreur de la base de données
    die("Erreur lors de la récupération des tournois : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Tournois</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container mt-5">
        <h1 class="mb-4">Liste des Tournois</h1>
        <?php if (empty($tournaments)): ?>
            <div class="alert alert-warning" role="alert">
                Aucun tournoi trouvé.
            </div>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Date de début</th>
                        <th>Date de fin</th>
                        <th>Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tournaments as $tournament): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tournament['id']); ?></td>
                        <td><?php echo htmlspecialchars($tournament['name']); ?></td>
                        <td><?php echo htmlspecialchars($tournament['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($tournament['end_date']); ?></td>
                        <td>
                            <a href="tournament_detail.php?id=<?php echo $tournament['id']; ?>" class="btn btn-info"><i class="fa-solid fa-circle-info"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
