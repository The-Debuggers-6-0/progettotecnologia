-- =============================================================
--  Schema database: piattaforma di prenotazione Esperienze & Tour
--  Tecnologie del Web 2025/2026
--
--  Le tabelle saranno aggiunte slice-by-slice secondo la
--  metodologia iterativa richiesta dal docente.
--
--  Slice 1: users, groups, services, users_has_groups,
--           services_has_groups
--  Slice 2: experiences, categories, experience_categories,
--           experience_photos
--  Slice 3: locations, guides, experience_guides
--  Slice 4: time_slots
--  Slice 5: bookings, booking_participants
--  Slice 6: reviews
-- =============================================================

CREATE DATABASE IF NOT EXISTS `progettotecnologia`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `progettotecnologia`;

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    name       VARCHAR(100),
    surname    VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE groups (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50)  NOT NULL,
    description VARCHAR(255)
);

CREATE TABLE services (
    username VARCHAR(50) PRIMARY KEY
);

CREATE TABLE users_has_groups (
    users_id  INT NOT NULL,
    groups_id INT NOT NULL,
    PRIMARY KEY (users_id, groups_id),
    FOREIGN KEY (users_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (groups_id) REFERENCES groups(id) ON DELETE CASCADE
);

CREATE TABLE services_has_groups (
    services_username VARCHAR(50) NOT NULL,
    groups_id         INT NOT NULL,
    PRIMARY KEY (services_username, groups_id),
    FOREIGN KEY (services_username) REFERENCES services(username),
    FOREIGN KEY (groups_id)         REFERENCES groups(id) ON DELETE CASCADE
);

