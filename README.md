# Istruzioni per l'installazione e l'uso di Bookfind

## Requisiti di sistema
- Server web con PHP 7.4 o superiore
- MySQL/MariaDB
- Browser web moderno (Chrome, Firefox, Edge, Safari)

## Installazione

1. **Configurazione del database**
   - Crea un nuovo database nel tuo MySQL/MariaDB chiamato `bookfind`
   - Importa il file `documentazione/db_schema.sql` per creare le tabelle necessarie

2. **Configurazione della connessione al database**
   - Apri il file `includes/db_connect.php` e modifica i parametri di connessione:
   ```php
   $servername = "localhost";  // Indirizzo del server MySQL
   $username = "root";         // Nome utente MySQL
   $password = "";             // Password MySQL
   $dbname = "bookfind";       // Nome del database
   ```

3. **Copia del progetto**
   - Copia tutti i file nella directory del tuo server web (es. `/var/www/html/bookfind/` o `C:/xampp/htdocs/bookfind/`)
   - Assicurati che le cartelle abbiano i permessi di scrittura (specialmente `uploads/` e `logs/`)

4. **Accesso al sistema**
   - Apri il browser e vai all'indirizzo: `http://localhost/bookfind/`
   - Per accedere all'area amministrativa, usa:
     - Username: `admin`
     - Password: `admin`

## Struttura del progetto

- **index.php**: Pagina principale per la ricerca dei libri
- **cart.php**: Gestione del carrello libri
- **add_book.php**: Aggiunta di nuovi libri
- **manage_books.php**: Gestione (modifica/eliminazione) dei libri esistenti
- **manage_buildings.php**: Gestione degli edifici
- **import.php**: Importazione di libri da file CSV
- **admin.php**: Area amministrativa

- **includes/**: Contiene i file di inclusione
  - **header.php**: Header HTML
  - **footer.php**: Footer HTML
  - **navbar.php**: Barra di navigazione
  - **db_connect.php**: Connessione al database

- **css/**: Contiene i file CSS
  - **style.css**: Foglio di stile principale

- **js/**: Contiene i file JavaScript
  - **script.js**: Script principale per le funzionalità del carrello e altre interazioni

- **documentazione/**: Contiene la documentazione del progetto
  - **db_schema.sql**: Schema del database

## Funzionalità principali

### Ricerca libri
- Cerca per numero di inventario
- Cerca per sezione, collocazione e sequenza

### Gestione del carrello
- Aggiungi libri al carrello
- Visualizza i libri nel carrello
- Ordina i libri per posizione (edificio, stanza, scaffale)
- Stampa la lista dei libri da prelevare
- Svuota il carrello

### Funzionalità amministrative
- Aggiungi nuovi libri
- Modifica/elimina libri esistenti
- Gestisci edifici (aggiungi, modifica, elimina)
- Importa libri da file CSV

## Risoluzione dei problemi comuni

### Errori di connessione al database
- Verifica i parametri di connessione in `includes/db_connect.php`
- Assicurati che il server MySQL sia in esecuzione
- Verifica che il database `bookfind` esista

### Errori 404 (pagine non trovate)
- Assicurati che tutti i file siano nella directory corretta
- Verifica che i percorsi nei file PHP siano relativi alla struttura delle directory del tuo server

### Problemi con il caricamento di file
- Verifica che la directory `uploads/` esista e abbia permessi di scrittura

### Problemi con il carrello
- Assicurati che JavaScript sia abilitato nel browser
- Verifica che localStorage sia disponibile nel browser (non in modalità di navigazione privata)
- Controlla la console JavaScript per eventuali errori

## Crediti
Sviluppato come progetto PCTO per la biblioteca.