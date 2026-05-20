-- =============================================================
--  seed.sql — dati di esempio per Esperienze & Tour
--  Eseguire DOPO clean.sql su database progettotecnologia
--
--  Credenziali utenti di test (tutti con password "password"):
--    admin        / password  (già esistente)
--    mario.rossi  / password
--    giulia.verdi / password
-- =============================================================

USE `progettotecnologia`;

-- ============================================================
-- UTENTI DI TEST
-- ============================================================

INSERT INTO users (username, email, password, name, surname) VALUES
('mario.rossi', 'mario.rossi@test.it',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Mario', 'Rossi');
SET @mario_id = LAST_INSERT_ID();

INSERT INTO users (username, email, password, name, surname) VALUES
('giulia.verdi', 'giulia.verdi@test.it',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'Giulia', 'Verdi');
SET @giulia_id = LAST_INSERT_ID();

-- ============================================================
-- CATEGORIE
-- ============================================================

INSERT INTO categories (id, name, description) VALUES
(1, 'Tour italiani',  'Scopri le città d\'arte e i borghi più belli d\'Italia con guide esperte del territorio.'),
(2, 'Musei',          'Visite guidate ai più importanti musei e gallerie d\'arte italiani.'),
(3, 'Cammini',        'Trekking ed escursioni sui percorsi naturalistici più suggestivi della penisola.');

-- ============================================================
-- LOCATION (con coordinate per le mappe)
-- ============================================================

INSERT INTO locations (id, name, city, address, latitude, longitude) VALUES
(1, 'Colosseo',              'Roma',      'Piazza del Colosseo, 1, 00184 Roma',             41.8902102, 12.4922309),
(2, 'Piazza San Marco',      'Venezia',   'Piazza San Marco, 30124 Venezia',                45.4340600, 12.3388300),
(3, 'Galleria degli Uffizi', 'Firenze',   'Piazzale degli Uffizi, 6, 50122 Firenze',        43.7682800, 11.2556000),
(4, 'Museo Nazionale',       'Napoli',    'Piazza Museo, 19, 80135 Napoli',                 40.8526300, 14.2681200),
(5, 'Riomaggiore',           'La Spezia', 'Via Colombo, 19017 Riomaggiore SP',              44.0998100,  9.7376000),
(6, 'Positano',              'Salerno',   'Viale Pasitea, 84017 Positano SA',               40.6281000, 14.4850000);

-- ============================================================
-- GUIDE (5)
-- ============================================================

INSERT INTO guides (id, name, surname, bio, languages, email, phone, is_active) VALUES
(1, 'Marco',   'Rossi',    'Storico dell\'arte con 15 anni di esperienza nel raccontare Roma antica. Specializzato in archeologia romana e storia imperiale.',
                            'Italiano, Inglese',            'marco.rossi@guide.it',    '+39 333 1001001', 1),
(2, 'Sofia',   'Bianchi',  'Storica dell\'arte fiorentina, laurea alla Normale di Pisa. Appassionata di Rinascimento e pittura italiana.',
                            'Italiano, Francese, Spagnolo', 'sofia.bianchi@guide.it',  '+39 333 2002002', 1),
(3, 'Luca',    'Ferrari',  'Guida alpina certificata CAI, esperto di trekking costiero e sentieri appenninici. 10 anni sulle Cinque Terre e la Costiera Amalfitana.',
                            'Italiano, Inglese, Tedesco',   'luca.ferrari@guide.it',   '+39 333 3003003', 1),
(4, 'Giulia',  'Marino',   'Veneziana doc, narratrice appassionata della Serenissima. Specializzata in storia della Repubblica di Venezia e architettura gotica veneziana.',
                            'Italiano, Inglese',            'giulia.marino@guide.it',  '+39 333 4004004', 1),
(5, 'Antonio', 'De Luca',  'Napoletano, storico e guida museale con specializzazione in arte greco-romana e reperti del Vesuvio. Collabora col Museo Nazionale da 12 anni.',
                            'Italiano, Inglese',            'antonio.deluca@guide.it', '+39 333 5005005', 1);

-- ============================================================
-- ESPERIENZE (7)
-- ============================================================

INSERT INTO experiences (id, title, slug, description, short_description, price, duration_minutes, max_participants, category_id, location_id, is_active) VALUES

(1, 'Tour del Colosseo e Foro Romano', 'tour-colosseo-foro-romano',
 'Immergiti nella storia dell\'antica Roma con una visita guidata al Colosseo e al Foro Romano. Il nostro esperto ti condurrà tra gladiatori, senatori e imperatori che hanno plasmato la civiltà occidentale. Il tour include accesso prioritario salta-coda e una spiegazione approfondita delle tecniche costruttive romane.',
 'Visita guidata al Colosseo e Foro Romano con accesso prioritario. Un viaggio nel cuore dell\'antica Roma.',
 35.00, 180, 15, 1, 1, 1),

(2, 'Giro in Gondola a Venezia', 'giro-gondola-venezia',
 'Vivi la magia di Venezia a bordo di una gondola tradizionale, scivolando tra i canali silenziosi lontano dalla ressa turistica. Il gondoliere ti guiderà attraverso calli nascoste, ponti storici e palazzi nobiliari raccontando aneddoti e leggende della Serenissima. Un\'esperienza romantica e indimenticabile.',
 'Un tour in gondola tra i canali nascosti di Venezia. Romantico, autentico, indimenticabile.',
 45.00, 90, 6, 1, 2, 1),

(3, 'Visita guidata agli Uffizi di Firenze', 'visita-uffizi-firenze',
 'Esplora la più importante raccolta d\'arte rinascimentale al mondo con una guida specializzata. Dal Botticelli alla Nascita di Venere, da Leonardo a Michelangelo: Sofia ti accompagnerà in un viaggio tra capolavori assoluti, con aneddoti esclusivi e curiosità che i libri di testo non raccontano.',
 'Tour guidato alla Galleria degli Uffizi tra Botticelli, Leonardo e Michelangelo.',
 28.00, 150, 12, 2, 3, 1),

(4, 'Museo Nazionale di Napoli', 'museo-nazionale-napoli',
 'Il Museo Nazionale di Napoli custodisce i più straordinari reperti di Pompei ed Ercolano. La visita guidata con Antonio ti porterà tra mosaici, bronzi, affreschi e il celebre Gabinetto Segreto, ricostruendo la vita quotidiana degli antichi romani strappata all\'eruzione del Vesuvio del 79 d.C.',
 'Visita guidata al Museo Nazionale di Napoli: reperti di Pompei, bronzi e mosaici unici al mondo.',
 22.00, 120, 18, 2, 4, 1),

(5, 'Cammino delle Cinque Terre', 'cammino-cinque-terre',
 'Un trekking mozzafiato lungo il Sentiero Azzurro che collega i cinque borghi più pittoreschi della Liguria. Riomaggiore, Manarola, Corniglia, Vernazza e Monterosso: panorami sul mar Ligure, vigneti terrazzati e profumo di basilico. Luca Ferrari ti guiderà con sicurezza lungo i tratti più panoramici, raccontando storia e cultura locale.',
 'Trekking guidato tra i cinque borghi della Liguria con vista mozzafiato sul mar Ligure.',
 30.00, 300, 10, 3, 5, 1),

(6, 'Sentiero degli Dei ad Amalfi', 'sentiero-degli-dei-amalfi',
 'Il Sentiero degli Dei è considerato uno dei percorsi di trekking più belli d\'Europa. Camminando tra Bomerano e Positano, a 600 metri sul livello del mare, godrai di viste spettacolari sulla Costiera Amalfitana, sul Golfo di Salerno e sulle isole Li Galli. Luca Ferrari ti guiderà in tutta sicurezza lungo questo percorso leggendario.',
 'Trekking sul leggendario Sentiero degli Dei con viste spettacolari sulla Costiera Amalfitana.',
 25.00, 240, 10, 3, 6, 1),

(7, 'Tour del Centro Storico di Roma', 'tour-centro-storico-roma',
 'Un tour a piedi nel cuore della Roma barocca e rinascimentale: Piazza Navona, il Pantheon, Campo de\' Fiori e Fontana di Trevi. Marco e Sofia ti racconteranno storie, scandali e segreti dei papi e degli artisti che hanno trasformato Roma nella città eterna. Il tour ideale per chi visita Roma per la prima volta.',
 'Tour a piedi tra Pantheon, Piazza Navona e Fontana di Trevi nel cuore di Roma.',
 20.00, 150, 20, 1, 1, 1);

-- ============================================================
-- GUIDE ASSEGNATE ALLE ESPERIENZE
-- ============================================================

INSERT INTO experience_guides (experience_id, guide_id) VALUES
(1, 1),  -- Colosseo → Marco
(2, 4),  -- Gondola → Giulia
(3, 2),  -- Uffizi → Sofia
(4, 5),  -- Museo Napoli → Antonio
(5, 3),  -- Cinque Terre → Luca
(6, 3),  -- Sentiero Dei → Luca
(7, 1),  -- Centro Roma → Marco
(7, 2);  -- Centro Roma → Sofia (doppia guida)

-- ============================================================
-- SLOT TEMPORALI (giugno–agosto 2026, orari variati)
-- Slot id 1–4:   Exp 1 Colosseo
-- Slot id 5–8:   Exp 2 Gondola
-- Slot id 9–12:  Exp 3 Uffizi
-- Slot id 13–15: Exp 4 Museo Napoli
-- Slot id 16–19: Exp 5 Cinque Terre
-- Slot id 20–22: Exp 6 Sentiero Dei
-- Slot id 23–26: Exp 7 Centro Roma
-- ============================================================

-- Exp 1 — Tour Colosseo
INSERT INTO time_slots (experience_id, start_datetime, capacity, booked_count, is_active, notes) VALUES
(1, '2026-06-06 09:00:00', 15, 0, 1, 'Punto di ritrovo: ingresso principale Colosseo'),
(1, '2026-06-06 14:30:00', 15, 0, 1, 'Punto di ritrovo: ingresso principale Colosseo'),
(1, '2026-07-11 09:00:00', 15, 0, 1, 'Punto di ritrovo: ingresso principale Colosseo'),
(1, '2026-08-08 10:00:00', 15, 0, 1, 'Punto di ritrovo: ingresso principale Colosseo');

-- Exp 2 — Gondola Venezia
INSERT INTO time_slots (experience_id, start_datetime, capacity, booked_count, is_active, notes) VALUES
(2, '2026-06-13 10:00:00', 6, 0, 1, 'Punto di ritrovo: Ponte di Rialto, lato mercato'),
(2, '2026-06-13 15:00:00', 6, 0, 1, 'Punto di ritrovo: Ponte di Rialto, lato mercato'),
(2, '2026-07-18 10:00:00', 6, 0, 1, 'Punto di ritrovo: Ponte di Rialto, lato mercato'),
(2, '2026-08-15 17:00:00', 6, 0, 1, 'Tour al tramonto — punto di ritrovo: Ponte di Rialto');

-- Exp 3 — Uffizi Firenze
INSERT INTO time_slots (experience_id, start_datetime, capacity, booked_count, is_active, notes) VALUES
(3, '2026-06-07 09:30:00', 12, 0, 1, 'Punto di ritrovo: biglietteria Uffizi, ingresso sud'),
(3, '2026-06-21 14:00:00', 12, 0, 1, 'Punto di ritrovo: biglietteria Uffizi, ingresso sud'),
(3, '2026-07-05 09:30:00', 12, 0, 1, 'Punto di ritrovo: biglietteria Uffizi, ingresso sud'),
(3, '2026-08-23 10:00:00', 12, 0, 1, 'Punto di ritrovo: biglietteria Uffizi, ingresso sud');

-- Exp 4 — Museo Nazionale Napoli
INSERT INTO time_slots (experience_id, start_datetime, capacity, booked_count, is_active, notes) VALUES
(4, '2026-06-12 10:00:00', 18, 0, 1, 'Punto di ritrovo: ingresso principale Museo Nazionale'),
(4, '2026-07-10 10:00:00', 18, 0, 1, 'Punto di ritrovo: ingresso principale Museo Nazionale'),
(4, '2026-08-14 15:30:00', 18, 0, 1, 'Punto di ritrovo: ingresso principale Museo Nazionale');

-- Exp 5 — Cinque Terre
INSERT INTO time_slots (experience_id, start_datetime, capacity, booked_count, is_active, notes) VALUES
(5, '2026-06-07 08:30:00', 10, 0, 1, 'Punto di ritrovo: stazione di Riomaggiore. Scarpe da trekking obbligatorie.'),
(5, '2026-06-21 08:30:00', 10, 0, 1, 'Punto di ritrovo: stazione di Riomaggiore. Scarpe da trekking obbligatorie.'),
(5, '2026-07-12 08:30:00', 10, 0, 1, 'Punto di ritrovo: stazione di Riomaggiore. Scarpe da trekking obbligatorie.'),
(5, '2026-08-09 08:30:00', 10, 0, 1, 'Punto di ritrovo: stazione di Riomaggiore. Scarpe da trekking obbligatorie.');

-- Exp 6 — Sentiero degli Dei
INSERT INTO time_slots (experience_id, start_datetime, capacity, booked_count, is_active, notes) VALUES
(6, '2026-06-14 08:00:00', 10, 0, 1, 'Punto di ritrovo: piazza Bomerano, Agerola. Scarpe da trekking obbligatorie.'),
(6, '2026-07-19 08:00:00', 10, 0, 1, 'Punto di ritrovo: piazza Bomerano, Agerola. Scarpe da trekking obbligatorie.'),
(6, '2026-08-16 08:00:00', 10, 0, 1, 'Punto di ritrovo: piazza Bomerano, Agerola. Scarpe da trekking obbligatorie.');

-- Exp 7 — Centro Storico Roma
INSERT INTO time_slots (experience_id, start_datetime, capacity, booked_count, is_active, notes) VALUES
(7, '2026-06-05 09:30:00', 20, 0, 1, 'Punto di ritrovo: Fontana di Trevi, scalini laterali'),
(7, '2026-06-05 15:00:00', 20, 0, 1, 'Punto di ritrovo: Fontana di Trevi, scalini laterali'),
(7, '2026-07-03 09:30:00', 20, 0, 1, 'Punto di ritrovo: Fontana di Trevi, scalini laterali'),
(7, '2026-08-07 17:00:00', 20, 0, 1, 'Tour serale — punto di ritrovo: Fontana di Trevi, scalini laterali');

-- ============================================================
-- PRENOTAZIONI CONFERMATE
-- Slot 1  = Colosseo      06/06 09:00
-- Slot 5  = Gondola       13/06 10:00
-- Slot 9  = Uffizi        07/06 09:30
-- Slot 16 = Cinque Terre  07/06 08:30
-- ============================================================

-- mario.rossi → Colosseo (slot 1), 2 partecipanti
INSERT INTO bookings (user_id, time_slot_id, participants_count, total_price, status) VALUES
(@mario_id, 1, 2, 70.00, 'confirmed');
SET @booking1 = LAST_INSERT_ID();
UPDATE time_slots SET booked_count = 2 WHERE id = 1;

-- mario.rossi → Cinque Terre (slot 16), 1 partecipante
INSERT INTO bookings (user_id, time_slot_id, participants_count, total_price, status) VALUES
(@mario_id, 16, 1, 30.00, 'confirmed');
SET @booking2 = LAST_INSERT_ID();
UPDATE time_slots SET booked_count = 1 WHERE id = 16;

-- giulia.verdi → Uffizi (slot 9), 2 partecipanti
INSERT INTO bookings (user_id, time_slot_id, participants_count, total_price, status) VALUES
(@giulia_id, 9, 2, 56.00, 'confirmed');
SET @booking3 = LAST_INSERT_ID();
UPDATE time_slots SET booked_count = 2 WHERE id = 9;

-- giulia.verdi → Gondola (slot 5), 2 partecipanti
INSERT INTO bookings (user_id, time_slot_id, participants_count, total_price, status) VALUES
(@giulia_id, 5, 2, 90.00, 'confirmed');
SET @booking4 = LAST_INSERT_ID();
UPDATE time_slots SET booked_count = 2 WHERE id = 5;

-- ============================================================
-- PARTECIPANTI
-- ============================================================

INSERT INTO booking_participants (booking_id, name, surname) VALUES
(@booking1, 'Mario',  'Rossi'),
(@booking1, 'Laura',  'Rossi'),
(@booking2, 'Mario',  'Rossi'),
(@booking3, 'Giulia', 'Verdi'),
(@booking3, 'Paolo',  'Verdi'),
(@booking4, 'Giulia', 'Verdi'),
(@booking4, 'Marco',  'Bianchi');

-- ============================================================
-- RECENSIONI
-- ============================================================

INSERT INTO reviews (experience_id, user_id, rating, comment) VALUES
(1, @mario_id, 5,
 'Un\'esperienza straordinaria! Marco è una guida eccezionale, conosce ogni pietra del Colosseo. L\'accesso salta-coda ha fatto la differenza: niente code e tanto tempo per godersi il posto. Consigliatissimo!'),

(5, @mario_id, 4,
 'Panorami mozzafiato e guida preparatissima. Il sentiero è impegnativo ma ne vale assolutamente la pena. Consiglio scarpe da trekking serie e acqua abbondante. Tornerò sicuramente.'),

(3, @giulia_id, 5,
 'Sofia è una guida incredibile, appassionata e coltissima. Ha reso ogni dipinto vivo con storie che non trovi sui libri. La Nascita di Venere vista con lei è stata un\'emozione vera. Da fare assolutamente.'),

(2, @giulia_id, 4,
 'Venezia in gondola è magica. Abbiamo percorso canali che non avremmo mai trovato da soli. Il gondoliere era simpaticissimo. Unico neo: un po\' corto, avrei voluto continuare ancora!'),

(4, @mario_id, 2,
 'Museo interessante ma visita deludente. Eravamo davvero troppi nel gruppo, faticavo a sentire la guida e a vedere i reperti. Alcune sale erano chiuse senza preavviso. Mi aspettavo di meglio per il prezzo pagato.'),

(7, @mario_id, 3,
 'Tour nella media. La guida era preparata ma il ritmo troppo veloce: corsa da una piazza all\'altra senza tempo per fermarsi a fotografare. Il gruppo era enorme. Carino ma niente di indimenticabile.'),

(6, @giulia_id, 1,
 'Esperienza pessima. Partiti nonostante il meteo incerto, a metà sentiero pioggia battente e nessun piano alternativo. Organizzazione scadente, ci siamo sentiti abbandonati. Sconsigliato, soprattutto in caso di previsioni dubbie.'),

(4, @giulia_id, 3,
 'Reperti bellissimi ma il Gabinetto Segreto era chiuso il giorno della nostra visita e non ce l\'avevano detto al momento della prenotazione. La guida si è impegnata, ma resta l\'amaro in bocca. Sufficiente.');
