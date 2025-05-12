<?php
// Questo file gestisce le richieste AJAX per l'importazione CSV
session_start();
header('Content-Type: application/json');

// Blocca l'accesso se non c'è un'importazione in corso
if (!isset($_SESSION['import_in_progress']) || $_SESSION['import_in_progress'] === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Nessuna importazione in corso'
    ]);
    exit;
}

// Parametri di importazione
$batch_size = 1000; // Deve corrispondere a quello in import.php

// Gestione della richiesta
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'process_batch':
        // Processa un batch di importazione
        processBatch();
        break;
        
    case 'toggle_auto_continue':
        // Abilita/disabilita la continuazione automatica
        $value = isset($_POST['value']) ? (bool)$_POST['value'] : false;
        $_SESSION['auto_continue'] = $value;
        
        echo json_encode([
            'success' => true,
            'auto_continue' => $_SESSION['auto_continue'],
            'completed' => $_SESSION['import_processed'] >= $_SESSION['import_total']
        ]);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Azione non valida'
        ]);
        break;
}

/**
 * Elabora un batch di record dal file CSV
 */
function processBatch() {
    global $batch_size;
    
    // Includi il file di connessione al database
    require_once 'includes/db_connect.php';
    
    $import_file = $_SESSION['import_file'];
    $offset = $_SESSION['import_offset'];
    $id_edificio = $_SESSION['import_id_edificio'];
    
    // Registra il tempo di inizio
    $batch_start_time = microtime(true);
    
    // Assicurati che il file esista
    if(!file_exists($import_file)) {
        echo json_encode([
            'success' => false,
            'message' => 'File di importazione non trovato',
            'completed' => true,
            'processed' => $_SESSION['import_processed'],
            'total' => $_SESSION['import_total'],
            'success' => $_SESSION['import_success'],
            'duplicates' => $_SESSION['import_duplicates'],
            'errors' => $_SESSION['import_errors'],
            'last_batch_time' => 0,
            'batch_processed' => 0
        ]);
        exit;
    }
    
    // Apri il file CSV
    $file = fopen($import_file, 'r');
    
    // Salta l'intestazione se è la prima elaborazione
    if($offset == 0) {
        fgetcsv($file);
    } else {
        // Salta le righe già elaborate
        for($i = 0; $i < $offset; $i++) {
            fgetcsv($file);
        }
    }
    
    // Inizializza contatori per questo batch
    $batch_processed = 0;
    $batch_success = 0;
    
    // Elabora un batch di righe
    while(($data = fgetcsv($file)) !== FALSE && $batch_processed < $batch_size) {
        // Incrementa contatori
        $batch_processed++;
        $_SESSION['import_processed']++;
        
        // Verifica che la riga abbia il numero atteso di colonne
        if(count($data) >= 7) { 
            $inventario = isset($data[4]) ? trim($data[4]) : '';
            
            // Verifica che l'inventario non sia vuoto
            if(empty($inventario)) {
                $_SESSION['import_errors'][] = "Errore alla riga " . ($_SESSION['import_offset'] + $batch_processed) . ": Numero di inventario vuoto. Riga saltata.";
                continue;
            }
            
            // Verifica che l'inventario non esista già nel database
            $check_query = "SELECT inventario FROM libri WHERE inventario = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("s", $inventario);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if($check_result->num_rows > 0) {
                // Incrementa il contatore di duplicati e continua con la riga successiva
                $_SESSION['import_duplicates']++;
                continue;
            }
            
            // Ora procedi con l'importazione
            $sezione = isset($data[0]) ? trim($data[0]) : '';
            $collocazione = isset($data[1]) ? trim($data[1]) : '';
            $specificazione = isset($data[2]) ? trim($data[2]) : '';
            $sequenza = isset($data[3]) ? trim($data[3]) : '';
            $stanza = isset($data[5]) ? trim($data[5]) : '';
            $scaffale = isset($data[6]) ? trim($data[6]) : '';
            
            // Query per inserire i dati nel database usando prepared statement
            $sql_insert = "INSERT INTO libri (inventario, id_edificio, sezione, collocazione, sequenza, specificazione, stanza, scaffale, stato) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'disponibile')";
            
            try {
                $stmt = $conn->prepare($sql_insert);
                $stmt->bind_param("sissssss", $inventario, $id_edificio, $sezione, $collocazione, $sequenza, $specificazione, $stanza, $scaffale);
                
                if($stmt->execute()) {
                    $batch_success++;
                    $_SESSION['import_success']++;
                } else {
                    $_SESSION['import_errors'][] = "Errore alla riga " . ($_SESSION['import_offset'] + $batch_processed) . " (Inventario: $inventario): " . $stmt->error;
                }
            } catch (Exception $e) {
                $_SESSION['import_errors'][] = "Errore alla riga " . ($_SESSION['import_offset'] + $batch_processed) . " (Inventario: $inventario): " . $e->getMessage();
            }
        } else {
            $_SESSION['import_errors'][] = "Errore alla riga " . ($_SESSION['import_offset'] + $batch_processed) . ": Numero di colonne insufficiente. Riga saltata.";
        }
    }
    
    // Chiudi il file
    fclose($file);
    
    // Aggiorna l'offset per il prossimo batch
    $_SESSION['import_offset'] += $batch_processed;
    
    // Calcola il tempo impiegato per il batch
    $batch_time = microtime(true) - $batch_start_time;
    $_SESSION['last_batch_time'] = round($batch_time, 2);
    
    // Verifica se l'importazione è completa
    $completed = $_SESSION['import_processed'] >= $_SESSION['import_total'];
    
    if($completed) {
        // Elimina il file temporaneo alla fine dell'importazione
        @unlink($import_file);
    }
    
    // Chiudi la connessione al database
    $conn->close();
    
    // Restituisci i risultati come JSON
    echo json_encode([
        'success' => true,
        'completed' => $completed,
        'processed' => $_SESSION['import_processed'],
        'total' => $_SESSION['import_total'],
        'success' => $_SESSION['import_success'],
        'duplicates' => $_SESSION['import_duplicates'],
        'errors' => $_SESSION['import_errors'],
        'progress' => ($_SESSION['import_processed'] / $_SESSION['import_total']) * 100,
        'last_batch_time' => $_SESSION['last_batch_time'],
        'batch_processed' => $batch_processed,
        'auto_continue' => $_SESSION['auto_continue']
    ]);
}