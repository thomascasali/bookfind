<?php
// Script per gestire l'aggiunta, l'eliminazione e la modifica dello stato di un libro
// Questo script viene chiamato tramite AJAX

// Imposta l'header per indicare che la risposta è in formato JSON
header('Content-Type: application/json');

// Includi il file di connessione al database
require_once 'includes/db_connect.php';

// Recupera i dati JSON inviati tramite POST
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Verifica che sia stato specificato un'azione
if (!isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Nessuna azione specificata']);
    exit;
}

// Gestisci le diverse azioni
switch ($data['action']) {
    case 'update_state':
        // Aggiorna lo stato di un libro
        if (!isset($data['inventario']) || !isset($data['stato'])) {
            echo json_encode(['success' => false, 'message' => 'Parametri mancanti']);
            exit;
        }
        
        $inventario = $conn->real_escape_string($data['inventario']);
        $stato = $conn->real_escape_string($data['stato']);
        
        // Verifica che lo stato sia valido
        if (!in_array($stato, ['disponibile', 'prestito', 'manutenzione'])) {
            echo json_encode(['success' => false, 'message' => 'Stato non valido']);
            exit;
        }
        
        // Aggiorna lo stato del libro
        $sql = "UPDATE libri SET stato = '$stato' WHERE inventario = '$inventario'";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true, 'message' => 'Stato aggiornato con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento dello stato: ' . $conn->error]);
        }
        break;
        
    case 'add_book':
        // Aggiungi un nuovo libro
        if (!isset($data['inventario']) || !isset($data['sezione']) || !isset($data['collocazione']) || 
            !isset($data['sequenza']) || !isset($data['stanza']) || !isset($data['scaffale'])) {
            echo json_encode(['success' => false, 'message' => 'Parametri mancanti']);
            exit;
        }
        
        $inventario = $conn->real_escape_string($data['inventario']);
        $id_edificio = isset($data['id_edificio']) ? $conn->real_escape_string($data['id_edificio']) : "NULL";
        $sezione = $conn->real_escape_string($data['sezione']);
        $collocazione = $conn->real_escape_string($data['collocazione']);
        $sequenza = $conn->real_escape_string($data['sequenza']);
        $specificazione = isset($data['specificazione']) ? $conn->real_escape_string($data['specificazione']) : "";
        $stanza = $conn->real_escape_string($data['stanza']);
        $scaffale = $conn->real_escape_string($data['scaffale']);
        $stato = isset($data['stato']) ? $conn->real_escape_string($data['stato']) : "disponibile";
        
        // Controlla se l'inventario esiste già
        $check_query = "SELECT COUNT(*) as count FROM libri WHERE inventario = '$inventario'";
        $check_result = $conn->query($check_query);
        $row = $check_result->fetch_assoc();
        
        if ($row['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Un libro con questo numero di inventario esiste già']);
            exit;
        }
        
        // Inserisci il nuovo libro
        $sql = "INSERT INTO libri (inventario, id_edificio, sezione, collocazione, sequenza, specificazione, stanza, scaffale, stato) 
                VALUES ('$inventario', " . ($id_edificio != "NULL" ? "'$id_edificio'" : "NULL") . ", '$sezione', '$collocazione', '$sequenza', '$specificazione', '$stanza', '$scaffale', '$stato')";
        
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true, 'message' => 'Libro aggiunto con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiunta del libro: ' . $conn->error]);
        }
        break;
        
    case 'delete_book':
        // Elimina un libro
        if (!isset($data['inventario'])) {
            echo json_encode(['success' => false, 'message' => 'Parametri mancanti']);
            exit;
        }
        
        $inventario = $conn->real_escape_string($data['inventario']);
        
        // Elimina il libro
        $sql = "DELETE FROM libri WHERE inventario = '$inventario'";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true, 'message' => 'Libro eliminato con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'eliminazione del libro: ' . $conn->error]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
        break;
}

// Chiudi la connessione al database
$conn->close();
?>