-- =============================================================
--  Schema database: piattaforma di prenotazione Esperienze & Tour
--  Tecnologie del Web 2025/2026
--
--  Le tabelle saranno aggiunte slice-by-slice secondo la
--  metodologia iterativa richiesta dal docente.
--
--  Slice 1: users, groups, services, users_has_groups,
--           services_has_groups
--  Slice 2: experiences, categories, experience_photos
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

-- Slice 2: catalogo esperienze

CREATE TABLE categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE experiences (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    title             VARCHAR(200) NOT NULL,
    slug              VARCHAR(200) NOT NULL UNIQUE,
    description       TEXT,
    short_description VARCHAR(500),
    price             DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    duration_minutes  INT          DEFAULT NULL,
    max_participants  INT          DEFAULT NULL,
    category_id       INT          DEFAULT NULL,
    location          VARCHAR(200),
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE experience_photos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    experience_id INT          NOT NULL,
    filename      VARCHAR(255) NOT NULL,
    is_cover      TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order    INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE
);

-- Slice 3: locations, guides, experience_guides

CREATE TABLE locations (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    city        VARCHAR(100) NOT NULL,
    address     VARCHAR(300),
    description TEXT,
    latitude    DECIMAL(10,7),
    longitude   DECIMAL(10,7)
);

CREATE TABLE guides (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    surname        VARCHAR(100) NOT NULL,
    bio            TEXT,
    photo_filename VARCHAR(255),
    languages      VARCHAR(200),
    email          VARCHAR(100),
    phone          VARCHAR(30),
    is_active      TINYINT(1)  NOT NULL DEFAULT 1,
    created_at     TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE experience_guides (
    experience_id INT NOT NULL,
    guide_id      INT NOT NULL,
    PRIMARY KEY (experience_id, guide_id),
    FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE,
    FOREIGN KEY (guide_id)      REFERENCES guides(id)      ON DELETE CASCADE
);

ALTER TABLE experiences
    ADD COLUMN location_id INT DEFAULT NULL AFTER location,
    ADD FOREIGN KEY fk_exp_location (location_id) REFERENCES locations(id) ON DELETE SET NULL;

-- Slice 4: time_slots

CREATE TABLE time_slots (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    experience_id  INT NOT NULL,
    start_datetime DATETIME NOT NULL,
    capacity       INT NOT NULL DEFAULT 10,
    booked_count   INT NOT NULL DEFAULT 0,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    notes          VARCHAR(500),
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE,
    INDEX idx_start (start_datetime)
);

-- Slice 5: bookings, booking_participants

CREATE TABLE bookings (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    user_id            INT NOT NULL,
    time_slot_id       INT NOT NULL,
    participants_count INT NOT NULL DEFAULT 1,
    total_price        DECIMAL(8,2) NOT NULL,
    status             ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    notes              TEXT,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)      REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (time_slot_id) REFERENCES time_slots(id)  ON DELETE CASCADE
);

CREATE TABLE booking_participants (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT          NOT NULL,
    name       VARCHAR(100) NOT NULL,
    surname    VARCHAR(100) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Slice 6: reviews

CREATE TABLE reviews (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    experience_id INT      NOT NULL,
    user_id       INT      NOT NULL,
    rating        TINYINT  NOT NULL,
    comment       TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_exp (experience_id, user_id),
    FOREIGN KEY (experience_id) REFERENCES experiences(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
);
