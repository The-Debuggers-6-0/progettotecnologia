# Slice 6 — Recensioni

**Stato:** completato

---

## 1. Schema DB aggiunto

1 nuova tabella in `sql/schema.sql`:

| Tabella | Scopo |
|---|---|
| `reviews` | Recensione di un'esperienza da parte di un utente |

### Dettaglio campi `reviews`

| Campo | Tipo | Note |
|---|---|---|
| `id` | INT PK | |
| `experience_id` | INT FK | → `experiences(id)` ON DELETE CASCADE |
| `user_id` | INT FK | → `users(id)` ON DELETE CASCADE |
| `rating` | TINYINT NOT NULL | Valore da 1 a 5 (CHECK constraint) |
| `comment` | TEXT | Facoltativo |
| `created_at` | TIMESTAMP | |

**Vincoli:**

- `UNIQUE KEY uq_user_exp (experience_id, user_id)` — un utente può recensire ogni esperienza una sola volta
- `CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)` — voto valido

---

## 2. File PHP creati/aggiornati

### Frontend pubblico

| File | Modifica |
|---|---|
| `tour-detail.php` | Aggiunta logica recensioni: controllo permessi, gestione POST, query, HTML building, nuovi `setContent` |

### Admin CRUD — Recensioni

| File | Scopo |
|---|---|
| `admin/reviews.php` | Lista tutte le recensioni con esperienza, utente, voto (stelle), commento, data, bottone elimina |
| `admin/reviews-delete.php` | Elimina recensione per ID, redirect a lista |

### Aggiornati

| File | Modifica |
|---|---|
| `skins/admin/dtml/frame-private.html` | Aggiunta voce "Recensioni" (icona `fas fa-star`) nel menu CONTENUTO della sidebar, dopo "Prenotazioni" |
| `admin/index.php` | Aggiunto conteggio recensioni totali (`$totalReviews`) |
| `skins/admin/dtml/dashboard.html` | Aggiunta card "Recensioni" (icona arancione `fas fa-star`, link a `admin/reviews.php`) nella riga "Disponibilità & Utenti" |

---

## 3. Template HTML creati/aggiornati

| File | Placeholder principali |
|---|---|
| `skins/tour/dtml/tour-detail.html` | `avg_rating`, `review_count`, `has_reviews`, `reviews_html`, `review_form` |
| `skins/admin/dtml/reviews-list.html` | `has_rows`; foreach: `rev_id`, `rev_exp`, `rev_user`, `rev_email`, `rev_stars`, `rev_rating`, `rev_comment`, `rev_date`, `rev_del_url` |

---

## 4. Note tecniche

### Permessi di recensione

Un utente può lasciare una recensione solo se soddisfa tutte queste condizioni:

1. È loggato
2. Ha almeno una prenotazione **confermata** (`status = 'confirmed'`) per quell'esperienza
3. Non ha già lasciato una recensione per la stessa esperienza

Il controllo viene effettuato in `tour-detail.php` prima di mostrare il form, e ri-verificato al momento del POST.

```php
// Verifica prenotazione confermata
SELECT 1 FROM bookings b
JOIN time_slots ts ON ts.id = b.time_slot_id
WHERE ts.experience_id = ? AND b.user_id = ? AND b.status = 'confirmed'

// Verifica recensione già esistente
SELECT 1 FROM reviews WHERE experience_id = ? AND user_id = ?
```

### Visualizzazione condizionale del form

Il form mostra messaggi diversi a seconda dello stato:

| Condizione | Messaggio mostrato |
|---|---|
| Non loggato | Link ad Accedi |
| Già recensito | "Hai già recensito questa esperienza." |
| Nessuna prenotazione confermata | "Prenota questa esperienza per poter lasciare una recensione." |
| Può recensire | Form con stelle + textarea |
| POST riuscito | Alert verde "Grazie per la tua recensione!" |

### HTML inline (no foreach nel template)

Le recensioni vengono costruite come stringa HTML in PHP (`$reviewsHtml`) e iniettate nel placeholder `reviews_html`. Stesso pattern degli slot e delle guide: evita i conflitti del template engine con il `<[foreach]>`.

### Stelle Unicode

Il voto viene visualizzato come sequenza di caratteri Unicode `★` (stella piena) e `☆` (stella vuota):

```php
$stars = '';
for ($i = 1; $i <= 5; $i++) {
    $stars .= $i <= $r['rating'] ? '★' : '☆';
}
```

Usato sia nella pagina pubblica (`tour-detail.php`) che nella lista admin (`admin/reviews.php`).

### Media voto in tempo reale

La media viene calcolata in PHP su tutti i `rating` recuperati dalla query:

```php
$avgRating = $reviewCount > 0
    ? round(array_sum(array_column($reviewList, 'rating')) / $reviewCount, 1)
    : 0;
```

Mostrata nell'intestazione "Recensioni" solo se c'è almeno una recensione (`has_reviews` non vuoto).

---

## 5. Problemi risolti

### Nome blocco admin errato
`admin/reviews.php` usava `new_block('admin/reviews-list')`, che causava la ricerca del file in `skins/admin/dtml/admin/reviews-list.html` (path inesistente).

**Causa:** `new_block()` in `page.inc.php` costruisce il path come `skins/{skin}/dtml/{nome}` — il prefisso `admin/` nel nome si sommava alla skin `admin` già nel path.

**Soluzione:** rinominato in `new_block('reviews-list')`, coerente con tutti gli altri blocchi admin (`bookings-list`, `experiences-list`, ecc.).

---

## 6. Come verificare lo slice

1. Esegui in phpMyAdmin le istruzioni SQL (a partire da `-- Slice 6` in `schema.sql`)
2. `/tour-detail.php?id=1` — scroll fino a "Recensioni": compare "Nessuna recensione ancora. Sii il primo!"
3. Senza login → link "Accedi per lasciare una recensione"
4. Login con utente senza prenotazione confermata → "Prenota questa esperienza per poter lasciare una recensione."
5. Login con utente che ha una prenotazione confermata → appare il form stelle + commento
6. Invia recensione con voto 1–5 → alert verde, form scompare, recensione compare nella lista
7. Ricarica la pagina → media voto e conteggio compaiono nell'intestazione "Recensioni"
8. Secondo tentativo dello stesso utente → "Hai già recensito questa esperienza."
9. `/admin/reviews.php` → la recensione è in lista con tutte le colonne
10. Click "Elimina" → confirm dialog → recensione rimossa, redirect a lista
