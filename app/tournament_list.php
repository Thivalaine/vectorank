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

<?php include('header.php'); ?>

<div class="container">
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

<?php include('footer.php'); ?>