<?php
// Configurazione del database
$servername = "localhost"; // Indirizzo del server mySQL (solitamente localhost con XAMPP)
$username = "root";     // Nome utente di default di mySQL con XAMPP
$password = "";         // Password di default di mySQL con XAMPP (solitamente vuota)
$dbname = "bookfind";   // Nome del database che hai creato nel Passo 1

// Creazione della connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controllo della connessione
if ($conn->connect_error) {
    die("Connessione al database fallita: " . $conn->connect_error);
}

// Imposta il set di caratteri a utf8 (opzionale ma consigliato)
$conn->set_charset("utf8");

// Definisci la funzione per ottenere l'URL base se non è ancora definita
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $base_dir = dirname($_SERVER['SCRIPT_NAME']);
        $base_url = '';
        if($base_dir != '/' && $base_dir != '\\') {
            $base_url = $base_dir;
            if(substr($base_url, -1) != '/') $base_url .= '/';
        }
        return $base_url;
    }
}

// Funzione per registrare errori (debug)
function logError($message) {
    $log_file = __DIR__ . '/../logs/error.log';
    $directory = dirname($log_file);
    
    // Crea la directory dei log se non esiste
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    
    // Registra l'errore nel file di log
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, 3, $log_file);
}