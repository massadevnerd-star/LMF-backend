# Documentazione Finale Deployment – lemiefiabe.online

Questo documento riassume tutte le operazioni eseguite per il setup e la messa in produzione del progetto.

## 🏗️ 1. Infrastruttura e Stack Tecnologico
È stato installato e configurato lo stack **LAMP** su sistema Ubuntu:
- **Web Server**: Apache2 con moduli `rewrite`, `proxy`, `proxy_http`, `ssl` e `headers`.
- **Database**: MySQL 8 con database `ibra-lmf` e utente dedicato.
- **Linguaggi/Runtime**: PHP 8.2 (con estensioni per Laravel) e Node.js 20 LTS.
- **Process Management**: PM2 per la gestione del frontend Next.js.
- **Sicurezza**: Certificati SSL Let's Encrypt per `lemiefiabe.online` e `api.lemiefiabe.online`.

---

## 🚀 2. Deployment Applicazioni

### Backend (Laravel) – [api.lemiefiabe.online](https://api.lemiefiabe.online)
- Configurazione file `.env` per produzione.
- Migrazione del database e seeding dei ruoli/permessi.
- Impostazione permessi cartelle (`storage`, `bootstrap/cache`) per l'utente `www-data`.
- Configurazione di **Laravel Sanctum** per gestire l'autenticazione tra domini.

### Frontend (Next.js 15) – [lemiefiabe.online](https://lemiefiabe.online)
- Configurazione variabili d'ambiente (`.env.local`).
- Esecuzione build di produzione ottimizzata.
- Avvio e monitoraggio tramite PM2 (processo `lmf-frontend`).
- Correzione di un errore di tipizzazione TypeScript in `MobileSlideEditor.tsx`.

---

## 🔧 3. Risoluzione Errori e Ottimizzazioni

### Errore 401 Unauthorized (Login)
- **Causa**: Database non popolato e configurazione domini stateful mancante.
- **Soluzione**: Eseguito seeding iniziale e configurato `SANCTUM_STATEFUL_DOMAINS` e `SESSION_DOMAIN` nel backend.

### Sidebar Menu e Permessi
- **Configurazione Menu**: Popolate le tabelle `menus` e `menu_role` per gestire la visibilità per ruolo.
- **Restrizioni Guest**: Configurato il sistema affinché gli utenti non loggati vedano solo "Il mio Castello" e "Cerca Storie".
- **Accesso Admin**: Aggiornata la logica del componente `Sidebar` per permettere all'amministratore di vedere tutte le voci (Crea Storie AI, Laboratorio, Negozio Magico).
- **Pulizia**: Rimossi link ridondanti ("Bacheca", "Le Mie Storie", "Area Admin") e sistemate le icone (sostituiti i testi grezzi con emoji).

---

## 🔑 Credenziali di Accesso
- **Admin Email**: `admin@lmf.com`
- **Admin Password**: `password`

## 📁 Percorsi Utili sul Server
- **VHost Apache**: `/etc/apache2/sites-available/`
- **Cartella Progetti**: `/var/www/`
- **Log Laravel**: `/var/www/api.lemiefiabe.online/storage/logs/laravel.log`
- **PM2 Status**: `pm2 status`
