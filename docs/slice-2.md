# Slice 2 — Catalogo Esperienze

**Stato:** completato

---

## 1. Schema DB aggiunto

3 nuove tabelle in `sql/schema.sql`, eseguite in phpMyAdmin:

| Tabella | Scopo |
|---|---|
| `categories` | Categorie per raggruppare le esperienze |
| `experiences` | Catalogo esperienze con slug, prezzo, durata, ecc. |
| `experience_photos` | Foto collegate a ogni esperienza (flag `is_cover`) |

### Dettaglio campi `experiences`
- `slug VARCHAR(200) UNIQUE` — URL-friendly, auto-generato dal titolo se non specificato
- `price DECIMAL(8,2)` — prezzo per persona
- `duration_minutes INT` — durata in minuti (visualizzata come Xh Ymin)
- `max_participants INT` — capienza massima
- `category_id INT` — FK verso `categories`, `ON DELETE SET NULL`
- `is_active TINYINT(1)` — visibilità pubblica (0 = bozza)

### Dettaglio campi `experience_photos`
- `is_cover TINYINT(1)` — foto di copertina (usata in liste e home)
- `sort_order INT` — ordine nel carousel della pagina dettaglio
- `ON DELETE CASCADE` su `experience_id`

---

## 2. File PHP creati

### Admin CRUD

| File | Scopo |
|---|---|
| `admin/categories.php` | Lista categorie con contatore esperienze |
| `admin/categories-form.php` | Crea/modifica categoria |
| `admin/categories-delete.php` | Elimina categoria |
| `admin/experiences.php` | Lista esperienze (thumbnail + stato) |
| `admin/experiences-form.php` | Crea/modifica esperienza + upload foto copertina |
| `admin/experiences-delete.php` | Elimina esperienza + file foto dal filesystem |

### Frontend

| File | Scopo |
|---|---|
| `tours.php` | Lista pubblica con filtro per categoria |
| `tour-detail.php` | Dettaglio esperienza con carousel foto e info |

### Aggiornato
- `index.php` — home ora popola il foreach con le 6 esperienze più recenti attive

---

## 3. Upload foto

- Directory: `uploads/experiences/`
- Tipi accettati: jpg, jpeg, png, webp
- Max: 5 MB
- Filename generato: `{experience_id}_{uniqid()}.{ext}`
- Alla cancellazione dell'esperienza: i file vengono rimossi dal filesystem prima del DELETE

---

## 4. Template HTML creati

| File | Placeholder principali |
|---|---|
| `skins/admin/dtml/categories-list.html` | foreach: cat_name, cat_description, cat_exp_count, cat_edit_url, cat_delete_url |
| `skins/admin/dtml/categories-form.html` | form_title, cat_name, cat_description, error |
| `skins/admin/dtml/experiences-list.html` | foreach: exp_title, exp_location, exp_price, exp_category, exp_cover_url, exp_status, exp_edit_url, exp_delete_url |
| `skins/admin/dtml/experiences-form.html` | exp_title, exp_slug, exp_price, exp_location, exp_duration, exp_max_part, exp_active_check, category_options, cover_preview, error |
| `skins/tour/dtml/tours.html` | category_filters, foreach: experience_url/photo/title/location/price |
| `skins/tour/dtml/tour-detail.html` | exp_title, exp_location, exp_price, exp_duration, exp_max_part, exp_category, exp_description, photos_html |

---

## 5. Note tecniche

### Slug auto-generato
La funzione `slugify()` in `experiences-form.php` normalizza caratteri accentati italiani e sostituisce spazi con `-`. Viene usato solo se il campo è lasciato vuoto.

### Category options come HTML inline
Il dropdown categorie nel form viene costruito in PHP come stringa HTML e iniettato nel placeholder `<[category_options]>` — evita di annidare foreach complessi nel template.

### Carousel foto senza foreach (tour-detail)
Il template engine del professore non permette lo stesso placeholder dentro e fuori da `<[foreach]>`. In `tour-detail.html` il placeholder `exp_title` compare sia nell'`<h1>` dell'hero (fuori) sia nell'attributo `alt` delle foto (dentro un ipotetico foreach) — conflitto fatale.
**Soluzione adottata:** eliminato il `<[foreach]>` da `tour-detail.html` e sostituito con un singolo `<[photos_html]>`. Il PHP in `tour-detail.php` costruisce l'intero blocco HTML del carousel (o l'immagine di fallback) come stringa e lo inietta nel placeholder.

### Foto di fallback
Se un'esperienza non ha foto caricate, viene mostrata `hero-slider-1.jpg` del template come immagine segnaposto.

---

## 6. Come verificare lo slice

1. `/admin/categories.php` → crea categoria "Tour guidati"
2. `/admin/experiences.php` → crea esperienza con foto, luogo, prezzo
3. `/index.php` → home mostra l'esperienza nella griglia "Esperienze in evidenza"
4. `/tours.php` → lista con filtro categorie, card con foto/titolo/prezzo
5. `/tours.php?cat=1` → filtro per categoria
6. Clic sulla card → `/tour-detail.php?id=1` → dettaglio con foto, prezzo, durata, descrizione
7. Admin → modifica esperienza → cambio foto di copertina → lista aggiornata
8. Admin → elimina esperienza → file foto rimosso da `uploads/experiences/`
