<?php
// Imposta il titolo della pagina
$page_title = "Aggiungi Libro";

// Includi l'header
include 'includes/header.php';

// Includi il file di connessione al database
require_once 'includes/db_connect.php';

// Elabora l'aggiunta di un nuovo libro
if(isset($_POST['add_book'])) {
    // Recupera tutti i campi dal form
    $inventario = $conn->real_escape_string($_POST['inventario']);
    $id_edificio = $conn->real_escape_string($_POST['id_edificio']);
    $sezione = $conn->real_escape_string($_POST['sezione']);
    $collocazione = $conn->real_escape_string($_POST['collocazione']);
    $sequenza = $conn->real_escape_string($_POST['sequenza']);
    $specificazione = $conn->real_escape_string($_POST['specificazione']);
    $stanza = $conn->real_escape_string($_POST['stanza']);
    $scaffale = $conn->real_escape_string($_POST['scaffale']);
    $stato = $conn->real_escape_string($_POST['stato']);
    
    // Controlla se l'inventario esiste già
    $check_query = "SELECT COUNT(*) as count FROM libri WHERE inventario = '$inventario'";
    $check_result = $conn->query($check_query);
    $row = $check_result->fetch_assoc();
    
    if($row['count'] > 0) {
        // L'inventario esiste già, mostra un errore
        $status = "errore";
        $message = "Errore: Il numero di inventario $inventario esiste già nel sistema.";
    } else {
        // Inserisci il nuovo libro
        $insert_query = "INSERT INTO libri (inventario, id_edificio, sezione, collocazione, sequenza, specificazione, stanza, scaffale, stato) 
                        VALUES ('$inventario', " . ($id_edificio ? "'$id_edificio'" : "NULL") . ", '$sezione', '$collocazione', '$sequenza', '$specificazione', '$stanza', '$scaffale', '$stato')";
        
        if($conn->query($insert_query) === TRUE) {
            $status = "successo";
            $message = "Libro aggiunto con successo.";
            
            // Reset dei campi del form per un nuovo inserimento
            $reset_form = true;
        } else {
            $status = "errore";
            $message = "Errore nell'aggiunta del libro: " . $conn->error;
        }
    }
}

// Recupera tutti gli edifici per il menu a tendina
$edifici_query = "SELECT id, nome FROM edifici ORDER BY nome ASC";
$edifici_result = $conn->query($edifici_query);
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="mb-4"><i class="bi bi-plus-circle"></i> Aggiungi Nuovo Libro</h1>
    </div>
</div>

<?php 
// Mostra messaggi di stato
if(isset($status)) {
    $alert_class = ($status == "successo") ? "alert-success" : "alert-danger";
    $icon_class = ($status == "successo") ? "bi-check-circle-fill" : "bi-exclamation-triangle-fill";
    
    echo "<div class='alert $alert_class alert-dismissible fade show' role='alert'>";
    echo "<i class='bi $icon_class me-2'></i>";
    echo $message;
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
    echo "</div>";
}
?>

<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card shadow-sm mb-4">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-journal-plus"></i> Dati del Nuovo Libro</h2>
            </div>
            <div class="card-body">
                <form action="add_book.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="inventario" class="form-label">Numero Inventario:*</label>
                            <input type="text" class="form-control" id="inventario" name="inventario" required 
                                value="<?php echo isset($_POST['inventario']) && !isset($reset_form) ? htmlspecialchars($_POST['inventario']) : ''; ?>">
                            <div class="form-text">Numero univoco che identifica il libro</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="id_edificio" class="form-label">Edificio:</label>
                            <select class="form-select" id="id_edificio" name="id_edificio">
                                <option value="">-- Seleziona Edificio --</option>
                                <?php 
                                if ($edifici_result->num_rows > 0) {
                                    while($edificio = $edifici_result->fetch_assoc()) {
                                        $selected = (isset($_POST['id_edificio']) && !isset($reset_form) && $_POST['id_edificio'] == $edificio['id']) ? 'selected' : '';
                                        echo '<option value="' . $edificio['id'] . '" ' . $selected . '>' . htmlspecialchars($edificio['nome']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <div class="form-text">Lascia vuoto se non è associato a nessun edificio</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="sezione" class="form-label">Sezione:*</label>
                            <input type="text" class="form-control" id="sezione" name="sezione" required
                                value="<?php echo isset($_POST['sezione']) && !isset($reset_form) ? htmlspecialchars($_POST['sezione']) : ''; ?>">
                            <div class="form-text">Es. N-V.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="collocazione" class="form-label">Collocazione:*</label>
                            <input type="text" class="form-control" id="collocazione" name="collocazione" required
                                value="<?php echo isset($_POST['collocazione']) && !isset($reset_form) ? htmlspecialchars($_POST['collocazione']) : ''; ?>">
                            <div class="form-text">Es. A.</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="sequenza" class="form-label">Sequenza:*</label>
                            <input type="text" class="form-control" id="sequenza" name="sequenza" required
                                value="<?php echo isset($_POST['sequenza']) && !isset($reset_form) ? htmlspecialchars($_POST['sequenza']) : ''; ?>">
                            <div class="form-text">Es. 1.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="specificazione" class="form-label">Specificazione:</label>
                        <input type="text" class="form-control" id="specificazione" name="specificazione" 
                            value="<?php echo isset($_POST['specificazione']) && !isset($reset_form) ? htmlspecialchars($_POST['specificazione']) : ''; ?>">
                        <div class="form-text">Campo di testo libero, utile per opere composte da più volumi</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stanza" class="form-label">Stanza:*</label>
                            <input type="text" class="form-control" id="stanza" name="stanza" required
                                value="<?php echo isset($_POST['stanza']) && !isset($reset_form) ? htmlspecialchars($_POST['stanza']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="scaffale" class="form-label">Scaffale:*</label>
                            <input type="text" class="form-control" id="scaffale" name="scaffale" required
                                value="<?php echo isset($_POST['scaffale']) && !isset($reset_form) ? htmlspecialchars($_POST['scaffale']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="stato" class="form-label">Stato:*</label>
                        <select class="form-select" id="stato" name="stato" required>
                            <option value="disponibile" <?php echo (isset($_POST['stato']) && !isset($reset_form) && $_POST['stato'] == 'disponibile') ? 'selected' : ''; ?>>Disponibile</option>
                            <option value="prestito" <?php echo (isset($_POST['stato']) && !isset($reset_form) && $_POST['stato'] == 'prestito') ? 'selected' : ''; ?>>In Prestito</option>
                            <option value="manutenzione" <?php echo (isset($_POST['stato']) && !isset($reset_form) && $_POST['stato'] == 'manutenzione') ? 'selected' : ''; ?>>In Manutenzione</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="index.php" class="btn btn-outline-secondary me-md-2">
                            <i class="bi bi-arrow-left me-1"></i> Annulla
                        </a>
                        <button type="submit" class="btn btn-primary" name="add_book">
                            <i class="bi bi-plus-circle me-1"></i> Aggiungi Libro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Includi il footer
include 'includes/footer.php';

// Chiudi la connessione al database
$conn->close();
?>