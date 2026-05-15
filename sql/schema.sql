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
