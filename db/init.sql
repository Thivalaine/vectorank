CREATE DATABASE IF NOT EXISTS elo_system;

USE elo_system;

-- Création de la table des joueurs
CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    mmr INT NOT NULL,
    rank VARCHAR(20) DEFAULT 'Iron' -- Colonne pour stocker le rang du joueur
);

-- Création de la table des matchs
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player1 INT NOT NULL,
    player2 INT NOT NULL,
    score1 INT NOT NULL,
    score2 INT NOT NULL,
    observed1 FLOAT NOT NULL,
    observed2 FLOAT NOT NULL,
    expected1 FLOAT NOT NULL,
    expected2 FLOAT NOT NULL,
    victory_margin INT NOT NULL,
    victory_factor FLOAT NOT NULL,
    probability1 FLOAT NOT NULL,
    probability2 FLOAT NOT NULL,
    old_mmr1 INT NOT NULL,
    old_mmr2 INT NOT NULL,
    new_mmr1 INT NOT NULL,
    new_mmr2 INT NOT NULL,
    elo_difference INT NOT NULL,
    match_date DATETIME DEFAULT CURRENT_TIMESTAMP, -- Colonne pour la date du match
    FOREIGN KEY (player1) REFERENCES players(id),
    FOREIGN KEY (player2) REFERENCES players(id)
);
