<?php
include 'db.php';

// Récupération de l'ID du tournoi depuis l'URL
$tournamentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupération des informations du tournoi
$tournamentSql = "SELECT * FROM tournaments WHERE id = ?";
$tournamentStmt = $conn->prepare($tournamentSql);
$tournamentStmt->bind_param("i", $tournamentId);
$tournamentStmt->execute();
$tournament = $tournamentStmt->get_result()->fetch_assoc();

if (!$tournament) {
    echo "Tournoi non trouvé.";
    exit;
}

// Récupération des matchs associés au tournoi
$matchesSql = "SELECT m.*, p1.name AS player1_name, p2.name AS player2_name 
               FROM matches m 
               JOIN players p1 ON m.player1 = p1.id 
               JOIN players p2 ON m.player2 = p2.id 
               WHERE m.tournament_id = ?";
$matchesStmt = $conn->prepare($matchesSql);
$matchesStmt->bind_param("i", $tournamentId);
$matchesStmt->execute();
$matchesResult = $matchesStmt->get_result();
$matches = $matchesResult->fetch_all(MYSQLI_ASSOC);

// Organisation des matchs par round
$matchesByRound = [
    'huitième' => [],
    'quart' => [],
    'demi' => [],
    'finale' => []
];

foreach ($matches as $match) {
    $round = strtolower($match['round']); // Utilisation de 'round' à la place de 'phase'
    if (isset($matchesByRound[$round])) {
        $matchesByRound[$round][] = $match;
    } else {
        echo "<p>Phase non reconnue: " . htmlspecialchars($match['round']) . "</p>";
    }
}

// Détermination des gagnants par phase
$eighthFinalWinners = [];
$quarterFinalWinners = [];
$semiFinalWinners = [];
$finalWinner = null;

foreach ($matchesByRound['huitième'] as $match) {
    if (isset($match['score1']) && isset($match['score2'])) {
        $winner = $match['score1'] > $match['score2'] ? $match['player1_name'] : $match['player2_name'];
        $eighthFinalWinners[] = $winner;
    }
}

foreach ($matchesByRound['quart'] as $match) {
    if (isset($match['score1']) && isset($match['score2'])) {
        $winner = $match['score1'] > $match['score2'] ? $match['player1_name'] : $match['player2_name'];
        $quarterFinalWinners[] = $winner;
    }
}

foreach ($matchesByRound['demi'] as $match) {
    if (isset($match['score1']) && isset($match['score2'])) {
        $winner = $match['score1'] > $match['score2'] ? $match['player1_name'] : $match['player2_name'];
        $semiFinalWinners[] = $winner;
    }
}

if (!empty($matchesByRound['finale'])) {
    $finalMatch = $matchesByRound['finale'][0];
    if (isset($finalMatch['score1']) && isset($finalMatch['score2'])) {
        $finalWinner = $finalMatch['score1'] > $finalMatch['score2'] ? $finalMatch['player1_name'] : $finalMatch['player2_name'];
    }
}

// Déterminer la phase actuelle du tournoi
$currentPhase = '';
if (!empty($matchesByRound['finale'])) {
    $currentPhase = 'finale';
} elseif (!empty($matchesByRound['demi'])) {
    $currentPhase = 'demi';
} elseif (!empty($matchesByRound['quart'])) {
    $currentPhase = 'quart';
} elseif (!empty($matchesByRound['huitième'])) {
    $currentPhase = 'huitième';
} else {
    $currentPhase = 'groupe';
}

// Préparer les joueurs pour les phases non jouées
$potentialSemiFinals = array_slice($quarterFinalWinners, 0, 4);
$potentialFinals = array_slice($semiFinalWinners, 0, 2);
?>

<?php include('header.php'); ?>

<div class="container">
    <h1 class="display-4 text-center mb-4">Détails du Tournoi: <?php echo htmlspecialchars($tournament['name']); ?></h1>

    <div class="row mb-5">
        <div class="col-md-6">
            <p><strong>Date de début:</strong> <?php echo htmlspecialchars($tournament['start_date']); ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Date de fin:</strong> <?php echo htmlspecialchars($tournament['end_date']); ?></p>
        </div>
    </div>

    <h2 class="mt-4 text-center">Matchs du Tournoi</h2>
    <div class="table-responsive mt-4">
        <table class="table table-striped table-hover table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Joueur 1</th>
                    <th>Joueur 2</th>
                    <th>Score 1</th>
                    <th>Score 2</th>
                    <th>Vainqueur</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matches)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Aucun match trouvé pour ce tournoi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($matches as $match): ?>
                        <tr>
                            <?php
                            $isMatchPlayed = $match['score1'] !== null && $match['score2'] !== null && ($match['score1'] > 0 || $match['score2'] > 0);
                            $isPlayer1Winner = $isMatchPlayed && $match['score1'] > $match['score2'];
                            ?>
                            
                            <td><?php echo htmlspecialchars($match['id']); ?></td>
                            <td class="font-weight-bold <?php echo $isMatchPlayed && $isPlayer1Winner ? 'text-success' : 'text-danger'; ?>">
                                <?php echo htmlspecialchars($match['player1_name']); ?>
                            </td>
                            <td class="font-weight-bold <?php echo $isMatchPlayed && !$isPlayer1Winner ? 'text-success' : 'text-danger'; ?>">
                                <?php echo htmlspecialchars($match['player2_name']); ?>
                            </td>
                            <td class="text-center"><?php echo htmlspecialchars($match['score1']); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($match['score2']); ?></td>
                            <td class="text-center font-weight-bold">
                                <?php if ($isMatchPlayed): ?>
                                    <?php echo htmlspecialchars($isPlayer1Winner ? $match['player1_name'] : $match['player2_name']); ?>
                                <?php else: ?>
                                    Non joué
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h1 class="text-center mb-5 display-4">Phases du Tournoi</h1>
    <div class="row tournament-phase">
        <div class="col-12">
            <h4 class="text-center">Huitièmes de Finale</h4>
        </div>
        <?php if (empty($matchesByRound['huitième'])): ?>
            <div class="col-12 text-center">
                <p>Aucun match n'a été joué pour les huitièmes de finale.</p>
            </div>
        <?php else: ?>
            <?php foreach ($matchesByRound['huitième'] as $match): ?>
                <div class="col-12 col-md-6 col-lg-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($match['player1_name']); ?> 
                                vs 
                                <?php echo htmlspecialchars($match['player2_name']); ?> 
                            </h5>
                            <div class="outcome">
                                <?php if (isset($match['score1']) && isset($match['score2'])): ?>
                                    <h4 class="score"><?php echo htmlspecialchars($match['score1']); ?> - <?php echo htmlspecialchars($match['score2']); ?></h4>
                                    <span class="winner text-success font-weight-bold">
                                        <span class="badge bg-success">Victoire</span> <?php echo htmlspecialchars($match['score1'] > $match['score2'] ? $match['player1_name'] : $match['player2_name']); ?>
                                    </span>
                                    <span class="loser text-danger font-weight-bold">
                                        <span class="badge bg-danger">Défaite</span> <?php echo htmlspecialchars($match['score1'] < $match['score2'] ? $match['player1_name'] : $match['player2_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <h4 class="score">Non joué</h4>
                                <?php endif; ?>
                            </div>
                            <?php if (!isset($match['score1']) || !isset($match['score2'])): ?>
                                <a href="add_match_tournament.php?id=<?php echo $match['id']; ?>&tournament_id=<?php echo $tournamentId ?>&player1_id=<?php echo $match['player1']; ?>&player2_id=<?php echo $match['player2']; ?>" class="btn btn-primary">Ajouter un score</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="col-12">
            <h4 class="text-center">Quarts de Finale</h4>
        </div>
        <?php if (empty($matchesByRound['quart'])): ?>
            <div class="col-12 text-center">
                <p>Aucun match n'a été joué pour les quarts de finale.</p>
            </div>
        <?php else: ?>
            <?php foreach ($matchesByRound['quart'] as $match): ?>
                <div class="col-12 col-md-6 col-lg-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($match['player1_name']); ?> 
                                vs 
                                <?php echo htmlspecialchars($match['player2_name']); ?> 
                            </h5>
                            <div class="outcome">
                                <?php if (isset($match['score1']) && isset($match['score2'])): ?>
                                    <h4 class="score"><?php echo htmlspecialchars($match['score1']); ?> - <?php echo htmlspecialchars($match['score2']); ?></h4>
                                    <span class="winner text-success font-weight-bold">
                                        <span class="badge bg-success">Victoire</span> <?php echo htmlspecialchars($match['score1'] > $match['score2'] ? $match['player1_name'] : $match['player2_name']); ?>
                                    </span>
                                    <span class="loser text-danger font-weight-bold">
                                        <span class="badge bg-danger">Défaite</span> <?php echo htmlspecialchars($match['score1'] < $match['score2'] ? $match['player1_name'] : $match['player2_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <h4 class="score">Non joué</h4>
                                <?php endif; ?>
                            </div>
                            <?php if (!isset($match['score1']) || !isset($match['score2'])): ?>
                                <a href="add_match_tournament.php?id=<?php echo $match['id']; ?>&tournament_id=<?php echo $tournamentId ?>&player1_id=<?php echo $match['player1']; ?>&player2_id=<?php echo $match['player2']; ?>" class="btn btn-primary">Ajouter un score</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="col-12">
            <h4 class="text-center">Demi-Finale</h4>
        </div>
        <?php if (empty($matchesByRound['demi'])): ?>
            <div class="col-12 text-center">
                <p>Aucun match n'a été joué pour les demi-finales.</p>
            </div>
        <?php else: ?>
            <?php foreach ($matchesByRound['demi'] as $match): ?>
                <div class="col-12 col-md-6 col-lg-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($match['player1_name']); ?> 
                                vs 
                                <?php echo htmlspecialchars($match['player2_name']); ?> 
                            </h5>
                            <div class="outcome">
                                <?php if (isset($match['score1']) && isset($match['score2'])): ?>
                                    <h4 class="score"><?php echo htmlspecialchars($match['score1']); ?> - <?php echo htmlspecialchars($match['score2']); ?></h4>
                                    <span class="winner text-success font-weight-bold">
                                        <span class="badge bg-success">Victoire</span> <?php echo htmlspecialchars($match['score1'] > $match['score2'] ? $match['player1_name'] : $match['player2_name']); ?>
                                    </span>
                                    <span class="loser text-danger font-weight-bold">
                                        <span class="badge bg-danger">Défaite</span> <?php echo htmlspecialchars($match['score1'] < $match['score2'] ? $match['player1_name'] : $match['player2_name']); ?>
                                        <span class="badge bg-warning">3ème</span>
                                    </span>
                                <?php else: ?>
                                    <h4 class="score">Non joué</h4>
                                <?php endif; ?>
                            </div>
                            <?php if (!isset($match['score1']) || !isset($match['score2'])): ?>
                                <a href="add_match_tournament.php?id=<?php echo $match['id']; ?>&tournament_id=<?php echo $tournamentId ?>&player1_id=<?php echo $match['player1']; ?>&player2_id=<?php echo $match['player2']; ?>" class="btn btn-primary">Ajouter un score</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="col-12">
            <h4 class="text-center">Finale</h4>
        </div>
        <?php if (empty($matchesByRound['finale'])): ?>
            <div class="col-12 text-center">
                <p>Aucun match n'a été joué pour la finale.</p>
            </div>
        <?php else: ?>
            <?php foreach ($matchesByRound['finale'] as $match): ?>
                <div class="col-12 col-md-6 col-lg-3 mb-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($match['player1_name']); ?> 
                                vs 
                                <?php echo htmlspecialchars($match['player2_name']); ?> 
                            </h5>
                            <div class="outcome">
                                <?php if (isset($match['score1']) && isset($match['score2'])): ?>
                                    <h4 class="score"><?php echo htmlspecialchars($match['score1']); ?> - <?php echo htmlspecialchars($match['score2']); ?></h4>
                                    <span class="winner text-success font-weight-bold">
                                        <span class="badge bg-success">Victoire</span> <?php echo htmlspecialchars($match['score1'] > $match['score2'] ? $match['player1_name'] : $match['player2_name']); ?>
                                        <span class="badge bg-warning">1er</span>
                                    </span>
                                    <span class="loser text-danger font-weight-bold">
                                        <span class="badge bg-danger">Défaite</span> <?php echo htmlspecialchars($match['score1'] < $match['score2'] ? $match['player1_name'] : $match['player2_name']); ?>
                                        <span class="badge bg-warning">2ème</span>
                                    </span>
                                <?php else: ?>
                                    <h4 class="score">Non joué</h4>
                                <?php endif; ?>
                            </div>
                            <?php if (!isset($match['score1']) || !isset($match['score2'])): ?>
                                <a href="add_match_tournament.php?id=<?php echo $match['id']; ?>&tournament_id=<?php echo $tournamentId ?>&player1_id=<?php echo $match['player1']; ?>&player2_id=<?php echo $match['player2']; ?>" class="btn btn-primary">Ajouter un score</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>



<style>
            .tournament-phase {
                display: flex;
                justify-content: space-around;
                align-items: center;
                flex-wrap: wrap;
            }

            .tournament-phase .col-md-4 {
                margin-bottom: 20px;
            }

            .tournament-phase h4 {
                background-color: #007bff;
                color: white;
                padding: 10px;
                border-radius: 10px;
                margin-bottom: 20px;
                font-size: 1.5rem;
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            }

            .match {
                background-color: #ffffff;
                border: 2px solid #007bff;
                padding: 15px;
                border-radius: 10px;
                margin-bottom: 15px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                transition: background-color 0.3s, transform 0.3s, box-shadow 0.3s;
            }

            .match:hover {
                background-color: #007bff;
                color: white;
                transform: translateY(-5px);
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            }

            .player-name {
                font-weight: bold;
                font-size: 1.2rem;
            }

            .vs {
                margin: 0 10px;
                font-weight: bold;
                font-size: 1.2rem;
            }

            .outcome {
                margin-top: 10px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            .score {
                font-weight: bold;
                font-size: 1.2rem;
            }

            .winner {
        background-color: #d4edda; /* Vert clair */
        color: #155724; /* Texte vert foncé */
    }

    .loser {
        background-color: #f8d7da; /* Rouge clair */
        color: #721c24; /* Texte rouge foncé */
    }

            .champion {
                font-size: 1.5rem;
                font-weight: bold;
                color: green;
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            }
</style>
</div>

<?php include('footer.php'); ?>
