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

// Ora la variabile $conn contiene l'oggetto connessione al database.
// Questo file non produce output, serve solo per stabilire la connessione.
?>