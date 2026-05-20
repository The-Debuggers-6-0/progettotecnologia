# Integrazione AdminLTE 3 — Backend Admin

**Stato:** completato (dopo Slice 2)

---

## Motivazione

Il PDF dei requisiti (Sezione 6) richiede esplicitamente due template separati:
- Frontend: Tour — Free Bootstrap 5 Travel Agency Website Template (ThemeWagon) — già integrato
- Backend: template admin orientato a usabilità e produttività

La Sezione 7 valuta l'admin su **Coverage, Usability, Functionality**. AdminLTE 3 è il template admin open-source più diffuso, riconoscibile e adatto all'esame.

---

## File scaricati

**AdminLTE 3.2.0** scaricato da GitHub releases e posizionato in:

| Sorgente ZIP | Destinazione nel progetto |
|---|---|
| `dist/css/` | `skins/admin/css/` |
| `dist/js/` | `skins/admin/js/` |
| `plugins/` | `skins/admin/plugins/` |

I file sono **locali** — nessuna dipendenza da CDN, funziona offline (es. all'esame).

---

## Modifiche apportate

### `skins/admin/dtml/frame-private.html`
Riscritto con la struttura HTML di AdminLTE 3:
- `<body class="hold-transition sidebar-mini">` + `.wrapper`
- Navbar superiore con hamburger, username, logout
- Sidebar scura con sezioni UTENTI / CONTENUTO e icone Font Awesome (voci aggiunte slice per slice — vedi tabella sotto)
- `.content-wrapper` → `.content` → `.container-fluid` → `<[body]>`
- Footer con nome applicazione
- Script: jQuery, Bootstrap 4, adminlte.min.js (tutti locali)

### Sidebar — voci nel menu CONTENUTO

| Voce | Icona | Aggiunta in |
|---|---|---|
| Esperienze | `fas fa-globe` | Slice 2 |
| Categorie | `fas fa-tag` | Slice 2 |
| Location | `fas fa-map-marker-alt` | Slice 3 |
| Guide | `fas fa-hiking` | Slice 3 |
| Slot / Calendario | `fas fa-calendar-alt` | Slice 4 |
| Prenotazioni | `fas fa-calendar-check` | Slice 5 |
| Recensioni | `fas fa-star` | Slice 6 |

### `skins/admin/css/style.css`
Layer di compatibilità Bootstrap 5 → Bootstrap 4, ampliato slice per slice:

| Classe BS5 | Equivalente aggiunto |
|---|---|
| `me-1/2/3`, `ms-1/2/3` | margin inline (Slice 2) |
| `gap-2` | flex gap (Slice 2) |
| `fw-semibold`, `fw-bold` | font-weight (Slice 2) |
| `badge bg-success/secondary/danger/warning/primary/info` | colori badge (Slice 2 + Slice 4) |
| `form-select` | dropdown nativo stilizzato (Slice 2) |
| `table-light` | intestazioni tabella (Slice 2) |
| `text-end` | allineamento destra (Slice 4) |
| `form-label` | label form (Slice 4) |
| `fs-1…fs-6` | font-size (Slice 4) |
| `row.g-3` | gutter righe (Slice 4) |
| `align-middle` su tabelle | allineamento verticale celle (Slice 4) |
| `stretched-link` | link che copre tutta la card (Slice 4) |

### Template blocchi — icone
Tutti i template admin usano Font Awesome (`fas fa-*`), incluso con AdminLTE 3.
Conversione da Bootstrap Icons (`bi bi-*`) avvenuta in due fasi:
- **Slice 2 (AdminLTE):** la maggior parte dei template (`users`, `groups`, `services`, liste e form principali)
- **Slice 4:** corretti i 3 file rimasti (`users-form.html`, `groups-form.html`, `services-form.html`) che avevano ancora `bi bi-arrow-left`

| Bootstrap Icons | Font Awesome |
|---|---|
| `bi bi-pencil` | `fas fa-edit` |
| `bi bi-trash` | `fas fa-trash` |
| `bi bi-plus-lg` / `bi bi-plus-circle` | `fas fa-plus` / `fas fa-plus-circle` |
| `bi bi-arrow-left` | `fas fa-arrow-left` |
| `bi bi-person-plus` | `fas fa-user-plus` |
| `bi bi-people` / `bi bi-collection` / `bi bi-shield-lock` | `fas fa-users` / `fas fa-layer-group` / `fas fa-shield-alt` (dashboard) |

---

## Uniformizzazione UI (post Slice 5)

Dopo il completamento dello Slice 5 è stata eseguita una revisione sistematica della coerenza visiva dell'admin:

### Intestazioni tabelle
Alcuni template usavano `thead class="table-dark"` (header nero) e altri `table-light` (header grigio). Uniformato tutto a `table-light`:

| File corretti | Da | A |
|---|---|---|
| `users-list.html` | `table-dark` | `table-light` |
| `groups-list.html` | `table-dark` | `table-light` |
| `services-list.html` | `table-dark` | `table-light` |

### Bottone "Nuovo"
Mix di colori (`btn-success`, `btn-warning`, `btn-primary`) e dimensioni (con/senza `btn-sm`). Uniformato a `btn-primary btn-sm` su tutti i template lista.

### Altre inconsistenze corrette
- Margine header: `mb-3` → `mb-4` in `users-list`, `groups-list`, `services-list`
- Stile card: `card border-0 shadow-sm` → `card` per uniformità con gli altri template lista

---

## Problemi risolti

### Titolo duplicato
`frame-private.html` aveva un `content-header` con `<[title]>` che si sommava all'`<h1>` già presente in ogni template blocco.
**Soluzione:** rimossa la sezione `content-header` dal frame — il titolo viene mostrato solo dal blocco.

### Bootstrap 4 vs Bootstrap 5
AdminLTE 3 usa Bootstrap 4, i nostri template blocchi usano classi Bootstrap 5 (`me-`, `fw-`, `badge bg-*`, `form-select`).
**Soluzione:** layer CSS di compatibilità in `style.css` che mappa le classi BS5 mancanti.
