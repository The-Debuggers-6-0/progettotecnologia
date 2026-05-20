# Diagramma ER — Esperienze & Tour

Schema completo del database `progettotecnologia` (15 tabelle).

![Diagramma ER](<Diagramma_ER(sfondo_bianco).png>)

> Immagine renderizzata: `Diagramma_ER(sfondo_bianco).png` (versione a sfondo trasparente: `Diagramma ER.png`).
> Il sorgente Mermaid qui sotto è la fonte di verità: per rigenerare l'immagine copialo su
> [mermaid.live](https://mermaid.live) ed esporta in PNG/SVG, oppure aprilo in VS Code con
> l'estensione *Markdown Preview Mermaid Support*. Su GitHub viene renderizzato automaticamente.

```mermaid
erDiagram
    users {
        int id PK
        varchar username UK
        varchar email UK
        varchar password
        varchar name
        varchar surname
        timestamp created_at
    }
    groups {
        int id PK
        varchar name
        varchar description
    }
    services {
        varchar username PK
    }
    users_has_groups {
        int users_id PK,FK
        int groups_id PK,FK
    }
    services_has_groups {
        varchar services_username PK,FK
        int groups_id PK,FK
    }
    categories {
        int id PK
        varchar name
        text description
    }
    experiences {
        int id PK
        varchar title
        varchar slug UK
        text description
        varchar short_description
        decimal price
        int duration_minutes
        int max_participants
        int category_id FK
        varchar location
        int location_id FK
        tinyint is_active
        timestamp created_at
    }
    experience_photos {
        int id PK
        int experience_id FK
        varchar filename
        tinyint is_cover
        int sort_order
    }
    locations {
        int id PK
        varchar name
        varchar city
        varchar address
        text description
        decimal latitude
        decimal longitude
    }
    guides {
        int id PK
        varchar name
        varchar surname
        text bio
        varchar photo_filename
        varchar languages
        varchar email
        varchar phone
        tinyint is_active
        timestamp created_at
    }
    experience_guides {
        int experience_id PK,FK
        int guide_id PK,FK
    }
    time_slots {
        int id PK
        int experience_id FK
        datetime start_datetime
        int capacity
        int booked_count
        tinyint is_active
        varchar notes
        timestamp created_at
    }
    bookings {
        int id PK
        int user_id FK
        int time_slot_id FK
        int participants_count
        decimal total_price
        enum status
        text notes
        timestamp created_at
    }
    booking_participants {
        int id PK
        int booking_id FK
        varchar name
        varchar surname
    }
    reviews {
        int id PK
        int experience_id FK
        int user_id FK
        tinyint rating
        text comment
        timestamp created_at
    }

    users                ||--o{ users_has_groups      : "appartiene a"
    groups               ||--o{ users_has_groups      : "raggruppa"
    services             ||--o{ services_has_groups   : "esposto a"
    groups               ||--o{ services_has_groups   : "autorizza"

    categories           ||--o{ experiences           : "classifica"
    locations            ||--o{ experiences           : "ospita"
    experiences          ||--o{ experience_photos      : "ha foto"
    experiences          ||--o{ experience_guides      : "assegnata a"
    guides               ||--o{ experience_guides      : "conduce"
    experiences          ||--o{ time_slots             : "ha slot"
    experiences          ||--o{ reviews                : "riceve"

    time_slots           ||--o{ bookings               : "prenotato in"
    users                ||--o{ bookings               : "effettua"
    bookings             ||--o{ booking_participants   : "include"
    users                ||--o{ reviews                : "scrive"
```

## Legenda relazioni

| Relazione | Cardinalità | Note |
|---|---|---|
| `users` ↔ `groups` | N:M (via `users_has_groups`) | Sistema di gruppi/permessi (Slice 1) |
| `services` ↔ `groups` | N:M (via `services_has_groups`) | Autorizzazione servizi per gruppo |
| `categories` → `experiences` | 1:N | `ON DELETE SET NULL` |
| `locations` → `experiences` | 1:N | `ON DELETE SET NULL` |
| `experiences` → `experience_photos` | 1:N | `ON DELETE CASCADE` |
| `experiences` ↔ `guides` | N:M (via `experience_guides`) | Una guida può condurre più esperienze e viceversa |
| `experiences` → `time_slots` | 1:N | `ON DELETE CASCADE` |
| `time_slots` → `bookings` | 1:N | `ON DELETE CASCADE` |
| `users` → `bookings` | 1:N | `ON DELETE CASCADE` |
| `bookings` → `booking_participants` | 1:N | `ON DELETE CASCADE` |
| `experiences` → `reviews` | 1:N | `ON DELETE CASCADE` |
| `users` → `reviews` | 1:N | `ON DELETE CASCADE`; vincolo `UNIQUE(experience_id, user_id)` → una recensione per utente/esperienza |
