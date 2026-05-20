# Slice 8 — Popolamento dati, rifinitura UI e diagramma ER

**Stato:** completato
**Obiettivo:** chiudere la fase tradizionale del progetto con (1) il popolamento del database con dati realistici di esempio, (2) una rifinitura grafica dell'interfaccia pubblica e (3) la produzione del diagramma Entità-Relazione completo.

---

## 1. Inserimento dati nel DB

Lo slice non introduce nuove tabelle: lo schema è quello consolidato negli Slice 1–6. Il lavoro qui è di **popolamento** con dati coerenti col dominio (Esperienze & Tour), pensati per rendere navigabile e dimostrabile l'intera applicazione.

### `sql/clean.sql`
Script di reset: azzera tutte le tabelle di test mantenendo l'utente `admin` e il suo gruppo. Disabilita temporaneamente `FOREIGN_KEY_CHECKS` per poter usare `TRUNCATE` in qualsiasi ordine, poi le riabilita.

### `sql/seed.sql`
Da eseguire **dopo** `clean.sql`. Popola il database con un set completo e collegato:

| Entità | Quantità | Note |
|---|---|---|
| Utenti di test | 2 | `mario.rossi`, `giulia.verdi` (password: `password`) |
| Categorie | 3 | Tour italiani, Musei, Cammini |
| Location | 6 | Con coordinate lat/lng per le mappe |
| Guide | 5 | Bio, lingue, contatti realistici |
| Esperienze | 7 | Con descrizione lunga e corta, prezzo, durata, categoria, location |
| `experience_guides` | 8 | Include un'esperienza con doppia guida (Centro Roma) |
| Slot temporali | 26 | Giugno–agosto 2026, orari variati |
| Prenotazioni confermate | 4 | Con `booked_count` aggiornato sugli slot |
| Partecipanti | 7 | Collegati alle prenotazioni |
| Recensioni | 8 | Mix realistico: 4 positive (4–5★) + 4 negative/mediocri (1–3★) |

**Tecniche usate:**
- `SET @var = LAST_INSERT_ID()` per catturare gli ID auto-increment e riusarli nelle FK (es. `@mario_id`, `@booking1`).
- `UPDATE time_slots SET booked_count = N` dopo ogni prenotazione, per mantenere la coerenza tra `bookings` e la capienza residua mostrata in frontend.
- Escape degli apostrofi italiani (`d\'arte`, `un\'esperienza`) nelle stringhe.

**Recensioni — mix di rating:** per rendere realistica la sezione recensioni, oltre alle 4 valutazioni positive iniziali sono state aggiunte 4 recensioni negative/mediocri, con commenti coerenti (gruppo troppo numeroso, ritmo eccessivo, maltempo non gestito, sale chiuse senza preavviso). Ogni recensione usa una combinazione `(experience_id, user_id)` distinta, nel rispetto del vincolo `UNIQUE(experience_id, user_id)` che impedisce a un utente di recensire due volte la stessa esperienza.

| Esperienza | Utente | Rating |
|---|---|---|
| Colosseo (#1) | mario | 5★ |
| Cinque Terre (#5) | mario | 4★ |
| Uffizi (#3) | giulia | 5★ |
| Gondola (#2) | giulia | 4★ |
| Museo Napoli (#4) | mario | 2★ |
| Centro Roma (#7) | mario | 3★ |
| Sentiero Dei (#6) | giulia | 1★ |
| Museo Napoli (#4) | giulia | 3★ |

### `install-photos.php` (foto esperienze)
Script una-tantum (root del progetto) che scarica da Unsplash una foto di copertina per ognuna delle 7 esperienze, la salva in `uploads/experiences/{id}_cover.jpg` e inserisce il record in `experience_photos` (`is_cover = 1`). Usa `file_get_contents` con uno `stream_context` (user-agent + follow redirect + timeout) e scarta i download < 5 KB come falliti.

### `install-guide-photos.php` (foto guide) — **nuovo in questo slice**
Stessa logica del precedente, applicata alle 5 guide. Scarica i ritratti da Unsplash, li salva in `uploads/guides/{id}_cover.jpg` e aggiorna la colonna `guides.photo_filename` con uno `UPDATE`. Le foto sono scelte coerenti col profilo di ogni guida (es. guida alpina → ritratto outdoor, guida museale senior → ritratto più maturo).

> Entrambi gli script sono pensati per essere **eseguiti una volta** visitando l'URL nel browser e poi eliminabili. Richiedono connessione internet attiva.

---

## 2. Rifinitura UI

Interventi di leggibilità e gerarchia visiva sull'interfaccia pubblica (skin `tour`). Nessuna modifica al motore di templating né alla logica PHP di business.

### Carosello "Esperienze in evidenza" (homepage)

| Problema | Soluzione | File |
|---|---|---|
| Le frecce di navigazione comparivano solo al passaggio del mouse | `opacity`/`visibility` portate a sempre visibili | `skins/tour/css/style.css` |
| Il carosello "saltava" in altezza ad ogni transizione | `autoHeight: true` → `false` | `skins/tour/js/custom.js` |
| Titoli poco leggibili (testo blu scuro su foto) | Testo bianco + `text-shadow` a contorno + gradiente scuro fisso in cima alla card (`::before`) | `skins/tour/css/style.css` |
| Foto troppo "piene" sotto il testo | `blur(1.5px)` leggero di default, rimosso (`blur(0)`) all'hover con transizione | `skins/tour/css/style.css` |

### Dimensioni testo (leggibilità generale)
Ingranditi i font su più pagine, su richiesta, per migliorare la leggibilità. Questo intervento **prosegue** il lavoro avviato nello Slice 5 (vedi `docs/slice-5.md`, sezione "Migliorie UI"): alcuni elementi di `booking.html` e `booking-success.html` già ingranditi allora sono stati portati a valori ancora maggiori.
- **Homepage** (`home.html`): titoli sezione "Perché sceglierci" / "Esperienze in evidenza" a `2.8rem`, descrizione e card "feature" ingrandite, bottoni "Esplora le esperienze" e "Tutte le esperienze" allargati.
- **Prenotazione** (`booking.html`): titolo slot, data/ora, label, select, riepilogo prezzo e nota termini tutti ingranditi.
- **Form partecipanti** (`booking.php`, generato via JS): "Partecipante N", "Nome", "Cognome" e input ingranditi.
- **Conferma prenotazione** (`booking-success.html`): tutti i testi del riepilogo.
- **Filtri categoria** (`tours.php`): bottoni con padding e font maggiori, bordi arrotondati.
- **Footer** (`style.css`): testo base `14px → 18px`, titoli colonne `14px → 20px`.

### Cache-busting del CSS
Per evitare che il browser servisse la vecchia `style.css` dopo le modifiche, al link nel `frame-public.html` è stato aggiunto un parametro di versione:
```html
<link rel="stylesheet" href="<[base]>/skins/tour/css/style.css?v=4">
```
Incrementando il numero (`?v=N`) ad ogni modifica del CSS, il browser è obbligato a riscaricarlo senza bisogno di hard-refresh manuale.

---

## 3. Diagramma ER

Prodotto il diagramma Entità-Relazione completo dello schema (15 tabelle), in formato **Mermaid**.

![Diagramma ER](<Diagramma_ER(sfondo_bianco).png>)

### File creati
- **`docs/er-diagram.md`** — diagramma incorporato in un blocco ```` ```mermaid ```` + tabella-legenda delle relazioni con cardinalità e comportamenti `ON DELETE`. Si renderizza automaticamente nella preview di GitHub e di VS Code (estensione Mermaid).
- **`docs/er-diagram.mmd`** — sorgente puro del solo diagramma, da incollare su [mermaid.live](https://mermaid.live) per l'export in PNG/SVG.
- **`docs/Diagramma_ER(sfondo_bianco).png`** e **`docs/Diagramma ER.png`** — immagini renderizzate del diagramma (sfondo bianco e trasparente).

### Relazioni modellate
- **N:M:** `users`↔`groups` (via `users_has_groups`), `services`↔`groups` (via `services_has_groups`), `experiences`↔`guides` (via `experience_guides`).
- **Catena di prenotazione 1:N:** `experiences` → `time_slots` → `bookings` → `booking_participants`.
- **Recensioni:** `experiences` e `users` → `reviews`, con vincolo `UNIQUE(experience_id, user_id)` (una recensione per utente/esperienza).

### Nota sullo schema emersa dalla modellazione
La tabella `experiences` contiene **due** colonne per la località: `location` (VARCHAR, testo libero, dallo Slice 2) e `location_id` (FK → `locations`, aggiunta nello Slice 3). Non è una duplicazione accidentale ma un **pattern di fallback voluto**: la pagina di dettaglio (`tour-detail.php`) usa la location strutturata via `location_id` quando disponibile — con nome, città, indirizzo e coordinate per la mappa Leaflet — e ripiega sul testo libero `location` altrimenti. La colonna `location` è inoltre usata nelle liste (`index.php`, `tours.php`, admin) e nel form di modifica esperienza. Entrambe le colonne sono quindi attive e vanno mantenute.

---

## 4. Come verificare lo slice

1. **Reset + popolamento DB:** in phpMyAdmin eseguire `sql/clean.sql`, poi `sql/seed.sql`. Verificare che le tabelle siano popolate (7 esperienze, 5 guide, 26 slot, 8 recensioni, ecc.).
2. **Foto:** visitare `http://localhost/progettotecnologia/install-photos.php` e `.../install-guide-photos.php`. Controllare che i file appaiano in `uploads/experiences/` e `uploads/guides/` e che il DB sia aggiornato.
3. **Carosello homepage:** le frecce ← → sono visibili subito (senza hover), i titoli sono bianchi e leggibili su ogni foto, l'altezza non "salta" durante lo scorrimento, le immagini si schiariscono al passaggio del mouse.
4. **Leggibilità:** homepage, pagina prenotazione, conferma e footer mostrano i testi ingranditi.
5. **Recensioni:** nelle pagine di dettaglio delle esperienze #4 (Museo Napoli), #6 (Sentiero Dei) e #7 (Centro Roma) compaiono recensioni con rating basso (1–3★), così la media e la distribuzione delle stelle risultano realistiche e non solo positive.
6. **Diagramma ER:** aprire `docs/er-diagram.md` nella preview (GitHub/VS Code) o incollare `docs/er-diagram.mmd` su mermaid.live → il diagramma si renderizza senza errori di parsing.
