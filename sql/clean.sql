-- =============================================================
--  clean.sql — svuota tutti i dati di test
--  Mantiene: utente admin, gruppo admin, relazione admin↔gruppo
--  Eseguire in phpMyAdmin su database progettotecnologia
-- =============================================================

USE `progettotecnologia`;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE reviews;
TRUNCATE TABLE booking_participants;
TRUNCATE TABLE bookings;
TRUNCATE TABLE time_slots;
TRUNCATE TABLE experience_guides;
TRUNCATE TABLE experience_photos;
TRUNCATE TABLE experiences;
TRUNCATE TABLE categories;
TRUNCATE TABLE locations;
TRUNCATE TABLE guides;

-- Rimuove tutti gli utenti tranne admin
DELETE FROM users WHERE username != 'admin';

SET FOREIGN_KEY_CHECKS = 1;
