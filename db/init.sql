CREATE DATABASE IF NOT EXISTS elo_system;

USE elo_system;

-- Création de la table des joueurs avec les colonnes pour les séries de victoires
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mmr INT NOT NULL,
    rank VARCHAR(20) DEFAULT 'Silver', -- Colonne pour stocker le rang du joueur
    current_win_streak INT DEFAULT 0, -- Colonne pour stocker la série de victoires actuelle
    best_win_streak INT DEFAULT 0, -- Colonne pour stocker la meilleure série de victoires
    best_mmr INT DEFAULT 0 -- Colonne pour stocker le meilleur MMR du joueur
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
    expected1 FLOAT DEFAULT NULL,
    expected2 FLOAT DEFAULT NULL,
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
    PRIMARY KEY (id),
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id),
    FOREIGN KEY (player1) REFERENCES players(id),
    FOREIGN KEY (player2) REFERENCES players(id)
);
