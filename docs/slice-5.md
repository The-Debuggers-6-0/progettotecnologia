# Slice 5 — Prenotazioni e Partecipanti

**Stato:** completato

---

## 1. Schema DB aggiunto

2 nuove tabelle in `sql/schema.sql`:

| Tabella | Scopo |
|---|---|
| `bookings` | Prenotazione legata a uno slot e a un utente |
| `booking_participants` | Dati anagrafici di ogni partecipante |

### Dettaglio campi `bookings`

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK | |
| `user_id` | INT FK | → `users(id)` ON DELETE CASCADE |
| `time_slot_id` | INT FK | → `time_slots(id)` ON DELETE CASCADE |
| `participants_count` | INT NOT NULL | Numero di partecipanti |
| `total_price` | DECIMAL(8,2) | `price × participants_count`, calcolato al momento della prenotazione |
| `status` | ENUM | `pending` / `confirmed` / `cancelled` |
| `notes` | TEXT | Note libere dell'utente |
| `created_at` | TIMESTAMP | |

### Dettaglio campi `booking_participants`

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK | |
| `booking_id` | INT FK | → `bookings(id)` ON DELETE CASCADE |
| `name` | VARCHAR(100) | Nome partecipante |
| `surname` | VARCHAR(100) | Cognome partecipante |

---

## 2. File PHP creati

### Frontend — Flusso prenotazione

| File | Scopo |
|---|---|
| `booking.php` | Form prenotazione: seleziona partecipanti, inserisce nomi/cognomi, calcola totale in tempo reale |
| `booking-success.php` | Pagina di conferma con riepilogo prenotazione e lista partecipanti |
| `about.php` | Pagina statica "Chi siamo" — risolve il 404 del link in navbar |
| `contact.php` | Pagina statica "Contatti" con form dimostrativo — risolve il 404 del link in navbar |

### Admin CRUD — Prenotazioni

| File | Scopo |
|---|---|
| `admin/bookings.php` | Lista prenotazioni con esperienza, slot, utente, stato |
| `admin/bookings-form.php` | Visualizza dettagli + cambia stato (pending/confirmed/cancelled) |
| `admin/bookings-delete.php` | Elimina prenotazione e libera i posti nello slot |

### Aggiornati

| File | Modifica |
|---|---|
| `tour-detail.php` | Aggiunto `id` alla query degli slot; aggiunto pulsante "Prenota" per ogni slot con posti liberi |
| `admin/index.php` | Aggiunto conteggio prenotazioni totali e in attesa |
| `skins/admin/dtml/dashboard.html` | Aggiunta card "Prenotazioni totali" (con badge "N in attesa") |
| `skins/admin/dtml/frame-private.html` | Aggiunta voce "Prenotazioni" (icona `fas fa-calendar-check`) nel menu CONTENUTO della sidebar, dopo "Slot / Calendario" |

---

## 3. Template HTML creati/aggiornati

| File | Placeholder principali |
|---|---|
| `skins/tour/dtml/booking.html` | exp_title, exp_detail_url, error, slot_date, slot_time, slot_available, has_slot_notes, slot_notes, count_options, notes_val, exp_price |
| `skins/tour/dtml/booking-success.html` | booking_id, exp_title, exp_detail_url, slot_date, slot_time, participants_count, total_price, participants_html, tours_url |
| `skins/admin/dtml/bookings-list.html` | has_rows; foreach: book_id, book_exp, book_slot, book_user, book_email, book_parts, book_price, book_status, book_status_cls, book_edit_url, book_delete_url |
| `skins/admin/dtml/bookings-form.html` | booking_id, back_url, exp_title, slot_datetime, user_name, user_email, participants_count, total_price, has_notes, booking_notes, status_options, participants_html |
| `skins/tour/dtml/about.html` | nessun placeholder dinamico — contenuto statico |
| `skins/tour/dtml/contact.html` | nessun placeholder dinamico — form dimostrativo statico |

---

## 4. Note tecniche

### Carrello in sessione
Quando l'utente clicca "Prenota" su uno slot, `booking.php` salva in `$_SESSION['cart']` il `slot_id` selezionato. Se l'utente naviga via e torna, il carrello è già valorizzato. Il carrello viene cancellato (`unset($_SESSION['cart'])`) solo al completamento con successo della prenotazione.

### Transazione + controllo concorrenza
`booking.php` usa una transazione PDO con `SELECT ... FOR UPDATE` per ri-verificare la disponibilità dello slot all'interno della transazione stessa. Questo previene race condition (due utenti che prenotano l'ultimo posto contemporaneamente).

```php
db()->beginTransaction();
$chk = db()->prepare('SELECT capacity - booked_count AS avail FROM time_slots WHERE id = ? FOR UPDATE');
$chk->execute([$slotId]);
// ... insert + update booked_count ...
db()->commit();
```

### Campi partecipanti dinamici (vanilla JS)
Il form di prenotazione genera dinamicamente i campi nome/cognome per ogni partecipante tramite vanilla JS. Quando l'utente cambia il numero nel `<select>`, la funzione `upd()` aggiunge/rimuove blocchi `.prow` nel DOM, e aggiorna il totale in tempo reale (`unit_price × count`).

Il JS viene iniettato nel placeholder `<[javascript]>` del frame pubblico come stringa PHP — nessun file JS esterno aggiuntivo.

### `booked_count` aggiornato coerentemente
- **Nuova prenotazione**: `booked_count += participants_count`
- **Cancellazione (admin)**: `booked_count -= participants_count` (solo se il vecchio stato non era già `cancelled`)
- **Riattivazione da cancelled**: `booked_count += participants_count`
- **Eliminazione (admin)**: `booked_count -= participants_count` (solo se status ≠ `cancelled`)

### `total_price` snapshot
Il prezzo totale è calcolato e salvato al momento della prenotazione (`price × count`). Se in futuro il prezzo dell'esperienza cambia, le prenotazioni passate conservano il prezzo originale.

### Partecipanti HTML in booking-success e bookings-form
La lista partecipanti è costruita in PHP come stringa `<li>` e iniettata nel placeholder `participants_html`. Evita il foreach nel template (conflitto con il template engine).

---

## 5. Migliorie UI (post-sviluppo)

### Frontend pubblico — testi ingranditi

I font-size delle card esperienze, della pagina prenotazione e della pagina di conferma erano troppo piccoli. Tutti i valori sono stati aumentati in modo uniforme:

| Template | Elemento | Prima | Dopo |
|---|---|---|---|
| `skins/tour/dtml/tours.html` | Location card | `1rem` | `1.15rem` |
| `skins/tour/dtml/tours.html` | Titolo card | `1.6rem` | `1.9rem` |
| `skins/tour/dtml/tours.html` | Prezzo card | `1.1rem` | `1.25rem` |
| `skins/tour/dtml/booking.html` | Titolo slot | `1.35rem` | `1.6rem` |
| `skins/tour/dtml/booking.html` | Data/ora slot | `1.05rem` | `1.2rem` |
| `skins/tour/dtml/booking.html` | Label form | `1rem` | `1.15rem` |
| `skins/tour/dtml/booking.html` | Prezzo totale | `1.6rem` | `1.9rem` |
| `skins/tour/dtml/booking.html` | Bottone conferma | `1.1rem` | `1.25rem` |
| `skins/tour/dtml/booking-success.html` | Titolo "Grazie" | `default` | `1.6rem` |
| `skins/tour/dtml/booking-success.html` | Dettagli riepilogo | `.98rem` | `1.1rem` |
| `skins/tour/dtml/booking-success.html` | Prezzo totale | `1.2rem` | `1.4rem` |

### Header hero centralizzato

Le dimensioni dell'hero banner erano definite con stili inline in ogni singolo template, con valori inconsistenti tra le pagine (es. `booking.html` usava `2.6rem` invece di `3.2rem`).

**Soluzione:** rimossi tutti gli stili inline dall'`h1` e dal sottotitolo di ogni pagina; le dimensioni sono ora definite una sola volta in `skins/tour/dtml/frame-public.html`:

```css
.hero.hero-inner h1 { font-size: 3.2rem; margin-bottom: 1rem; }
.hero.hero-inner .text-white-50 { font-size: 1.15rem; margin-top: .5rem; }
```

Pagine aggiornate: `tours.html`, `tour-detail.html`, `booking.html`, `booking-success.html`, `login.html`, `register.html`.

> Modifica futura: basta toccare `frame-public.html`, tutte le pagine si aggiornano.

### Admin — uniformità visiva

Le tabelle e i bottoni dell'area admin presentavano stili disomogenei. Tutto allineato a un unico standard:

**Tabelle:**

| Template | Prima | Dopo |
|---|---|---|
| `users-list.html` | `table-dark` | `table-light` |
| `groups-list.html` | `table-dark` | `table-light` |
| `services-list.html` | `table-dark` | `table-light` |
| Tutti gli altri | `table-light` | invariato |

**Bottone "Nuovo":**

| Template | Prima | Dopo |
|---|---|---|
| `groups-list.html` | `btn-success btn-sm` | `btn-primary btn-sm` |
| `services-list.html` | `btn-warning btn-sm` | `btn-primary btn-sm` |
| `time-slots-list.html` | `btn-primary` (no sm) | `btn-primary btn-sm` |
| `guides-list.html` | `btn-primary` (no sm) | `btn-primary btn-sm` |
| `categories-list.html` | `btn-primary` (no sm) | `btn-primary btn-sm` |
| `locations-list.html` | `btn-primary` (no sm) | `btn-primary btn-sm` |
| `experiences-list.html` | `btn-primary` (no sm) | `btn-primary btn-sm` |

**Altre inconsistenze corrette:**
- `users-list.html`, `groups-list.html`, `services-list.html`: margine header da `mb-3` a `mb-4`
- Stile card: `card border-0 shadow-sm` → `card` semplice per uniformità con le altre liste

---

## 6. Come verificare lo slice

1. Esegui in phpMyAdmin le nuove istruzioni SQL (a partire da `-- Slice 5` in `schema.sql`)
2. `/tour-detail.php?id=1` → ogni slot con posti liberi mostra il pulsante blu "Prenota"
3. Click "Prenota" senza essere loggati → redirect a `/login.php`
4. Login → redirect a `/booking.php?slot=ID`
5. Form prenotazione: cambia numero partecipanti → campi nome/cognome si aggiungono dinamicamente, totale si aggiorna in tempo reale
6. Compila e conferma → `/booking-success.php?id=1` con riepilogo
7. `/tour-detail.php?id=1` → il badge dello slot mostra i posti diminuiti
8. `/admin/bookings.php` → la prenotazione è in lista con stato "In attesa"
9. Click modifica → cambia stato a "Confermata" o "Cancellata"
10. Cancellazione → i posti vengono liberati (badge slot torna a +N)
11. Eliminazione prenotazione → booked_count decrementato, slot torna disponibile
