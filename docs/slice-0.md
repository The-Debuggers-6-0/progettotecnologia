# Slice 0 — Setup iniziale del progetto

**Stato:** completato
**Obiettivo:** mettere in piedi l'ossatura dell'applicazione (configurazione, connessione DB, template engine, skin pubblica e admin) senza nessuna funzionalità di business, in modo da partire dagli slice successivi con un ambiente che gira.

---

## 1. Decisioni di dominio e template

- **Dominio applicativo:** piattaforma di prenotazione di **Esperienze & Tour** (visite guidate, attività turistiche, esperienze locali).
  Motivazione: dominio sufficientemente ricco da richiedere ≥14 tabelle (utenti, esperienze, categorie, foto, location, guide, slot temporali, prenotazioni, partecipanti, recensioni, oltre alle tabelle di giunzione).
- **Template frontend scelto:** *Tour by Untree.co* (ThemeWagon, gratuito) — appropriato per il dominio turistico. BootstrapMade era la scelta originale ma è diventato a pagamento; sostituito con Untree.co stessa categoria visiva. Integrato in `skins/tour/` dopo la Slice 1.
- **Template backend scelto:** *AdminLTE 3.2.0* (scaricato da GitHub releases, file locali in `skins/admin/`). Integrato dopo la Slice 2. Vedi `docs/admin-template.md` per i dettagli.

## 2. Struttura delle cartelle

```
progettotecnologia/
├── include/
│   ├── bootstrap.inc.php   # punto d'ingresso comune (session, config, DB, helper)
│   ├── config.inc.php      # array $config con credenziali DB e skin
│   ├── db.inc.php          # singleton PDO
│   └── page.inc.php        # helper new_page() / new_block()
├── skins/
│   ├── tour/
│   │   ├── dtml/           # template HTML pubblici
│   │   │   ├── frame-public.html
│   │   │   └── home.html
│   │   └── css/style.css
│   └── admin/
│       └── dtml/
│           └── frame-private.html
├── sql/
│   └── schema.sql          # schema DB, popolato slice-by-slice
├── template2.inc.php       # motore di templating del docente (NON modificare)
├── index.php               # homepage pubblica
└── docs/                   # documentazione di sviluppo (questa cartella)
```

## 3. File creati

### `include/config.inc.php`
Array `$config` con: credenziali DB (host/port/name/user/pass/charset), nomi delle skin (`tour` per il pubblico, `admin` per il backend), `base` URL, parametri cache (`NONE` / `FILE` / `MEMORY`).

### `include/db.inc.php`
Funzione `db()` che restituisce un singleton `PDO` configurato con:
- `ATTR_ERRMODE = ERRMODE_EXCEPTION` (errori come eccezioni, niente check manuali)
- `ATTR_DEFAULT_FETCH_MODE = FETCH_ASSOC` (risultati come array associativi)
- `ATTR_EMULATE_PREPARES = false` (prepared statement veri lato MySQL)

### `include/bootstrap.inc.php`
Punto d'ingresso che ogni pagina PHP include come prima cosa:
1. `session_start()`
2. Inizializza `$_SESSION['user'] = []` se non esiste (workaround necessario per il template engine, vedi sotto)
3. Include config, template engine, DB, helper

### `include/page.inc.php`
Helper `new_page($skin)` e `new_block($name)` che istanziano direttamente la classe `Template` del motore del docente, **senza passare per le classi `Skin` / `Skinlet`**.

### Template HTML (`skins/tour/dtml/`)
- **`frame-public.html`** — shell della pagina pubblica: header con navbar (Home, Esperienze, Chi siamo, Contatti, login/logout condizionale), `<main>` con `<[body]>`, footer con copyright e attribuzione al template.
- **`home.html`** — hero + lista esperienze in evidenza (vuota in questo slice, popolata nello Slice 2).

### `index.php`
Homepage: bootstrap → istanzia `new_page()` → setta i placeholder (`title`, `year`, `base`, `skin`, `is_logged`) → carica `new_block('home')` → render.

## 4. Problemi incontrati e come risolti

### 4.1 Warning *"Undefined array key 'user'"*
`template2.inc.php` itera su `$_SESSION['user']` per popolare i placeholder tipo `<[user.username]>`. Quando l'utente non è loggato la chiave non esiste e PHP emette un warning.
**Soluzione (senza toccare il template engine):** in `bootstrap.inc.php` inizializziamo `$_SESSION['user'] = []` se manca.

### 4.2 Fatal error *"file non trovato: frame-public.html.html"*
Le classi `Skin` / `Skinlet` del motore costruiscono path che terminano già con `.html`, poi la classe `Template` aggiunge un altro `.html` causando doppia estensione.
**Soluzione (senza toccare il template engine):** creato `include/page.inc.php` con `new_page()` e `new_block()` che istanziano direttamente `new Template("skins/.../dtml/nome")` passando il path **senza** estensione.

### 4.3 Fatal error sui tag `<[if!empty user.username]>`
La regex `checkIfNotEmpty` del motore usa `\w+` per il nome del placeholder, che non matcha il punto. Quindi `<[if!empty user.username]>` viene letto come tag malformato.
**Soluzione (senza toccare il template engine):** nel frame-public.html usiamo un nome di placeholder dot-free, `<[if!empty is_logged]>`, e settiamo `is_logged` in PHP a `'1'` o stringa vuota.

### 4.4 Fatal error *"cannot define <[base]> in foreach"*
Il motore non permette lo stesso placeholder dentro e fuori da `<[foreach]>`.
**Soluzione:** dentro al foreach delle esperienze, invece di `<[base]>/tour-details.php?id=<[experience_id]>`, useremo un singolo placeholder `<[experience_url]>` costruito interamente in PHP.

## 5. Cosa NON è stato fatto in questo slice (rimandato)

- ~~Download dei file reali del template Tour e AdminLTE~~ — **completato**: Tour by Untree.co integrato dopo Slice 1, AdminLTE 3 integrato dopo Slice 2.
- Pagine `tours.php`, `about.php`, `contact.php` — `tours.php` completata in Slice 2; `about.php` e `contact.php` rimangono da creare.
- Qualsiasi logica di business o DB query — completata a partire da Slice 1.

## 6. Come verificare che lo slice funzioni

1. Avviare XAMPP (Apache + MySQL).
2. Aprire phpMyAdmin e verificare che il database `progettotecnologia` esista (eseguire `sql/schema.sql` se serve).
3. Visitare `http://localhost/progettotecnologia/`.
4. Si deve vedere: header scuro con "Esperienze & Tour", navbar con Home/Esperienze/Chi siamo/Contatti/Accedi/Registrati, sezione hero "Scopri esperienze uniche", messaggio "Nessuna esperienza disponibile", footer con copyright.
5. Nessun warning o fatal error PHP visibile a schermo.
