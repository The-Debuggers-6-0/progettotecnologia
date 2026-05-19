# Slice 4 — TimeSlots e disponibilità

**Stato:** completato

---

## 1. Schema DB aggiunto

1 nuova tabella in `sql/schema.sql`:

| Tabella | Scopo |
|---|---|
| `time_slots` | Singole date/orari disponibili per ogni esperienza |

### Dettaglio campi `time_slots`

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `experience_id` | INT NOT NULL FK | → `experiences(id)` ON DELETE CASCADE |
| `start_datetime` | DATETIME NOT NULL | Data e ora di inizio |
| `capacity` | INT NOT NULL DEFAULT 10 | Posti totali per lo slot |
| `booked_count` | INT NOT NULL DEFAULT 0 | Incrementato dallo Slice 5 (prenotazioni) |
| `is_active` | TINYINT(1) DEFAULT 1 | Solo slot attivi sono mostrati al pubblico |
| `notes` | VARCHAR(500) | Testo libero: punto di ritrovo, abbigliamento… |
| `created_at` | TIMESTAMP | |

**INDEX** su `start_datetime` per filtrare rapidamente gli slot futuri.

**ON DELETE CASCADE**: se si elimina un'esperienza, tutti i suoi slot vengono rimossi automaticamente.

---

## 2. File PHP creati

### Admin CRUD — Slot / Calendario

| File | Scopo |
|---|---|
| `admin/time-slots.php` | Lista slot (tutti o filtrati per `?exp=ID`); mostra data, ora, capienza, prenotati, disponibili, stato |
| `admin/time-slots-form.php` | Crea/modifica slot: dropdown esperienza, date+time picker, capienza, note, toggle attivo |
| `admin/time-slots-delete.php` | Elimina slot; torna alla lista filtrata per l'esperienza |

### Aggiornati

| File | Modifica |
|---|---|
| `admin/experiences.php` | Aggiunto placeholder `exp_slots_url` → pulsante calendario blu per ogni esperienza |
| `admin/index.php` | Query estese a tutte le tabelle; passa 9 placeholder alla dashboard (esperienze attive/bozza, categorie, location, guide, slot futuri, utenti, gruppi) |
| `login.php` | Dopo il login verifica il gruppo: admin → redirect a `/admin/index.php`; utente normale → `/index.php` |
| `tour-detail.php` | Query prossimi 8 slot attivi futuri; costruisce `$slotsHtml` inline; passa `slots_html` e `has_slots` al block |
| `skins/admin/dtml/dashboard.html` | Completamente riscritto: 7 card con contatori e icone Font Awesome, sezione accesso rapido, card cliccabili con `stretched-link` |
| `skins/admin/css/style.css` | Compat layer ampliato con: `text-end`, `form-label`, `fs-1…6`, `row.g-3`, `align-middle`, `stretched-link`, `badge bg-info`, `badge bg-primary` |

---

## 3. Template HTML creati/aggiornati

| File | Placeholder principali |
|---|---|
| `skins/admin/dtml/time-slots-list.html` | page_title, new_url, base, has_rows, exp_filter_name; foreach: slot_exp_title, slot_date, slot_time, slot_capacity, slot_booked, slot_available, slot_avail_class, slot_status, slot_status_class, slot_edit_url, slot_delete_url |
| `skins/admin/dtml/time-slots-form.html` | form_title, back_url, error, exp_options (HTML inline), slot_date, slot_time, slot_capacity, slot_notes, slot_active_checked |
| `skins/admin/dtml/experiences-list.html` | Aggiunto pulsante calendario (blu, `fas fa-calendar-alt`) per ogni esperienza; nuovo placeholder `exp_slots_url` nel foreach |
| `skins/admin/dtml/frame-private.html` | Aggiunta voce "Slot / Calendario" (icona `fas fa-calendar-alt`) nel menu CONTENUTO, tra Guide e Prenotazioni |
| `skins/tour/dtml/tour-detail.html` | Aggiunta sezione "Quando è disponibile" con `<[if!empty has_slots]>` e `<[slots_html]>` nella colonna sinistra, dopo la sezione guide |

---

## 4. Note tecniche

### Navigazione admin: due entry point
1. **Sidebar**: `admin/time-slots.php` senza filtro → mostra tutti gli slot in ordine cronologico
2. **Lista esperienze**: icona calendario (blu) per ogni esperienza → `admin/time-slots.php?exp=ID` → mostra solo gli slot di quell'esperienza; pulsante "Nuovo slot" eredita il filtro `?exp=ID`

### Costruzione slot HTML nel frontend (no foreach)
Come per `guides_html`, gli slot vengono costruiti in PHP come stringa HTML e iniettati nel placeholder `<[slots_html]>`. Questo evita il conflitto del template engine (stesso placeholder dentro/fuori foreach).

### `booked_count` pre-calcolato
Il campo `booked_count` è un contatore denormalizzato: viene incrementato/decrementato dallo Slice 5 (bookings) invece di fare `COUNT(*)` a ogni richiesta. Questo semplifica la query di visualizzazione e velocizza la risposta.

### Disponibilità calcolata al volo
`disponibili = capacity - booked_count` — calcolata in PHP, non nel DB.
- `> 0` → badge verde "N posti liberi"
- `= 0` → badge rosso "Esaurito"

### Filtro slot futuri
La query nel frontend usa `start_datetime >= NOW()` per mostrare solo le date future (non ha senso mostrare slot già passati al pubblico).

### Layout data/ora nella lista pubblica
Data e ora di ogni slot sono mostrate su righe separate per leggibilità:
- **Riga 1:** data in grassetto (`dd/mm/YYYY`, `font-size:1.08rem`)
- **Riga 2:** ora preceduta dall'icona `flaticon-clock` (`H:i`, grigio, `font-size:.95rem`)
- Riga opzionale con il testo `notes` in grigio chiaro, `font-size:.92rem`
- Ogni slot ha `padding:1rem 0` e separatore `border-bottom:1px solid #f0f0f0`
- Badge disponibilità allineato a destra (`padding:.45em .85em`, `font-size:.88rem`)

---

## 5. Come verificare lo slice

1. Esegui in phpMyAdmin le nuove istruzioni SQL (a partire da `-- Slice 4` in `schema.sql`)
2. `/admin/experiences.php` → ogni esperienza mostra l'icona calendario blu
3. Click sull'icona → `/admin/time-slots.php?exp=1` → lista vuota con pulsante "Nuovo slot"
4. Crea 3 slot per l'esperienza: due con date future, uno passato
5. `/tour-detail.php?id=1` → nella colonna sinistra (sotto le guide) compare la sezione "Quando è disponibile" con i 2 slot futuri
6. Verifica badge verde (posti liberi) e colonna data/ora corretta
7. Crea uno slot con capienza = 0 prenotati (disponibili = capienza) → badge verde
8. Disattiva uno slot (`is_active = 0`) → non appare nel frontend
9. Elimina uno slot → redirect corretto alla lista filtrata per l'esperienza
10. Se nessun slot futuro è assegnato all'esperienza → la sezione "Quando è disponibile" NON appare (zero overhead)
