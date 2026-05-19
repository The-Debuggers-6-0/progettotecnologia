# Slice 3 — Location + Guide/Operatori

**Stato:** completato

---

## 1. Schema DB aggiunto

3 nuove tabelle + 1 ALTER in `sql/schema.sql`:

| Tabella | Scopo |
|---|---|
| `locations` | Sedi fisiche strutturate (nome, città, indirizzo, coordinate) |
| `guides` | Guide/operatori con bio, foto, lingue parlate |
| `experience_guides` | Relazione N:N tra esperienze e guide |

Aggiornamento esistente:
- `ALTER TABLE experiences ADD COLUMN location_id INT DEFAULT NULL` — FK verso `locations` (complementare al campo testo libero `location`)

### Dettaglio campi `locations`
- `name VARCHAR(200)` — nome della sede (es. "Museo Nazionale")
- `city VARCHAR(100)` — città
- `address VARCHAR(300)` — indirizzo completo
- `latitude / longitude DECIMAL(10,7)` — coordinate per future integrazioni con mappe
- `description TEXT` — note aggiuntive

### Dettaglio campi `guides`
- `name / surname VARCHAR(100)` — anagrafica
- `bio TEXT` — biografia/presentazione
- `photo_filename VARCHAR(255)` — foto profilo (file in `uploads/guides/`)
- `languages VARCHAR(200)` — lingue parlate (es. "Italiano, Inglese")
- `email / phone` — contatti
- `is_active TINYINT(1)` — solo le guide attive sono mostrate al pubblico e selezionabili nel form

### Dettaglio campi `experience_guides`
- Chiave primaria composta `(experience_id, guide_id)`
- `ON DELETE CASCADE` su entrambe le FK — la riga viene rimossa se si cancella esperienza o guida

---

## 2. File PHP creati

### Admin CRUD — Location

| File | Scopo |
|---|---|
| `admin/locations.php` | Lista location con contatore esperienze collegate |
| `admin/locations-form.php` | Crea/modifica location (nome, città, indirizzo, coordinate) |
| `admin/locations-delete.php` | Elimina location (ON DELETE SET NULL su experiences.location_id) |

### Admin CRUD — Guide

| File | Scopo |
|---|---|
| `admin/guides.php` | Lista guide con foto, lingue, badge stato, contatore esperienze |
| `admin/guides-form.php` | Crea/modifica guida + upload foto profilo |
| `admin/guides-delete.php` | Elimina guida + rimuove file foto dal filesystem |

### Aggiornati

| File | Modifica |
|---|---|
| `admin/experiences-form.php` | Aggiunto dropdown `location_id`, checkbox guide, query alle nuove tabelle |
| `tour-detail.php` | JOIN con `locations`, query guide assegnate, costruzione `guides_html` inline |

---

## 3. Template HTML creati/aggiornati

| File | Placeholder principali |
|---|---|
| `skins/admin/dtml/locations-list.html` | foreach: loc_name, loc_city, loc_address, loc_exp_count, loc_edit_url, loc_delete_url |
| `skins/admin/dtml/locations-form.html` | loc_name, loc_city, loc_address, loc_description, loc_latitude, loc_longitude, error |
| `skins/admin/dtml/guides-list.html` | foreach: guide_photo, guide_name, guide_languages, guide_email, guide_exp_count, guide_status, guide_edit_url, guide_delete_url |
| `skins/admin/dtml/guides-form.html` | guide_name, guide_surname, guide_bio, guide_languages, guide_email, guide_phone, guide_active, photo_preview, error |
| `skins/admin/dtml/experiences-form.html` | Aggiunto: location_options (dropdown), guides_checkboxes (HTML inline) |
| `skins/tour/dtml/tour-detail.html` | Aggiunto: has_guides, guides_html |

---

## 4. Note tecniche

### Location: testo libero vs strutturata
`experiences` mantiene sia il campo `location VARCHAR` (testo libero, già esistente) che il nuovo `location_id FK`.
In `tour-detail.php` la logica di visualizzazione è: se `location_id` è valorizzato mostra `name, city — address` della location; altrimenti usa il testo libero `location`. Questo garantisce retrocompatibilità con le esperienze create prima dello Slice 3.

### Guide checkbox senza foreach
Nel form esperienze, le checkbox delle guide sono costruite in PHP come stringa HTML e iniettate nel placeholder `<[guides_checkboxes]>`. Questo evita il conflitto del template engine (stesso placeholder dentro/fuori foreach) e permette di pre-selezionare le guide già assegnate.

### Guide HTML nel frontend senza foreach
Analogamente, `guides_html` in `tour-detail.php` è costruito in PHP con foto, nome, lingue e bio di ciascuna guida. Il template usa `<[if!empty has_guides]>` per nascondere la sezione se nessuna guida è assegnata.

### Foto guide
- Directory: `uploads/guides/`
- Stessa logica di `uploads/experiences/`: tipi JPG/PNG/WebP, max 5 MB, filename `{guide_id}_{uniqid()}.{ext}`
- Alla cancellazione della guida: il file viene rimosso dal filesystem prima del DELETE

### Sidebar admin aggiornata
Aggiunte due voci nel menu CONTENUTO di `frame-private.html`:
- **Location** (icona `fas fa-map-marker-alt`)
- **Guide** (icona `fas fa-hiking`)

---

## 5. Come verificare lo slice

1. Esegui in phpMyAdmin le nuove istruzioni SQL (a partire da `-- Slice 3` in `schema.sql`)
2. `/admin/locations.php` → crea una location (es. "Colosseo, Roma")
3. `/admin/guides.php` → crea una guida con foto e lingue
4. `/admin/experiences.php` → modifica un'esperienza → assegna la location dal dropdown e la guida dalla checkbox
5. `/tour-detail.php?id=1` → la card mostra il luogo strutturato; sotto la descrizione compare la sezione "Le tue guide" con foto e bio
6. Cancella la guida → la foto viene rimossa dal filesystem
7. Cancella la location → `experiences.location_id` diventa NULL, la scheda mostra il testo libero (se valorizzato)
