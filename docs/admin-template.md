# Integrazione AdminLTE 3 — Backend Admin

**Stato:** completato (dopo Slice 2)

---

## Motivazione

Il PDF dei requisiti (Sezione 6) richiede esplicitamente due template separati:
- Frontend: Tour by Untree.co (ThemeWagon) — già integrato
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
- Sidebar scura con sezioni UTENTI / CONTENUTO e icone Font Awesome
- `.content-wrapper` → `.content` → `.container-fluid` → `<[body]>`
- Footer con nome applicazione
- Script: jQuery, Bootstrap 4, adminlte.min.js (tutti locali)

### `skins/admin/css/style.css`
Aggiunto layer di compatibilità Bootstrap 5 → Bootstrap 4:
- Classi spacing: `me-*`, `ms-*`, `gap-*`
- Font weight: `fw-semibold`, `fw-bold`
- Badge: `bg-success`, `bg-secondary`, ecc.
- `form-select` emulato
- `table-light` per intestazioni tabelle

### Template blocchi — icone
Tutti i template admin aggiornati da Bootstrap Icons (`bi bi-*`) a Font Awesome (`fas fa-*`):

| Bootstrap Icons | Font Awesome |
|---|---|
| `bi bi-pencil` | `fas fa-edit` |
| `bi bi-trash` | `fas fa-trash` |
| `bi bi-plus-lg` / `bi bi-plus-circle` | `fas fa-plus` / `fas fa-plus-circle` |
| `bi bi-arrow-left` | `fas fa-arrow-left` |
| `bi bi-person-plus` | `fas fa-user-plus` |

---

## Problemi risolti

### Titolo duplicato
`frame-private.html` aveva un `content-header` con `<[title]>` che si sommava all'`<h1>` già presente in ogni template blocco.
**Soluzione:** rimossa la sezione `content-header` dal frame — il titolo viene mostrato solo dal blocco.

### Bootstrap 4 vs Bootstrap 5
AdminLTE 3 usa Bootstrap 4, i nostri template blocchi usano classi Bootstrap 5 (`me-`, `fw-`, `badge bg-*`, `form-select`).
**Soluzione:** layer CSS di compatibilità in `style.css` che mappa le classi BS5 mancanti.
