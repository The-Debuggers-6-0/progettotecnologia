# Slice 1 — Autenticazione + Users/Groups/Services

**Stato:** completato
**Obiettivo:** soddisfare il requisito **obbligatorio** della Sezione 4.3 dei requisiti di progetto (*Generic User Management* basato sul modello classico users–groups–services), e abilitare login, logout e registrazione per gli utenti finali.

> **Nota dal PDF dei requisiti (Sezione 4.3):**
> *"not fulfilling the users-groups-services requirement corresponds to a rejection of the project."*

---

## 1. Schema DB aggiunto

Aggiunte 5 tabelle in `sql/schema.sql` ed eseguite in phpMyAdmin:

| Tabella | Scopo |
|---|---|
| `users` | Anagrafica utenti registrati |
| `groups` | Gruppi di permessi (es. `admin`, `user`) |
| `services` | Servizi/applicazioni che possono autenticarsi (modello del docente) |
| `users_has_groups` | Relazione N:N tra utenti e gruppi |
| `services_has_groups` | Relazione N:N tra servizi e gruppi |

### Dettaglio campi `users`
- `id INT AUTO_INCREMENT PRIMARY KEY` — chiave surrogata
- `username VARCHAR(50) NOT NULL UNIQUE` — identificativo per il login
- `email VARCHAR(100) NOT NULL UNIQUE` — secondo identificativo, utile per recupero password
- `password VARCHAR(255) NOT NULL` — hash bcrypt (mai password in chiaro)
- `name`, `surname VARCHAR(100)` — dati anagrafici
- `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP` — auditing

### Dettaglio chiavi esterne
- `users_has_groups.users_id  → users.id  ON DELETE CASCADE`
- `users_has_groups.groups_id → groups.id ON DELETE CASCADE`
- `services_has_groups.services_username → services.username`
- `services_has_groups.groups_id        → groups.id ON DELETE CASCADE`

## 2. File di supporto modificati

| File | Modifica |
|---|---|
| `include/config.inc.php` | `'base' => '/progettotecnologia'` (era stringa vuota) |
| `include/bootstrap.inc.php` | Aggiunto `require_once auth.inc.php` |
| `include/page.inc.php` | Percorsi assoluti con `__DIR__ . '/..'` per supportare sottocartelle |

## 3. File PHP creati

### `login.php` (radice)
Gestisce sia `GET` (mostra form) che `POST` (processa login):
1. Se l'utente è già loggato → redirect immediato a `index.php`.
2. Validazione input (`username` e `password` non vuoti).
3. Query con **prepared statement** PDO: `SELECT id, username, name, password FROM users WHERE username = ?` — protegge da SQL injection.
4. Confronto password con `password_verify($input, $rowHash)` — bcrypt.
5. Se OK: salva in `$_SESSION['user']` solo i campi utili (`id`, `username`, `name`) — **mai la password**, nemmeno hashata.
6. Redirect a `index.php`.
7. Se errore: rimostra il form con messaggio nel placeholder `<[error]>` e username pre-compilato.

Punti di sicurezza:
- Prepared statement (no SQL injection).
- `password_verify` (no confronto in chiaro).
- `htmlspecialchars` sul valore username ri-stampato nel form (no XSS).

### `skins/tour/dtml/login.html`
Form Bootstrap 5 centrato in una card, con:
- Blocco `<[if!empty error]>` per mostrare l'alert solo in caso di errore
- Campi `username` e `password` con `required`
- Link a `register.php` per chi non ha un account

## 3. Utente di test inserito

```sql
INSERT INTO users (username, email, password, name, surname)
VALUES ('admin', 'admin@test.it',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Admin', 'Test');
```
Credenziali di prova: `admin` / `password`. L'hash è stato pre-generato con `password_hash('password', PASSWORD_DEFAULT)`.

## 4. File PHP e template creati (completo)

| File | Scopo |
|---|---|
| `login.php` | Form login + elaborazione POST |
| `logout.php` | Distrugge sessione e redirect |
| `register.php` | Form registrazione + INSERT utente |
| `include/auth.inc.php` | `require_login()` e `require_admin()` |
| `admin/index.php` | Dashboard admin con contatori |
| `admin/users.php` | Lista utenti con gruppi |
| `admin/users-form.php` | Crea/modifica utente |
| `admin/users-delete.php` | Elimina utente |
| `admin/groups.php` | Lista gruppi con contatore utenti |
| `admin/groups-form.php` | Crea/modifica gruppo |
| `admin/groups-delete.php` | Elimina gruppo |
| `admin/services.php` | Lista servizi |
| `admin/services-form.php` | Crea servizio |
| `admin/services-delete.php` | Elimina servizio |

Template HTML in `skins/tour/dtml/`: `login.html`, `register.html`.
Template HTML in `skins/admin/dtml/`: `dashboard.html`, `users-list.html`, `users-form.html`, `groups-list.html`, `groups-form.html`, `services-list.html`, `services-form.html`.

## 5. Problemi incontrati e come risolti

### 5.1 Redirect post-login alla pagina XAMPP
`$config['base']` era stringa vuota → `header('Location: /index.php')` puntava alla root di XAMPP.
**Soluzione:** impostato `'base' => '/progettotecnologia'` in `config.inc.php`.

### 5.2 Template engine stampava il path invece di renderizzare (pagine admin)
`new_page()` usava percorsi relativi (`skins/admin/dtml/...`). Le pagine in `admin/` hanno CWD diversa dalla root → file non trovato.
**Soluzione:** aggiornato `include/page.inc.php` per usare `__DIR__ . '/..'` come base assoluta.

### 5.3 "Accesso negato" per utente non admin
L'utente registrato non era nel gruppo `admin`.
**Soluzione:** `INSERT INTO users_has_groups` per aggiungere l'utente al gruppo, oppure usare l'utente di test `admin`/`password`.

## 6. Come verificare lo slice

1. `http://localhost/progettotecnologia/login.php` → form di login.
2. Credenziali sbagliate → alert "Username o password errati".
3. Login con `admin`/`password` → redirect home, navbar mostra "Ciao, Admin Test" e "Esci".
4. `http://localhost/progettotecnologia/register.php` → form registrazione, crea nuovo utente → redirect a login.
5. `http://localhost/progettotecnologia/admin/index.php` (loggati come admin) → dashboard con contatori utenti/gruppi/servizi.
6. Admin → Utenti: lista con badge gruppo, pulsanti modifica/elimina.
7. Admin → Gruppi: lista con contatore utenti, pulsanti modifica/elimina.
8. Admin → Servizi: lista (vuota), pulsante "Nuovo servizio".
9. Utente non admin → `/admin/index.php` → "Accesso negato".
10. Logout → navbar torna ad "Accedi/Registrati".
