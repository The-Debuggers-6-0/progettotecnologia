# Slice 9 — Box Homepage gestibili da backoffice e uniformità delle immagini

**Stato:** completato
**Obiettivo:** due interventi sull'interfaccia pubblica:
1. **Uniformare le dimensioni delle immagini** nel carosello "Esperienze in evidenza" (homepage) e nella griglia dell'elenco esperienze (`tours.php`) — su richiesta del docente, che le voleva tutte della stessa dimensione.
2. Rendere i 4 riquadri (icona + titolo + testo) della sezione *"Perché sceglierci"* in homepage **modificabili dal pannello admin** ("Box Homepage"), senza toccare l'immagine laterale che resta fissa. Richiesta del docente: poter cambiare facilmente sia i testi sia le icone dei box.

---

## 1. Uniformità delle immagini (carosello + elenco esperienze)

Le immagini avevano dimensioni diverse perché erano renderizzate alla loro **dimensione naturale**: la sola classe Bootstrap `img-fluid` imposta larghezza responsive ma `height: auto`, quindi ogni foto manteneva il proprio rapporto e altezza. Soluzione: dare al **contenitore** un'altezza fissa e all'**immagine** `object-fit: cover`, così riempie il riquadro venendo ritagliata e centrata, senza deformarsi.

### Carosello "Esperienze in evidenza" (`home.html`)
In `skins/tour/css/style.css`:
```css
.media-thumb { /* ... */ height: 280px; }          /* altezza fissa della card */
.media-thumb img {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover;
}
```

### Griglia elenco esperienze (`tours.php`)
La card `.destination-item` non aveva **alcun** CSS dedicato (solo `img-fluid`): aggiunta una regola apposta in `style.css`:
```css
.destination-item .img {
  display: block;
  height: 220px;
  overflow: hidden;
  border-radius: 10px;
}
.destination-item .img img {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover;
  /* leggero zoom all'hover, coerente col template */
}
.destination-item .img:hover img { transform: scale(1.05); }
```

> **Nota sul `!important`:** serve a battere la regola `height: auto` della classe Bootstrap `.img-fluid`, che altrimenti vince per specificità/ordine e impedisce all'immagine di riempire l'altezza fissa del contenitore.

### Cache-busting
Il CSS è linkato in `frame-public.html` con un parametro di versione (`style.css?v=N`). Ad ogni modifica del foglio di stile il numero è stato incrementato (fino a **`?v=6`**) per costringere il browser a riscaricare la versione aggiornata senza hard-refresh manuale — lo stesso meccanismo introdotto nello Slice 8.

---

## 2. Nuova tabella `home_features`

Prima di questo slice i 4 box erano **hard-coded** in `skins/tour/dtml/home.html` (markup statico). Per renderli editabili li abbiamo spostati su database.

```sql
CREATE TABLE home_features (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    icon        VARCHAR(100) NOT NULL DEFAULT 'flaticon-house',
    title       VARCHAR(150) NOT NULL,
    description VARCHAR(500),
    sort_order  INT          NOT NULL DEFAULT 0
);
```

- **`icon`** — la classe CSS del font *flaticon* (es. `flaticon-house`). Il frontend carica già questo font (`skins/tour/fonts/flaticon`), quindi memorizzare la classe è sufficiente a far comparire l'icona.
- **`sort_order`** — determina la disposizione nella griglia 2×2 della homepage: `1` = alto-sx, `2` = alto-dx, `3` = basso-sx, `4` = basso-dx (l'ordine di lettura del `foreach`).

### File toccati
- **`sql/schema.sql`** — aggiunta la `CREATE TABLE home_features` (sezione "Slice 9").
- **`sql/seed.sql`** — aggiunti i 4 box iniziali (gli stessi che erano cablati nell'HTML).
- **`sql/migrate-home-features.sql`** — *nuovo*: script **idempotente** per applicare la tabella a un DB già popolato senza ri-eseguire tutto il seed. Crea la tabella con `IF NOT EXISTS` e inserisce i 4 box solo se la tabella è vuota (`WHERE NOT EXISTS (SELECT 1 FROM home_features)`).

### Admin nel seed
Colta l'occasione per rendere `sql/seed.sql` **autosufficiente**: prima l'utente `admin` (+ gruppo `admin` + relazione) era dato per "già esistente" e non veniva creato da nessuno script. Ora il seed lo inserisce in testa con **`INSERT IGNORE`**: su uno schema vuoto l'admin viene creato, mentre dopo `clean.sql` (che preserva l'admin) le righe in conflitto su PK/UNIQUE vengono semplicemente saltate, senza duplicati né errori. Così `schema.sql` + `seed.sql` producono da soli un ambiente completo e loggabile.

---

## 3. Frontend dinamico (`index.php` + `home.html`)

Il markup statico dei due `col-lg-4` con i 4 box è stato sostituito da un **unico `foreach`** dentro un `col-lg-8`, mantenendo identica l'immagine (`col-lg-4`) e l'aspetto a griglia 2×2.

```html
<div class="col-lg-8 order-lg-2">
    <div class="row align-items-stretch">
        <[foreach]>
        <div class="col-6 col-sm-6 feature-1-wrap d-flex">
            <div class="feature-1 d-md-flex w-100">
                <div class="align-self-center">
                    <span class="<[feature_icon]> display-4 text-primary"></span>
                    <h3><[feature_title]></h3>
                    <p class="mb-0"><[feature_text]></p>
                </div>
            </div>
        </div>
        <[/foreach]>
    </div>
</div>
```

In **`index.php`** una query carica i box ordinati e un ciclo popola i placeholder:

```php
$features = db()->query(
    'SELECT icon, title, description FROM home_features ORDER BY sort_order, id'
)->fetchAll();

foreach ($features as $f) {
    $home->setContent('feature_icon',  htmlspecialchars($f['icon']));
    $home->setContent('feature_title', htmlspecialchars($f['title']));
    $home->setContent('feature_text',  htmlspecialchars($f['description'] ?? ''));
}
```

### Nota sul template engine (due `foreach` nella stessa pagina)
La home contiene ora **due cicli `foreach` distinti**: i box "feature" e il carosello "esperienze in evidenza". Il motore (`template2.inc.php`) lo consente a una condizione, verificata da `checkPlaceholders()`: **i nomi dei placeholder non devono ripetersi tra `foreach` diversi**. Per questo i box usano `feature_*` e il carosello `experience_*`. Durante il binding ogni `foreach` di primo livello scorre l'intera lista dei contenuti e **salta** i placeholder che non gli appartengono, così i due cicli convivono senza interferenze.

---

## 4. CRUD nel backoffice (skin `admin`)

Replicato il pattern già usato per le **Categorie** (lista / form / delete).

| File | Ruolo |
|---|---|
| `admin/features.php` | Lista dei box (icona in anteprima, titolo, testo, ordine) |
| `admin/features-form.php` | Creazione/modifica di un box |
| `admin/features-delete.php` | Eliminazione (con `confirm()` lato lista) |
| `skins/admin/dtml/features-list.html` | Template tabella |
| `skins/admin/dtml/features-form.html` | Template form |

Aggiunta inoltre la voce **"Box homepage"** (icona `fa-th-large`) nel menu laterale `frame-private.html`, sezione *CONTENUTO*. La pagina lista ha come titolo **"Box Homepage"** e sottotitolo *"I riquadri con icona mostrati nella homepage."*.

### Selezione dell'icona con anteprima
Il docente voleva poter cambiare **facilmente** le icone: il form propone un **menu a tendina** con un set curato di icone flaticon e un'etichetta in italiano (Casa, Ristorante/Gastronomia, Busta/Email, Telefono/Supporto, Aereo/Viaggio, Nuoto/Mare, Attività/Famiglia).

- Le `<option>` sono costruite in PHP con preselezione della scelta corrente.
- Il valore inviato è **validato** contro la whitelist `$icons` (non si possono salvare classi arbitrarie).
- Accanto al menu c'è un **riquadro di anteprima dal vivo**: il font flaticon viene iniettato nell'`<head>` della pagina admin (placeholder `<[head]>`) e un piccolo script aggiorna l'icona mostrata al cambio di selezione.

---

## 5. Come verificare lo slice

1. **Immagini uniformi:** in homepage il carosello "Esperienze in evidenza" mostra tutte le card della **stessa altezza**; in `tours.php` tutte le foto della griglia hanno la **stessa dimensione**. Le immagini sono ritagliate e centrate (`object-fit: cover`), mai deformate. Se non si vede l'aggiornamento, controllare che il CSS sia caricato con `?v=6` (cache-busting).
2. **Migrazione DB:** eseguire `sql/migrate-home-features.sql` (oppure `clean.sql` + `seed.sql`). Verificare che `home_features` contenga 4 righe.
3. **Homepage:** la sezione "Perché sceglierci" mostra i 4 box come prima (immagine a sinistra invariata, griglia 2×2 a destra).
4. **Backoffice:** login admin → menu *CONTENUTO → Box homepage*. La lista mostra i 4 box con l'icona in anteprima.
5. **Modifica testo:** aprire un box, cambiare titolo/testo, salvare → ricaricando la homepage il box è aggiornato.
6. **Modifica icona:** nel form scegliere un'altra icona dal menu → l'anteprima cambia subito; dopo il salvataggio l'icona è aggiornata anche in homepage.
7. **Ordine:** cambiando il campo "Ordine" si riposizionano i box nella griglia 2×2.
8. **Nuovo / elimina:** creare un box aggiuntivo ed eliminarne uno; la homepage riflette le modifiche.
