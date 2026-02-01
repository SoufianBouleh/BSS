gestione-forniture/
├── .env                          # Variabili d'ambiente (NON committare)
├── .gitignore                    # File da ignorare
├── README.md                     # Documentazione progetto
├── composer.json                 # Dipendenze (opzionale)
│
├── app/                          # CODICE APPLICAZIONE (non accessibile via web)
│   ├── config/
│   │   └── database.php          # Connessione DB
│   ├── models/                   # Classi modelli (opzionale)
│   │   ├── Utente.php
│   │   ├── Articolo.php
│   │   └── Richiesta.php
│   └── helpers/
│       └── functions.php         # Funzioni riutilizzabili
│
├── public/                       # DOCUMENT ROOT (accessibile via web)
│   ├── index.php                 # Entry point
│   │
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css         # CSS unico
│   │   ├── js/
│   │   │   └── main.js           # JavaScript
│   │   └── images/
│   │       └── logo.png
│   │
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── registrazione.php
│   │
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── articoli/
│   │   │   ├── index.php         # Lista articoli
│   │   │   ├── aggiungi.php
│   │   │   ├── modifica.php
│   │   │   └── elimina.php
│   │   ├── fornitori/
│   │   │   ├── index.php
│   │   │   ├── aggiungi.php
│   │   │   └── modifica.php
│   │   ├── ordini/
│   │   │   ├── index.php
│   │   │   └── crea.php
│   │   ├── richieste.php
│   │   ├── scorte_critiche.php
│   │   ├── approva_utenti.php
│   │   └── impostazioni.php
│   │
│   ├── dipendente/
│   │   ├── dashboard.php
│   │   ├── catalogo.php
│   │   ├── richieste/
│   │   │   ├── index.php         # Le mie richieste
│   │   │   ├── nuova.php
│   │   │   └── modifica.php
│   │   └── profilo.php
│   │
│   └── includes/
│       ├── header.php
│       ├── sidebar_admin.php
│       └── sidebar_dipendente.php