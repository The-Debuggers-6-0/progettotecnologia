USE `progettotecnologia`;

CREATE TABLE IF NOT EXISTS home_features (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    icon        VARCHAR(100) NOT NULL DEFAULT 'flaticon-house',
    title       VARCHAR(150) NOT NULL,
    description VARCHAR(500),
    sort_order  INT          NOT NULL DEFAULT 0
);

INSERT INTO home_features (icon, title, description, sort_order)
SELECT * FROM (
    SELECT 'flaticon-house'      AS icon, 'Esperienze locali'     AS title,
           'Tour guidati, attività all\'aria aperta e avventure a contatto con la cultura locale.' AS description, 1 AS sort_order
    UNION ALL SELECT 'flaticon-mail',       'Prenotazione facile',
           'Prenota in pochi clic, ricevi conferma immediata e modifica quando vuoi.', 2
    UNION ALL SELECT 'flaticon-restaurant', 'Gastronomia & Cultura',
           'Scopri i sapori autentici e le tradizioni dei territori più belli d\'Italia.', 3
    UNION ALL SELECT 'flaticon-phone-call', 'Supporto 7/7',
           'Il nostro team è sempre disponibile per assisterti prima e durante l\'esperienza.', 4
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM home_features);
