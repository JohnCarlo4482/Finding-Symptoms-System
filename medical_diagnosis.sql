CREATE DATABASE IF NOT EXISTS medical_diagnosis;
USE medical_diagnosis;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE diagnosis_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    symptoms TEXT,
    predicted_illness VARCHAR(255),
    confidence_score DECIMAL(5,2),
    diagnosed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ALTER TABLE diagnosis_history ADD COLUMN diagnosis VARCHAR(255);

);


