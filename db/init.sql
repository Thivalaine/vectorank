CREATE DATABASE IF NOT EXISTS elo_system;

USE elo_system;

-- Création de la table des joueurs avec les colonnes pour les séries de victoires
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mmr INT NOT NULL,
    old_mmr INT NOT NULL DEFAULT 0, -- Colonne pour stocker l'ancien MMR du joueur
    rank VARCHAR(20) DEFAULT 'Silver', -- Colonne pour stocker le rang du joueur
    current_win_streak INT DEFAULT 0, -- Colonne pour stocker la série de victoires actuelle
    best_win_streak INT DEFAULT 0, -- Colonne pour stocker la meilleure série de victoires
    best_mmr INT DEFAULT 0 -- Colonne pour stocker le meilleur MMR du joueur
    new_ranking INT(11) NOT NULL DEFAULT 0, -- Colonne pour le nouveau classement
    old_ranking INT(11) NOT NULL DEFAULT 0 -- Colonne pour l'ancien classement
    is_anonymized TINYINT(1) DEFAULT '0' -- Colonne pour stocker si le joueur est anonymisé
);

CREATE TABLE IF NOT EXISTS tournaments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    phase VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS tournament_players (
    tournament_id INT(11) NOT NULL,
    player_id INT(11) NOT NULL,
    current_round VARCHAR(50) DEFAULT NULL,
    is_won TINYINT(1) DEFAULT '0',
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (player_id) REFERENCES players(id),
    PRIMARY KEY (tournament_id, player_id)
);

CREATE TABLE IF NOT EXISTS matches (
    id INT(11) NOT NULL AUTO_INCREMENT,
    player1 INT(11) NOT NULL,
    player2 INT(11) NOT NULL,
    score1 INT(11) DEFAULT NULL,
    score2 INT(11) DEFAULT NULL,
    observed1 FLOAT DEFAULT NULL,
    observed2 FLOAT DEFAULT NULL,
    victory_margin INT(11) DEFAULT NULL,
    victory_factor FLOAT DEFAULT NULL,
    probability1 FLOAT DEFAULT NULL,
    probability2 FLOAT DEFAULT NULL,
    old_mmr1 INT(11) DEFAULT NULL,
    old_mmr2 INT(11) DEFAULT NULL,
    new_mmr1 INT(11) DEFAULT NULL,
    new_mmr2 INT(11) DEFAULT NULL,
    elo_difference INT(11) DEFAULT NULL,
    match_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    points1 INT(11) DEFAULT NULL,
    points2 INT(11) DEFAULT NULL,
    win_streak_bonus1 INT(11) DEFAULT NULL,
    win_streak_bonus2 INT(11) DEFAULT NULL,
    tournament_id INT(11) DEFAULT NULL,
    round VARCHAR(50) DEFAULT NULL,
    consolation_points INT(11) DEFAULT '0',
    winner_points INT(11) DEFAULT '0',
    is_adjusted TINYINT(1) DEFAULT '0',
    PRIMARY KEY (id),
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (player1) REFERENCES players(id),
    FOREIGN KEY (player2) REFERENCES players(id)
);

CREATE TABLE adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    old_mmr DECIMAL(10, 2) NOT NULL,
    new_mmr DECIMAL(10, 2) NOT NULL,
    adjustment_value DECIMAL(10, 2) NOT NULL,
    match_id INT NOT NULL,
    adjustment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES players(id),
    FOREIGN KEY (match_id) REFERENCES matches(id)
);

-- Créer la table avec les colonnes correctes
CREATE TABLE IF NOT EXISTS duo_matches (
    id INT(11) NOT NULL AUTO_INCREMENT,
    team1_player1 INT(11) NOT NULL,
    team1_player2 INT(11) NOT NULL,
    team2_player1 INT(11) NOT NULL,
    team2_player2 INT(11) NOT NULL,
    team1_score INT(11) DEFAULT NULL,
    team2_score INT(11) DEFAULT NULL,
    observed_team1 FLOAT DEFAULT NULL,
    observed_team2 FLOAT DEFAULT NULL,
    victory_margin INT(11) DEFAULT NULL,
    victory_factor FLOAT DEFAULT NULL,
    probability_team1 FLOAT DEFAULT NULL,
    probability_team2 FLOAT DEFAULT NULL,
    old_mmr_team1_player1 INT(11) DEFAULT NULL,
    old_mmr_team1_player2 INT(11) DEFAULT NULL,
    old_mmr_team2_player1 INT(11) DEFAULT NULL,
    old_mmr_team2_player2 INT(11) DEFAULT NULL,
    new_mmr_team1_player1 INT(11) DEFAULT NULL,
    new_mmr_team1_player2 INT(11) DEFAULT NULL,
    new_mmr_team2_player1 INT(11) DEFAULT NULL,
    new_mmr_team2_player2 INT(11) DEFAULT NULL,
    elo_difference INT(11) DEFAULT NULL,
    match_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    points_team1 INT(11) DEFAULT NULL,
    points_team2 INT(11) DEFAULT NULL,
    win_streak_bonus_team1 INT(11) DEFAULT NULL,
    win_streak_bonus_team2 INT(11) DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (team1_player1) REFERENCES players(id),
    FOREIGN KEY (team1_player2) REFERENCES players(id),
    FOREIGN KEY (team2_player1) REFERENCES players(id),
    FOREIGN KEY (team2_player2) REFERENCES players(id)
);
