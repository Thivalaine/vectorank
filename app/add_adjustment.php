<?php 
    $pageTitle = "Ajuster un match";
    include('header.php');
?>

    <style>
        body {
            background-color: #f4f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            margin-top: 30px;
            max-width: 800px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #333333;
            margin-bottom: 20px;
        }

        .player-info {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #fafafa;
            transition: transform 0.2s;
        }

        .player-info:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .player-info h5 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #007bff;
            margin-bottom: 15px;
        }

        .mmr-changes {
            font-size: 1.1rem;
            margin-top: 10px;
            color: #555555;
        }

        .mmr-change {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background-color: #ffffff;
        }

        .mmr-change .arrow {
            font-size: 1.5rem;
            color: #17a2b8;
        }

        .mmr-change .change-value {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .mmr-change .change-value.positive {
            color: #28a745;
        }

        .mmr-change .change-value.negative {
            color: #dc3545;
        }

        .btn-primary {
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 1.1rem;
            font-weight: 600;
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-3px);
        }

        .result {
            margin-top: 30px;
            font-size: 1.2rem;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            background-color: #e9f7ef;
            color: #155724;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .result .winner {
            color: #28a745;
            font-weight: bold;
        }

        .result .loser {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Ajuster un match</h1>

        <?php
        // Inclusions, récupération des données et logique back-end
        include 'db.php';

        // Récupérer l'ID du match depuis l'URL
        if (isset($_GET['match_id']) && is_numeric($_GET['match_id'])) {
            $match_id = intval($_GET['match_id']);
        } else {
            die("Erreur: ID du match non spécifié ou invalide.");
        }

        // Récupérer les détails du match
        $matchQuery = "SELECT * FROM matches WHERE id = $match_id";
        $matchResult = $conn->query($matchQuery);
        if ($matchResult->num_rows > 0) {
            $matchData = $matchResult->fetch_assoc();

            // Vérifier si le match a déjà été ajusté
            if ($matchData['is_adjusted']) {
                echo "Ce match a déjà été ajusté et ne peut pas être ajusté une seconde fois.";
                exit;
            }

            $player1_id = $matchData['player1'];
            $player2_id = $matchData['player2'];
            $score1 = $matchData['score1'];
            $score2 = $matchData['score2'];
            $points1 = $matchData['points1'];
            $points2 = $matchData['points2'];
            $win_streak_bonus1 = $matchData['win_streak_bonus1'];
            $win_streak_bonus2 = $matchData['win_streak_bonus2'];

            // Récupérer les MMR actuels des joueurs
            $player1Query = "SELECT id, name, mmr FROM players WHERE id = $player1_id";
            $player2Query = "SELECT id, name, mmr FROM players WHERE id = $player2_id";

            $player1Result = $conn->query($player1Query);
            $player2Result = $conn->query($player2Query);

            if ($player1Result->num_rows > 0 && $player2Result->num_rows > 0) {
                $player1Data = $player1Result->fetch_assoc();
                $player2Data = $player2Result->fetch_assoc();

                $player1Name = $player1Data['name'];
                $player2Name = $player2Data['name'];

                $current_mmr1 = $player1Data['mmr'];
                $current_mmr2 = $player2Data['mmr'];

                // Déterminer le résultat du match
                $is_player1_winner = $score1 > $score2;
                $is_player2_winner = $score2 > $score1;

                // Ajuster les points pour chaque joueur
                $base_points_player1 = $winner_id == $player1_id ? $points1 + $win_streak_bonus1 : -($points1 + $win_streak_bonus1);
                $base_points_player2 = $winner_id == $player2_id ? $points2 + $win_streak_bonus2 : -($points2 + $win_streak_bonus2);

                // Ajustements de MMR
                $adjustment_player1 = $base_points_player1;
                $adjustment_player2 = $base_points_player2;

                // Calculer le MMR après ajustement
                $mmr_after_adjustment1 = $current_mmr1 + $adjustment_player1;
                $mmr_after_adjustment2 = $current_mmr2 + $adjustment_player2;

                // Déterminer le gagnant et le perdant
                $winner_id = $is_player1_winner ? $player1_id : $player2_id;
                $loser_id = ($winner_id == $player1_id) ? $player2_id : $player1_id;

                $adjustments = [
                    $player1_id => $adjustment_player1,
                    $player2_id => $adjustment_player2
                ];
            } else {
                die("Erreur: Joueur non trouvé.");
            }
        } else {
            die("Erreur: Match non trouvé.");
        }
        ?>

        <div class="result">
            <h2>Résultat du match</h2>
            <p>
                <?php if ($winner_id == $player1_id): ?>
                    <span class="winner"><?php echo htmlspecialchars($player1Name); ?> avait gagné !</span><br>
                    <span class="loser"><?php echo htmlspecialchars($player2Name); ?> avait perdu.</span>
                <?php else: ?>
                    <span class="winner"><?php echo htmlspecialchars($player2Name); ?> avait gagné !</span><br>
                    <span class="loser"><?php echo htmlspecialchars($player1Name); ?> avait perdu.</span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Joueur 1 -->
        <div class="player-info">
            <h5>Joueur 1: <?php echo htmlspecialchars($player1Name); ?></h5>
            <div class="mmr-changes">
                <div class="mmr-change">
                    <span>MMR actuel : <strong><?php echo $current_mmr1; ?></strong></span>
                    <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="change-value <?php echo ($adjustments[$player1_id] >= 0) ? 'positive' : 'negative'; ?>">
                        <?php echo ($adjustments[$player1_id] >= 0 ? '+' : '') . $adjustments[$player1_id]; ?>
                    </span>
                    <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span>MMR : <strong class="change-value <?php echo ($adjustments[$player1_id] >= 0) ? 'positive' : 'negative'; ?>"><?php echo $mmr_after_adjustment1; ?></strong></span>
                </div>
                <div>
                    <span>Points obtenus : <strong><?php echo $points1 + $win_streak_bonus1; ?></strong></span>
                </div>
            </div>
        </div>

        <!-- Joueur 2 -->
        <div class="player-info">
            <h5>Joueur 2: <?php echo htmlspecialchars($player2Name); ?></h5>
            <div class="mmr-changes">
                <div class="mmr-change">
                    <span>MMR actuel : <strong><?php echo $current_mmr2; ?></strong></span>
                    <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="change-value <?php echo ($adjustments[$player2_id] >= 0) ? 'positive' : 'negative'; ?>">
                        <?php echo ($adjustments[$player2_id] >= 0 ? '+' : '') . $adjustments[$player2_id]; ?>
                    </span>
                    <span class="arrow"><i class="fa-solid fa-arrow-right"></i></span>
                    <span>MMR : <strong class="change-value <?php echo ($adjustments[$player2_id] >= 0) ? 'positive' : 'negative'; ?>"><?php echo $mmr_after_adjustment2; ?></strong></span>
                </div>
                <div>
                    <span>Points obtenus : <strong><?php echo $points2 + $win_streak_bonus2; ?></strong></span>
                </div>
            </div>
        </div>

        <form action="add_adjustment_action.php" method="POST">
            <input type="hidden" name="match_id" value="<?php echo htmlspecialchars($match_id); ?>">

            <?php foreach ($adjustments as $player_id => $adjustment_value): ?>
                <input type="hidden" name="adjustments[<?php echo htmlspecialchars($player_id); ?>]" value="<?php echo htmlspecialchars($adjustment_value); ?>">
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary">Ajuster</button>
        </form>
    </div>

<?php include('footer.php'); ?>