-- Create the database
CREATE DATABASE IF NOT EXISTS cards_db;
USE cards_db;

-- Create the cards table
CREATE TABLE IF NOT EXISTS cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT UNIQUE NOT NULL,
    date DATE NOT NULL,
    obtainable BOOLEAN NOT NULL DEFAULT TRUE
);

-- Add an index on card_id for faster lookups
CREATE INDEX idx_card_id ON cards(card_id);
