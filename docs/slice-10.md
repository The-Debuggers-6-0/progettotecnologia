# Slice 10 — Gruppo "Visitatori" automatico per gli utenti registrati

**Stato:** completato
**Obiettivo:** ogni utente "normale" (non admin) deve appartenere a un gruppo
**Visitatori**. Prima di questo slice gli utenti creati — sia tramite la
registrazione pubblica sia dal backoffice — restavano **senza alcun gruppo**.

---

## 1. Il problema

- `register.php` (registrazione pubblica) faceva solo `INSERT INTO users`, senza
  riga in `users_has_groups`: l'utente nasceva "orfano".
- `admin/users-form.php` (creazione da admin) idem.
- Nel DB esisteva **solo** il gruppo `admin` (id=1): nessun gruppo per gli utenti
  comuni.

Conseguenza: impossibile distinguere a livello di gruppo gli utenti registrati,
e la relazione N:M `users_has_groups` restava vuota per tutti tranne l'admin.

---

## 2. Nuovo gruppo "Visitatori" (dato, non schema)

Il gruppo è un **dato**, quindi vive in `seed.sql` (non in `schema.sql`, che
contiene solo le `CREATE TABLE`). Inserito in modo idempotente come l'admin:

```sql
INSERT IGNORE INTO groups (id, name, description) VALUES
(2, 'Visitatori', 'Utenti registrati del sito');
```

Inoltre i due utenti di test (`mario.rossi`, `giulia.verdi`) vengono ora
assegnati a Visitatori:

```sql
INSERT INTO users_has_groups (users_id, groups_id) VALUES
(@mario_id,  2),
(@giulia_id, 2);
```

L'admin resta **solo** nel gruppo `admin`.

---

## 3. Assegnazione del gruppo

### 3a. Registrazione pubblica (`register.php`) → sempre "Visitatori"

Chi si registra dal sito è per definizione un visitatore. Dopo l'`INSERT` si
recupera l'id (`lastInsertId()`), si cerca l'id del gruppo Visitatori per
**nome** (così non si dipende da un id cablato) e si crea la riga in
`users_has_groups`:

```php
$newUserId = (int) db()->lastInsertId();
$grp = db()->prepare('SELECT id FROM groups WHERE name = ?');
$grp->execute(['Visitatori']);
$groupId = $grp->fetchColumn();
if ($groupId) {
    db()->prepare('INSERT INTO users_has_groups (users_id, groups_id) VALUES (?, ?)')
        ->execute([$newUserId, $groupId]);
}
```

> Il guard `if ($groupId)` evita di rompere la registrazione se il gruppo
> Visitatori non esistesse ancora: l'utente viene comunque creato.

### 3b. Creazione/modifica da backoffice (`admin/users-form.php`) → gruppo scelto

Il form admin ha un menu a tendina **"Gruppo"** popolato **dinamicamente** dalla
tabella `groups` (oggi `admin` / `Visitatori`, ma si estende da solo se se ne
aggiungono altri). Preselezione: per un nuovo utente "Visitatori"; in modifica
il gruppo attuale dell'utente.

Al salvataggio il gruppo scelto **sostituisce** quello eventuale (modello "un
solo gruppo per utente"), sia in creazione sia in modifica:

```php
$selectedGroupId = (int)($_POST['group_id'] ?? 0);
// ... il valore è validato contro gli id reali dei gruppi ($validIds) con in_array ...
$targetUserId = $isEdit ? $id : (int) db()->lastInsertId();
db()->prepare('DELETE FROM users_has_groups WHERE users_id = ?')->execute([$targetUserId]);
db()->prepare('INSERT INTO users_has_groups (users_id, groups_id) VALUES (?, ?)')
    ->execute([$targetUserId, $selectedGroupId]);
```

Le `<option>` sono costruite in PHP preselezionando il gruppo corrente; la
validazione con `in_array` impedisce di salvare gruppi inesistenti.

---

## 4. Applicare la modifica a un database già esistente

`seed.sql` viene eseguito solo su un DB vuoto, quindi su un database **già
popolato** il gruppo va aggiunto a mano con un singolo INSERT (es. da
phpMyAdmin):

```sql
INSERT INTO groups (name, description)
VALUES ('Visitatori', 'Utenti registrati del sito');
```

Da quel momento ogni nuova registrazione (pubblica o da admin) assegna
automaticamente il gruppo.

---

## 5. Come verificare lo slice

Testato su database usa-e-getta (`schema.sql` + `seed.sql`):

1. **Setup pulito**:
   - `groups` contiene `admin` (1) e `Visitatori` (2);
   - `admin` → gruppo `admin`; `mario.rossi` e `giulia.verdi` → `Visitatori`.
2. **Registrazione pubblica**: registrare un nuovo utente da `register.php` →
   in `users_has_groups` compare la riga verso Visitatori.
3. **Creazione/modifica da admin**: nel form c'è il menu "Gruppo". Creando un
   utente gli si assegna il gruppo scelto (default Visitatori); modificandone uno
   si può cambiare il gruppo, che sostituisce il precedente.
