<?php
// Avvia la sessione all'inizio del file, prima di qualsiasi output
session_start();

// Imposta il titolo della pagina
$page_title = "Importa Libri";

// Aumentiamo i limiti di PHP per gestire file grandi
ini_set('max_execution_time', 300); // 5 minuti
ini_set('memory_limit', '256M');    // 256 MB di memoria

// Definisci dimensione del batch
$batch_size = 100; // Numero di record da importare in un singolo batch

// Inizializza le variabili di sessione per l'importazione batch se non esistono
if(!isset($_SESSION['import_in_progress'])) {
    $_SESSION['import_in_progress'] = false;
    $_SESSION['import_file'] = '';
    $_SESSION['import_offset'] = 0;
    $_SESSION['import_total'] = 0;
    $_SESSION['import_processed'] = 0;
    $_SESSION['import_success'] = 0;
    $_SESSION['import_errors'] = [];
    $_SESSION['import_id_edificio'] = '';
}

// Gestisci l'annullamento dell'importazione
if(isset($_POST['cancel_import'])) {
    // Elimina il file temporaneo se esiste
    if(file_exists($_SESSION['import_file'])) {
        @unlink($_SESSION['import_file']);
    }
    
    // Reimposta le variabili di sessione
    $_SESSION['import_in_progress'] = false;
    $_SESSION['import_file'] = '';
    $_SESSION['import_offset'] = 0;
    $_SESSION['import_total'] = 0;
    $_SESSION['import_processed'] = 0;
    $_SESSION['import_success'] = 0;
    $_SESSION['import_errors'] = [];
    $_SESSION['import_id_edificio'] = '';
    
    // Impostiamo un flag invece di fare redirect
    $redirect_to_import = true;
}

// Gestione dell'importazione
$import_message = '';
$import_status = '';

if(isset($_POST['submit_import']) || isset($_POST['continue_import'])) {
    // Includi il file di connessione al database
    if (!isset($conn) || $conn->connect_error) {
        require_once 'includes/db_connect.php';
    }
    
    // Fase iniziale dell'importazione
    if(isset($_POST['submit_import'])) {
        // Controlla se il file è stato caricato correttamente
        if(!isset($_FILES["csv_file"]) || $_FILES["csv_file"]["error"] != 0) {
            $import_message = "Errore nel caricamento del file. Codice errore: " . ($_FILES["csv_file"]["error"] ?? 'Sconosciuto');
            $import_status = "danger";
        }
        // Controlla se è stato selezionato un edificio
        elseif(!isset($_POST['id_edificio']) || empty($_POST['id_edificio'])) {
            $import_message = "Seleziona un edificio di destinazione.";
            $import_status = "danger";
        }
        else {
            // Directory temporanea per salvare il file caricato
            $target_dir = "uploads/"; 
            // Crea la directory di upload se non esiste
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Genera un nome file univoco
            $temp_file = $target_dir . uniqid('import_') . '.csv';
            
            // Controllo del tipo di file
            $fileType = strtolower(pathinfo($_FILES["csv_file"]["name"], PATHINFO_EXTENSION));
            if($fileType != "csv") {
                $import_message = "Errore: è consentito solo l'upload di file CSV.";
                $import_status = "danger";
            }
            // Sposta il file caricato nella directory temporanea
            elseif(move_uploaded_file($_FILES["csv_file"]["tmp_name"], $temp_file)) {
                // Conta il numero totale di righe nel file CSV
                $total_rows = 0;
                $file = fopen($temp_file, 'r');
                while(fgetcsv($file)) {
                    $total_rows++;
                }
                fclose($file);
                
                // Sottrai 1 per la riga di intestazione
                $total_rows = max(0, $total_rows - 1);
                
                // Inizializza le variabili di sessione per l'importazione batch
                $_SESSION['import_in_progress'] = true;
                $_SESSION['import_file'] = $temp_file;
                $_SESSION['import_offset'] = 0; // Inizia dalla prima riga dopo l'intestazione
                $_SESSION['import_total'] = $total_rows;
                $_SESSION['import_processed'] = 0;
                $_SESSION['import_success'] = 0;
                $_SESSION['import_errors'] = [];
                $_SESSION['import_id_edificio'] = $_POST['id_edificio'];
                
                // Impostiamo un flag invece di fare redirect
                $continue_import = true;
            } else {
                $import_message = "Errore nel caricamento del file.";
                $import_status = "danger";
            }
        }
    }
    
    // Fase di elaborazione batch
    if($_SESSION['import_in_progress'] && (isset($_POST['continue_import']) || isset($continue_import))) {
        $import_file = $_SESSION['import_file'];
        $offset = $_SESSION['import_offset'];
        $id_edificio = $_SESSION['import_id_edificio'];
        
        // Assicurati che il file esista
        if(!file_exists($import_file)) {
            $import_message = "File di importazione non trovato.";
            $import_status = "danger";
            $_SESSION['import_in_progress'] = false;
        }
        else {
            // Apri il file CSV
            $file = fopen($import_file, 'r');
            
            // Salta l'intestazione se è la prima elaborazione
            if($offset == 0) {
                fgetcsv($file);
            } else {
                // Salta le righe già elaborate
                for($i = 0; $i <= $offset; $i++) {
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
                        $_SESSION['import_errors'][] = "Errore alla riga " . ($_SESSION['import_offset'] + $batch_processed) . ": Inventario '$inventario' già esistente nel database. Riga saltata.";
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
            
            // Verifica se l'importazione è completa
            if($_SESSION['import_processed'] >= $_SESSION['import_total']) {
                // L'importazione è completata
                $import_message = "Importazione completata. Importati " . $_SESSION['import_success'] . " libri su " . $_SESSION['import_total'] . " righe processate.";
                if(count($_SESSION['import_errors']) > 0) {
                    $import_message .= " " . count($_SESSION['import_errors']) . " righe sono state saltate a causa di errori.";
                }
                $import_status = "success";
                
                // Elimina il file temporaneo
                @unlink($import_file);
                
                // NON reimpostiamo ancora le variabili di sessione per mostrare il risultato finale
            }
        }
    }
    
    // Chiudi la connessione al database
    $conn->close();
}

// Ora includi l'header dopo aver gestito le operazioni
include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="mb-4"><i class="bi bi-file-earmark-arrow-up"></i> Importa Libri da CSV</h1>
    </div>
</div>

<?php if(isset($import_message) && !empty($import_message)): ?>
<div class="alert alert-<?php echo $import_status; ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?php echo ($import_status == 'success') ? 'check-circle-fill' : 'exclamation-triangle-fill'; ?> me-2"></i>
    <?php echo $import_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if(isset($redirect_to_import) && $redirect_to_import): ?>
<script>
    // Redirect con JavaScript invece che con header()
    window.location.href = 'import.php';
</script>
<?php endif; ?>

<?php if($_SESSION['import_in_progress']): ?>
<!-- Visualizzazione dello stato di importazione batch -->
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card shadow-sm mb-4">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-arrow-repeat"></i> Importazione in corso</h2>
            </div>
            <div class="card-body">
                <div class="progress mb-3">
                    <?php 
                    $progress = ($_SESSION['import_processed'] / $_SESSION['import_total']) * 100;
                    ?>
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: <?php echo $progress; ?>%" 
                         aria-valuenow="<?php echo $progress; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        <?php echo round($progress); ?>%
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Stato importazione:</strong><br>
                    Elaborati <?php echo $_SESSION['import_processed']; ?> di <?php echo $_SESSION['import_total']; ?> record.<br>
                    <?php echo $_SESSION['import_success']; ?> libri importati con successo.<br>
                    <?php echo count($_SESSION['import_errors']); ?> errori riscontrati.
                </div>
                
                <form action="import.php" method="POST" class="text-center">
                    <button type="submit" name="continue_import" class="btn btn-primary">
                        <i class="bi bi-arrow-repeat me-1"></i> Continua Importazione
                    </button>
                    <button type="submit" name="cancel_import" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i> Annulla Importazione
                    </button>
                </form>
            </div>
        </div>
        
        <?php if(count($_SESSION['import_errors']) > 0): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-exclamation-triangle"></i> Errori riscontrati</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Sono stati riscontrati degli errori durante l'importazione:</strong>
                </div>
                
                <div class="error-log" style="max-height: 300px; overflow-y: auto;">
                    <ul class="list-group">
                        <?php 
                        // Limita il numero di errori visualizzati per non appesantire la pagina
                        $errors_to_show = array_slice($_SESSION['import_errors'], 0, 50);
                        foreach($errors_to_show as $error): 
                        ?>
                        <li class="list-group-item list-group-item-warning">
                            <?php echo $error; ?>
                        </li>
                        <?php endforeach; ?>
                        
                        <?php if(count($_SESSION['import_errors']) > 50): ?>
                        <li class="list-group-item list-group-item-warning">
                            ... e altri <?php echo count($_SESSION['import_errors']) - 50; ?> errori (non visualizzati).
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<!-- Form di caricamento file CSV -->
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card shadow-sm mb-4">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-upload"></i> Seleziona File e Destinazione</h2>
            </div>
            <div class="card-body">
                <form action="import.php" method="POST" enctype="multipart/form-data">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Esporta il tuo foglio Google Sheet in formato CSV e caricalo qui.
                    </div>
                    
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Seleziona file CSV:</label>
                        <input type="file" class="form-control" name="csv_file" id="csv_file" accept=".csv" required>
                        <div class="form-text">Il file deve contenere intestazioni nella prima riga.</div>
                    </div>

                    <div class="mb-3">
                        <label for="id_edificio" class="form-label">Edificio di destinazione:</label>
                        <select class="form-select" name="id_edificio" id="id_edificio" required>
                            <option value="">-- Seleziona Edificio --</option>
                            <?php
                            // Includi il file di connessione al database per recuperare gli edifici
                            require_once 'includes/db_connect.php';

                            // Recupera gli edifici dal database
                            $sql_edifici = "SELECT id, nome FROM edifici ORDER BY nome ASC";
                            $result_edifici = $conn->query($sql_edifici);

                            if ($result_edifici->num_rows > 0) {
                                while($row_edificio = $result_edifici->fetch_assoc()) {
                                    echo '<option value="' . $row_edificio["id"] . '">' . htmlspecialchars($row_edificio["nome"]) . '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>Nessun edificio trovato. Aggiungine uno prima.</option>';
                            }
                            
                            // Chiudi la connessione (sarà riaperta se necessario)
                            $conn->close();
                            ?>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" name="submit_import">
                            <i class="bi bi-upload me-1"></i> Avvia Importazione
                        </button>
                        <a href="manage_buildings.php" class="btn btn-outline-secondary">
                            <i class="bi bi-building me-1"></i> Gestisci Edifici
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Includi il footer
include 'includes/footer.php';
?>