<?php
// Avvia la sessione all'inizio del file, prima di qualsiasi output
session_start();

// Imposta il titolo della pagina
$page_title = "Importa Libri";

// Aumentiamo i limiti di PHP per gestire file grandi
ini_set('max_execution_time', 600); // 10 minuti
ini_set('memory_limit', '512M');    // 512 MB di memoria

// Definisci dimensione del batch
$batch_size = 1000; // Numero di record da importare in un singolo batch

// Inizializza le variabili di sessione per l'importazione batch se non esistono
if(!isset($_SESSION['import_in_progress'])) {
    $_SESSION['import_in_progress'] = false;
    $_SESSION['import_file'] = '';
    $_SESSION['import_offset'] = 0;
    $_SESSION['import_total'] = 0;
    $_SESSION['import_processed'] = 0;
    $_SESSION['import_success'] = 0;
    $_SESSION['import_duplicates'] = 0;
    $_SESSION['import_errors'] = [];
    $_SESSION['import_id_edificio'] = '';
    $_SESSION['auto_continue'] = false;
    $_SESSION['last_batch_time'] = 0;
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
    $_SESSION['import_duplicates'] = 0;
    $_SESSION['import_errors'] = [];
    $_SESSION['import_id_edificio'] = '';
    $_SESSION['auto_continue'] = false;
    $_SESSION['last_batch_time'] = 0;
    
    // Impostiamo un flag invece di fare redirect
    $redirect_to_import = true;
}

// Gestione iniziale dell'importazione (solo caricamento del file)
$import_message = '';
$import_status = '';

if(isset($_POST['submit_import'])) {
    // Includi il file di connessione al database
    require_once 'includes/db_connect.php';
    
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
            $_SESSION['import_duplicates'] = 0;
            $_SESSION['import_errors'] = [];
            $_SESSION['import_id_edificio'] = $_POST['id_edificio'];
            
            // Se la checkbox di auto-continuazione è selezionata
            if(isset($_POST['auto_continue']) && $_POST['auto_continue'] == '1') {
                $_SESSION['auto_continue'] = true;
            }
            
            $import_message = "File caricato con successo. L'importazione inizierà a breve.";
            $import_status = "success";
        } else {
            $import_message = "Errore nel caricamento del file.";
            $import_status = "danger";
        }
    }
    
    // Chiudi la connessione al database
    $conn->close();
}

// Includi l'header dopo aver gestito le operazioni
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
    <div class="col-lg-10 col-md-12 mx-auto">
        <div class="card shadow-sm mb-4">
            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><i class="bi bi-arrow-repeat"></i> Importazione in corso</h2>
                <div id="import-status-badge" class="badge bg-primary">In attesa</div>
            </div>
            <div class="card-body">
                <div class="progress mb-3">
                    <?php 
                    $progress = ($_SESSION['import_processed'] / $_SESSION['import_total']) * 100;
                    ?>
                    <div class="progress-bar progress-bar-striped" 
                         id="progress-bar"
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
                    Elaborati <span id="processed-count"><?php echo $_SESSION['import_processed']; ?></span> di <span id="total-count"><?php echo $_SESSION['import_total']; ?></span> record.<br>
                    <span id="success-count"><?php echo $_SESSION['import_success']; ?></span> libri importati con successo.<br>
                    <span id="duplicates-count"><?php echo $_SESSION['import_duplicates']; ?></span> record saltati (già esistenti).<br>
                    <span id="errors-count"><?php echo count($_SESSION['import_errors']); ?></span> errori riscontrati.<br>
                    Tempo ultimo batch: <span id="last-batch-time"><?php echo $_SESSION['last_batch_time']; ?></span> secondi.
                </div>
                
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="auto_continue_switch" 
                                   <?php echo $_SESSION['auto_continue'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="auto_continue_switch">Continua automaticamente</label>
                        </div>
                    </div>
                    <div>
                        <button id="start-import-btn" class="btn btn-primary">
                            <i class="bi bi-play-fill me-1"></i> Avvia/Continua Importazione
                        </button>
                        <button id="cancel-import-btn" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i> Annulla Importazione
                        </button>
                    </div>
                </div>
                
                <div id="import-log" class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                    <div class="text-muted">Log dell'importazione apparirà qui...</div>
                </div>
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
                
                <div id="error-log" class="error-log" style="max-height: 300px; overflow-y: auto;">
                    <ul class="list-group" id="error-list">
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

<!-- Script per gestire l'importazione con AJAX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startImportBtn = document.getElementById('start-import-btn');
    const cancelImportBtn = document.getElementById('cancel-import-btn');
    const autoContinueSwitch = document.getElementById('auto_continue_switch');
    const importLog = document.getElementById('import-log');
    const progressBar = document.getElementById('progress-bar');
    const importStatusBadge = document.getElementById('import-status-badge');
    
    let isImporting = false;
    
    // Funzione per aggiungere messaggi al log
    function addToLog(message, type = 'info') {
        const time = new Date().toLocaleTimeString();
        const logClass = type === 'error' ? 'text-danger' : 
                         type === 'success' ? 'text-success' : 'text-info';
        
        const logEntry = document.createElement('div');
        logEntry.className = logClass;
        logEntry.innerHTML = `<small>[${time}] ${message}</small>`;
        
        importLog.appendChild(logEntry);
        importLog.scrollTop = importLog.scrollHeight; // Auto-scroll
    }
    
    // Funzione per aggiornare l'interfaccia
    function updateUI(data) {
        // Aggiorna la barra di progresso
        const progress = Math.round((data.processed / data.total) * 100);
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', progress);
        progressBar.textContent = progress + '%';
        
        // Aggiorna i contatori
        document.getElementById('processed-count').textContent = data.processed;
        document.getElementById('success-count').textContent = data.success;
        document.getElementById('duplicates-count').textContent = data.duplicates;
        document.getElementById('errors-count').textContent = data.errors.length;
        document.getElementById('last-batch-time').textContent = data.last_batch_time;
        
        // Aggiorna il badge di stato
        if (data.completed) {
            importStatusBadge.className = 'badge bg-success';
            importStatusBadge.textContent = 'Completato';
            progressBar.classList.remove('progress-bar-animated');
            isImporting = false;
            startImportBtn.disabled = true;
        } else if (isImporting) {
            importStatusBadge.className = 'badge bg-primary';
            importStatusBadge.textContent = 'In corso';
            progressBar.classList.add('progress-bar-animated');
        } else {
            importStatusBadge.className = 'badge bg-warning';
            importStatusBadge.textContent = 'In pausa';
            progressBar.classList.remove('progress-bar-animated');
        }
        
        // Aggiorna la lista degli errori
        if (data.errors && data.errors.length > 0) {
            const errorList = document.getElementById('error-list');
            if (errorList) {
                errorList.innerHTML = '';
                
                // Mostra solo i primi 50 errori
                const errorsToShow = data.errors.slice(0, 50);
                errorsToShow.forEach(error => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-warning';
                    li.textContent = error;
                    errorList.appendChild(li);
                });
                
                // Mostra il conteggio degli errori rimanenti
                if (data.errors.length > 50) {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-warning';
                    li.textContent = `... e altri ${data.errors.length - 50} errori (non visualizzati).`;
                    errorList.appendChild(li);
                }
            }
        }
    }
    
    // Funzione per elaborare un batch
    function processBatch() {
        if (!isImporting) return;
        
        importStatusBadge.className = 'badge bg-primary';
        importStatusBadge.textContent = 'In corso';
        progressBar.classList.add('progress-bar-animated');
        
        addToLog('Elaborazione batch in corso...');
        
        // Disabilita i pulsanti durante l'elaborazione
        startImportBtn.disabled = true;
        
        fetch('import_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=process_batch'
        })
        .then(response => response.json())
        .then(data => {
            // Aggiorna l'interfaccia utente
            updateUI(data);
            
            // Aggiungi messaggi al log
            if (data.batch_processed > 0) {
                addToLog(`Elaborati ${data.batch_processed} record in ${data.last_batch_time} secondi.`, 'success');
            }
            
            if (data.completed) {
                addToLog('Importazione completata!', 'success');
                isImporting = false;
                startImportBtn.disabled = true;
            } else {
                startImportBtn.disabled = false;
                
                // Se l'auto-continua è attivo, avvia il prossimo batch
                if (autoContinueSwitch.checked && isImporting) {
                    addToLog('Auto-continuazione attiva, prossimo batch tra 2 secondi...');
                    setTimeout(processBatch, 2000);
                } else {
                    importStatusBadge.className = 'badge bg-warning';
                    importStatusBadge.textContent = 'In pausa';
                    progressBar.classList.remove('progress-bar-animated');
                    isImporting = false;
                }
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            addToLog('Errore durante l\'elaborazione: ' + error.message, 'error');
            
            importStatusBadge.className = 'badge bg-danger';
            importStatusBadge.textContent = 'Errore';
            progressBar.classList.remove('progress-bar-animated');
            
            startImportBtn.disabled = false;
            isImporting = false;
        });
    }
    
    // Evento per il pulsante Avvia/Continua
    startImportBtn.addEventListener('click', function() {
        isImporting = true;
        processBatch();
    });
    
    // Evento per il toggle di auto-continua
    autoContinueSwitch.addEventListener('change', function() {
        fetch('import_ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=toggle_auto_continue&value=' + (this.checked ? '1' : '0')
        })
        .then(response => response.json())
        .then(data => {
            addToLog(`Auto-continuazione ${data.auto_continue ? 'attivata' : 'disattivata'}.`);
            
            // Se l'auto-continua è attivato e non stiamo già importando, avvia l'importazione
            if (data.auto_continue && !isImporting && !data.completed) {
                isImporting = true;
                processBatch();
            }
        });
    });
    
    // Evento per il pulsante Annulla
    cancelImportBtn.addEventListener('click', function() {
        if (confirm('Sei sicuro di voler annullare l\'importazione? I record già importati rimarranno nel database.')) {
            // Invia la richiesta di annullamento al server
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'import.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'cancel_import';
            input.value = '1';
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    });
    
    // Aggiungi un messaggio iniziale al log
    addToLog('Importazione inizializzata. Premi "Avvia/Continua Importazione" per iniziare.');
    
    // Se l'auto-continua è attivo, avvia l'importazione automaticamente
    if (autoContinueSwitch.checked && <?php echo $_SESSION['import_processed'] < $_SESSION['import_total'] ? 'true' : 'false'; ?>) {
        addToLog('Auto-continuazione attivata, avvio importazione...');
        setTimeout(function() {
            isImporting = true;
            processBatch();
        }, 1000);
    }
});
</script>

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
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="auto_continue" name="auto_continue" value="1" checked>
                        <label class="form-check-label" for="auto_continue">Continua automaticamente tra i batch</label>
                        <div class="form-text">Seleziona questa opzione per continuare automaticamente l'importazione senza dover cliccare dopo ogni batch.</div>
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