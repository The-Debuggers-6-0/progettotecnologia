# Slice 7 — Area Utente

**Stato:** completato

---

## 1. Schema DB

Nessuna tabella nuova. Lo slice utilizza le tabelle già esistenti:

| Tabella | Uso |
|---|---|
| `users` | Lettura e aggiornamento dati profilo (nome, cognome, email, password) |
| `bookings` | Storico prenotazioni dell'utente loggato |
| `time_slots` | Data/ora dello slot prenotato (JOIN) |
| `experiences` | Titolo dell'esperienza prenotata (JOIN) |

---

## 2. File PHP creati

### `account.php`

Unica pagina per tutta l'area utente. Richiede login (`require_login()` → redirect a `/login.php` se non autenticato). Risolve il 404 del link "Ciao, [nome]" in navbar.

**Funzionalità:**

1. **Modifica profilo** — form POST con `action=profile`: aggiorna nome, cognome, email. Aggiorna anche `$_SESSION['user']['name']` in tempo reale così la navbar riflette subito il cambio. Gestisce email duplicata (PDOException → errore). Risponde con JSON se la richiesta è AJAX.

2. **Cambio password** — form POST con `action=password`: validazioni in ordine:
   - Password attuale errata (`password_verify`)
   - Nuova password uguale all'attuale (confronto in chiaro prima dell'hashing)
   - Nuova password < 6 caratteri
   - Conferma non coincide
   - Se tutto ok → salva hash bcrypt con `password_hash()`
   Risponde con JSON se la richiesta è AJAX.

3. **Storico prenotazioni** — query con JOIN su `time_slots` e `experiences`, ordinata per data di prenotazione decrescente. Mostra: titolo esperienza (link a `tour-detail.php`), data/ora slot, numero partecipanti, data prenotazione, badge stato, prezzo totale.

---

## 3. Template HTML creato

### `skins/tour/dtml/account.html`

Layout a due colonne:

| Colonna | Contenuto |
|---|---|
| Sinistra (`col-lg-4`) | Card "Dati personali" (`id="profile-form"`) + Card "Cambia password" (`id="pw-form"`) |
| Destra (`col-lg-8`) | Storico prenotazioni con badge stato e prezzo |

**Placeholder:**

| Placeholder | Valore |
|---|---|
| `username` | Username (non modificabile, campo `disabled`) |
| `user_name` | Nome pre-compilato nel form |
| `user_surname` | Cognome pre-compilato nel form |
| `user_email` | Email pre-compilata nel form |
| `has_bookings` | `'1'` se ci sono prenotazioni, vuoto altrimenti |
| `bookings_html` | HTML storico prenotazioni (costruito in PHP) |
| `tours_url` | Link a `/tours.php` (CTA se nessuna prenotazione) |

> I placeholder `profile_success`, `profile_error`, `password_success`, `password_error` **non sono più renderizzati nel template** — il feedback avviene tramite toast AJAX (vedi nota tecnica).

---

## 4. Note tecniche

### Due form, una pagina
La pagina gestisce due form distinti tramite `<input type="hidden" name="action" value="profile|password">`. Il PHP controlla `$_POST['action']` e processa solo il form pertinente.

### Toast AJAX per entrambi i form
Entrambi i form (`profile-form` e `pw-form`) vengono intercettati via JavaScript con `fetch()`. Il PHP rileva la richiesta AJAX tramite l'header `X-Requested-With: XMLHttpRequest` e risponde con JSON:

```json
{ "success": true|false, "message": "..." }
```

Il JS mostra un popup centrato in cima alla pagina (verde = successo, rosso = errore) che scompare dopo ~3 secondi con fade-out. Nessuna ricarica di pagina.

```js
function ajaxForm(id, resetOnSuccess) {
    var form = document.getElementById(id);
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        fetch(window.location.href, {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
            body: new FormData(form)
        })
        .then(r => r.json())
        .then(data => {
            showToast(data.success, data.message);
            if (data.success && resetOnSuccess) form.reset();
        });
    });
}
// profilo: non resetta (i dati rimangono nel form)
// password: resetta i campi dopo il successo
ajaxForm("profile-form", false);
ajaxForm("pw-form", true);
```

Il JS è iniettato nel placeholder `<[javascript]>` tramite `$skin->setContent('javascript', $accountJs)`.

### Validazione nuova password = attuale
Controllo aggiunto **prima** dell'hashing: se `$new === $current` viene restituito errore senza toccare il DB. L'ordine completo delle validazioni password è:

1. Password attuale errata
2. Nuova = attuale
3. Nuova < 6 caratteri
4. Conferma ≠ nuova
5. Aggiornamento DB

### Username non modificabile
Lo `username` è mostrato come `<input disabled>` — non viene mai inviato dal browser né processato dal PHP.

### Storico prenotazioni HTML inline
Come per slots, guide e recensioni, `$bookingsHtml` è costruito come stringa PHP e iniettato nel placeholder `bookings_html`. Evita il conflitto del template engine con `<[foreach]>`.

### Badge stato prenotazione

| Status DB | Label | Classe badge |
|---|---|---|
| `confirmed` | Confermata | `bg-success` |
| `pending` | In attesa | `bg-warning` |
| `cancelled` | Cancellata | `bg-danger` |

---

## 5. Come verificare lo slice

1. Senza login → `http://localhost/progettotecnologia/account.php` → redirect a `/login.php`
2. Login → click "Ciao, [nome]" in navbar → `/account.php` si apre senza 404
3. Modifica nome → "Salva modifiche" → popup verde centrato in cima, navbar aggiorna il nome
4. Email già in uso → popup rosso "Email già in uso da un altro account."
5. Cambio password con password attuale errata → popup rosso
6. Cambio password con nuova = attuale → popup rosso "La nuova password deve essere diversa da quella attuale."
7. Cambio password con nuova < 6 caratteri → popup rosso
8. Cambio password con conferma diversa → popup rosso
9. Cambio password corretta → popup verde, form resettato, login con nuova password funziona
10. Storico prenotazioni: mostra tutte le prenotazioni con badge e prezzo
11. Click sul titolo esperienza → `/tour-detail.php?id=X`
12. Utente senza prenotazioni → "Non hai ancora nessuna prenotazione." + CTA "Esplora le esperienze"
