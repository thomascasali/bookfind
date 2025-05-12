<?php
// Imposta il titolo della pagina
$page_title = "Gestione Edifici";

// Includi l'header
include 'includes/header.php';

// Includi il file di connessione al database
require_once 'includes/db_connect.php';

// Elabora l'eliminazione di un edificio
if(isset($_POST['delete_building']) && isset($_POST['id'])) {
    $id = $conn->real_escape_string($_POST['id']);
    
    // Prima controlla se ci sono libri associati a questo edificio
    $check_query = "SELECT COUNT(*) as count FROM libri WHERE id_edificio = '$id'";
    $check_result = $conn->query($check_query);
    $row = $check_result->fetch_assoc();
    
    if($row['count'] > 0) {
        $delete_status = "errore";
        $delete_message = "Impossibile eliminare l'edificio. Ci sono " . $row['count'] . " libri associati a questo edificio.";
    } else {
        // Procedi con l'eliminazione se non ci sono libri associati
        $delete_query = "DELETE FROM edifici WHERE id = '$id'";
        if($conn->query($delete_query) === TRUE) {
            $delete_status = "successo";
            $delete_message = "Edificio eliminato con successo.";
        } else {
            $delete_status = "errore";
            $delete_message = "Errore nell'eliminazione dell'edificio: " . $conn->error;
        }
    }
}

// Elabora l'aggiunta di un nuovo edificio
if(isset($_POST['add_building'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $indirizzo = $conn->real_escape_string($_POST['indirizzo']);
    $citta = $conn->real_escape_string($_POST['citta']);
    $cap = $conn->real_escape_string($_POST['cap']);
    
    $add_query = "INSERT INTO edifici (nome, indirizzo, citta, cap) VALUES ('$nome', '$indirizzo', '$citta', '$cap')";
    
    if($conn->query($add_query) === TRUE) {
        $add_status = "successo";
        $add_message = "Nuovo edificio aggiunto con successo.";
    } else {
        $add_status = "errore";
        $add_message = "Errore nell'aggiunta dell'edificio: " . $conn->error;
    }
}

// Elabora l'aggiornamento di un edificio esistente
if(isset($_POST['update_building'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $nome = $conn->real_escape_string($_POST['nome']);
    $indirizzo = $conn->real_escape_string($_POST['indirizzo']);
    $citta = $conn->real_escape_string($_POST['citta']);
    $cap = $conn->real_escape_string($_POST['cap']);
    
    $update_query = "UPDATE edifici SET nome = '$nome', indirizzo = '$indirizzo', citta = '$citta', cap = '$cap' WHERE id = '$id'";
    
    if($conn->query($update_query) === TRUE) {
        $update_status = "successo";
        $update_message = "Edificio aggiornato con successo.";
    } else {
        $update_status = "errore";
        $update_message = "Errore nell'aggiornamento dell'edificio: " . $conn->error;
    }
}

// Recupera tutti gli edifici per visualizzarli
$sql = "SELECT * FROM edifici ORDER BY nome ASC";
$result = $conn->query($sql);
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="mb-4"><i class="bi bi-building"></i> Gestione Edifici</h1>
    </div>
</div>

<?php 
// Mostra messaggi di stato per eliminazione
if(isset($delete_status)) {
    $alert_class = ($delete_status == "successo") ? "alert-success" : "alert-danger";
    $icon_class = ($delete_status == "successo") ? "bi-check-circle-fill" : "bi-exclamation-triangle-fill";
    
    echo "<div class='alert $alert_class alert-dismissible fade show' role='alert'>";
    echo "<i class='bi $icon_class me-2'></i>";
    echo $delete_message;
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
    echo "</div>";
}

// Mostra messaggi di stato per aggiunta
if(isset($add_status)) {
    $alert_class = ($add_status == "successo") ? "alert-success" : "alert-danger";
    $icon_class = ($add_status == "successo") ? "bi-check-circle-fill" : "bi-exclamation-triangle-fill";
    
    echo "<div class='alert $alert_class alert-dismissible fade show' role='alert'>";
    echo "<i class='bi $icon_class me-2'></i>";
    echo $add_message;
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
    echo "</div>";
}

// Mostra messaggi di stato per aggiornamento
if(isset($update_status)) {
    $alert_class = ($update_status == "successo") ? "alert-success" : "alert-danger";
    $icon_class = ($update_status == "successo") ? "bi-check-circle-fill" : "bi-exclamation-triangle-fill";
    
    echo "<div class='alert $alert_class alert-dismissible fade show' role='alert'>";
    echo "<i class='bi $icon_class me-2'></i>";
    echo $update_message;
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
    echo "</div>";
}
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-plus-circle"></i> Aggiungi Nuovo Edificio</h2>
            </div>
            <div class="card-body">
                <form action="manage_buildings.php" method="POST">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Edificio:</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="indirizzo" class="form-label">Indirizzo:</label>
                        <input type="text" class="form-control" id="indirizzo" name="indirizzo" required>
                    </div>
                    <div class="mb-3">
                        <label for="citta" class="form-label">Città:</label>
                        <input type="text" class="form-control" id="citta" name="citta" required>
                    </div>
                    <div class="mb-3">
                        <label for="cap" class="form-label">CAP:</label>
                        <input type="text" class="form-control" id="cap" name="cap" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" name="add_building">
                            <i class="bi bi-plus-circle me-1"></i> Aggiungi Edificio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-list-check"></i> Edifici Esistenti</h2>
            </div>
            <div class="card-body">
                <?php if($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Indirizzo</th>
                                    <th>Città</th>
                                    <th>CAP</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row["id"]; ?></td>
                                        <td><?php echo $row["nome"]; ?></td>
                                        <td><?php echo $row["indirizzo"]; ?></td>
                                        <td><?php echo $row["citta"]; ?></td>
                                        <td><?php echo $row["cap"]; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-building" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editBuildingModal"
                                                    data-id="<?php echo $row["id"]; ?>"
                                                    data-nome="<?php echo htmlspecialchars($row["nome"]); ?>"
                                                    data-indirizzo="<?php echo htmlspecialchars($row["indirizzo"]); ?>"
                                                    data-citta="<?php echo htmlspecialchars($row["citta"]); ?>"
                                                    data-cap="<?php echo htmlspecialchars($row["cap"]); ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-danger delete-building" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteBuildingModal"
                                                    data-id="<?php echo $row["id"]; ?>"
                                                    data-nome="<?php echo htmlspecialchars($row["nome"]); ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Nessun edificio trovato. Aggiungi un nuovo edificio utilizzando il modulo a sinistra.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal per la modifica dell'edificio -->
<div class="modal fade" id="editBuildingModal" tabindex="-1" aria-labelledby="editBuildingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editBuildingModalLabel">Modifica Edificio</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="manage_buildings.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label for="edit_nome" class="form-label">Nome Edificio:</label>
                        <input type="text" class="form-control" id="edit_nome" name="nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_indirizzo" class="form-label">Indirizzo:</label>
                        <input type="text" class="form-control" id="edit_indirizzo" name="indirizzo" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_citta" class="form-label">Città:</label>
                        <input type="text" class="form-control" id="edit_citta" name="citta" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cap" class="form-label">CAP:</label>
                        <input type="text" class="form-control" id="edit_cap" name="cap" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary" name="update_building">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per la conferma di eliminazione -->
<div class="modal fade" id="deleteBuildingModal" tabindex="-1" aria-labelledby="deleteBuildingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteBuildingModalLabel">Conferma Eliminazione</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Sei sicuro di voler eliminare l'edificio <strong id="delete_building_name"></strong>?</p>
                <p class="text-danger">Attenzione: questa azione non può essere annullata. L'edificio può essere eliminato solo se non ha libri associati.</p>
            </div>
            <div class="modal-footer">
                <form action="manage_buildings.php" method="POST">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger" name="delete_building">Elimina</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Script per popolare il modal di modifica
document.addEventListener('DOMContentLoaded', function() {
    // Gestione del modal di modifica
    const editButtons = document.querySelectorAll('.edit-building');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            const indirizzo = this.getAttribute('data-indirizzo');
            const citta = this.getAttribute('data-citta');
            const cap = this.getAttribute('data-cap');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_indirizzo').value = indirizzo;
            document.getElementById('edit_citta').value = citta;
            document.getElementById('edit_cap').value = cap;
        });
    });
    
    // Gestione del modal di eliminazione
    const deleteButtons = document.querySelectorAll('.delete-building');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_building_name').textContent = nome;
        });
    });
});
</script>

<?php
// Includi il footer
include 'includes/footer.php';

// Chiudi la connessione al database
$conn->close();
?>