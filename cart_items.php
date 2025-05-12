<?php
// Script per recuperare le informazioni sui libri presenti nel carrello
// Questo script viene chiamato tramite AJAX

// Imposta l'header per indicare che la risposta è in formato JSON
header('Content-Type: application/json');

// Includi il file di connessione al database
require_once 'includes/db_connect.php';

// Recupera i dati JSON inviati tramite POST
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Verifica che siano stati inviati degli ID di libri
if (!isset($data['bookIds']) || !is_array($data['bookIds']) || empty($data['bookIds'])) {
    echo json_encode(['success' => false, 'message' => 'Nessun libro specificato']);
    exit;
}

// Prepara gli ID dei libri per la query
$book_ids = array();
foreach ($data['bookIds'] as $id) {
    // Pulisci gli ID per evitare SQL injection
    $book_ids[] = $conn->real_escape_string($id);
}

// Crea una stringa con gli ID separati da virgole
$book_ids_str = "'" . implode("','", $book_ids) . "'";

// Query per recuperare i dettagli dei libri
$sql = "SELECT l.*, e.nome AS nome_edificio
        FROM libri l
        LEFT JOIN edifici e ON l.id_edificio = e.id
        WHERE l.inventario IN ($book_ids_str)
        ORDER BY l.inventario ASC";

$result = $conn->query($sql);

// Prepara l'array per la risposta
$books = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    echo json_encode(['success' => true, 'books' => $books]);
} else {
    echo json_encode(['success' => false, 'message' => 'Nessun libro trovato']);
}

// Chiudi la connessione al database
$conn->close();
?>