CREATE DATABASE IF NOT EXISTS car_workshop_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE car_workshop_db;

CREATE TABLE IF NOT EXISTS mechanics (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointments (
    id INT NOT NULL AUTO_INCREMENT,
    client_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(30) NOT NULL,
    license_no VARCHAR(50) NOT NULL,
    engine_no VARCHAR(50) NOT NULL,
    appointment_date DATE NOT NULL,
    mechanic_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_appointments_mechanics
        FOREIGN KEY (mechanic_id) REFERENCES mechanics (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE appointments
    MODIFY id INT NOT NULL AUTO_INCREMENT;

INSERT INTO mechanics (name, specialty) VALUES
    ('John Smith', 'Engine'),
    ('Mike Johnson', 'Brakes'),
    ('David Williams', 'Transmission'),
    ('Robert Brown', 'Electrical Systems'),
    ('James Davis', 'Suspension');
