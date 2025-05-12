<?php
// Imposta il titolo della pagina
$page_title = "Gestione Libri";

// Includi l'header
include 'includes/header.php';

// Includi il file di connessione al database
require_once 'includes/db_connect.php';

// Operazioni sui libri (eliminazione, aggiornamento)
processBookOperations($conn);

// Inizializza le variabili per la paginazione e la ricerca
$search_params = initializeSearchParams();
$pagination = initializePagination($conn, $search_params);

// Recupera i libri in base ai parametri di ricerca e paginazione
$books = getBooks($conn, $search_params, $pagination);

// Recupera tutti gli edifici per il menu a tendina
$edifici = getBuildings($conn);

/******** FUNZIONI ********/

/**
 * Gestisce le operazioni sui libri (eliminazione, aggiornamento)
 */
function processBookOperations($conn) {
    // Elabora l'eliminazione di un libro
    if(isset($_POST['delete_book']) && isset($_POST['inventario'])) {
        $inventario = $conn->real_escape_string($_POST['inventario']);
        
        // Procedi con l'eliminazione
        $delete_query = "DELETE FROM libri WHERE inventario = '$inventario'";
        if($conn->query($delete_query) === TRUE) {
            $GLOBALS['delete_status'] = "successo";
            $GLOBALS['delete_message'] = "Libro eliminato con successo.";
        } else {
            $GLOBALS['delete_status'] = "errore";
            $GLOBALS['delete_message'] = "Errore nell'eliminazione del libro: " . $conn->error;
        }
    }

    // Elabora l'aggiornamento di un libro esistente
    if(isset($_POST['update_book'])) {
        $inventario = $conn->real_escape_string($_POST['inventario']);
        $id_edificio = $conn->real_escape_string($_POST['id_edificio']);
        $sezione = $conn->real_escape_string($_POST['sezione']);
        $collocazione = $conn->real_escape_string($_POST['collocazione']);
        $sequenza = $conn->real_escape_string($_POST['sequenza']);
        $specificazione = $conn->real_escape_string($_POST['specificazione']);
        $stanza = $conn->real_escape_string($_POST['stanza']);
        $scaffale = $conn->real_escape_string($_POST['scaffale']);
        $stato = $conn->real_escape_string($_POST['stato']);
        
        $update_query = "UPDATE libri SET 
                        id_edificio = " . ($id_edificio ? "'$id_edificio'" : "NULL") . ", 
                        sezione = '$sezione', 
                        collocazione = '$collocazione', 
                        sequenza = '$sequenza', 
                        specificazione = '$specificazione', 
                        stanza = '$stanza', 
                        scaffale = '$scaffale', 
                        stato = '$stato' 
                        WHERE inventario = '$inventario'";
        
        if($conn->query($update_query) === TRUE) {
            $GLOBALS['update_status'] = "successo";
            $GLOBALS['update_message'] = "Libro aggiornato con successo.";
        } else {
            $GLOBALS['update_status'] = "errore";
            $GLOBALS['update_message'] = "Errore nell'aggiornamento del libro: " . $conn->error;
        }
    }
}

/**
 * Inizializza i parametri di ricerca dai parametri GET
 */
function initializeSearchParams() {
    return array(
        'search_inventario' => isset($_GET['search_inventario']) ? $_GET['search_inventario'] : '',
        'search_sezione' => isset($_GET['search_sezione']) ? $_GET['search_sezione'] : '',
        'search_stanza' => isset($_GET['search_stanza']) ? $_GET['search_stanza'] : '',
        'search_edificio' => isset($_GET['search_edificio']) ? $_GET['search_edificio'] : '',
        'search_stato' => isset($_GET['search_stato']) ? $_GET['search_stato'] : ''
    );
}

/**
 * Inizializza i parametri di paginazione
 */
function initializePagination($conn, $search_params) {
    // Imposta i parametri di paginazione
    $per_pagina = 100; // 100 libri per pagina
    $pagina_corrente = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    
    // Costruisce la clausola WHERE per la ricerca
    $where_clause = buildWhereClause($search_params);
    
    // Query per contare il totale dei libri
    $count_query = "SELECT COUNT(*) as total FROM libri l WHERE 1=1 $where_clause";
    $count_result = $conn->query($count_query);
    $count_row = $count_result->fetch_assoc();
    $total_records = $count_row['total'];
    $total_pagine = ceil($total_records / $per_pagina);
    
    // Assicurati che la pagina corrente non superi il totale delle pagine
    $pagina_corrente = min($pagina_corrente, max(1, $total_pagine));
    
    // Calcola l'offset per la query
    $offset = ($pagina_corrente - 1) * $per_pagina;
    
    return array(
        'per_pagina' => $per_pagina,
        'pagina_corrente' => $pagina_corrente,
        'total_records' => $total_records,
        'total_pagine' => $total_pagine,
        'offset' => $offset
    );
}

/**
 * Costruisce la clausola WHERE per la ricerca
 */
function buildWhereClause($search_params) {
    global $conn;
    
    $where_clause = "";
    if(!empty($search_params['search_inventario'])) {
        $inventario = $conn->real_escape_string($search_params['search_inventario']);
        $where_clause .= " AND l.inventario LIKE '%$inventario%'";
    }
    if(!empty($search_params['search_sezione'])) {
        $sezione = $conn->real_escape_string($search_params['search_sezione']);
        $where_clause .= " AND l.sezione LIKE '%$sezione%'";
    }
    if(!empty($search_params['search_stanza'])) {
        $stanza = $conn->real_escape_string($search_params['search_stanza']);
        $where_clause .= " AND l.stanza LIKE '%$stanza%'";
    }
    if(!empty($search_params['search_edificio'])) {
        $edificio = $conn->real_escape_string($search_params['search_edificio']);
        $where_clause .= " AND l.id_edificio = '$edificio'";
    }
    if(!empty($search_params['search_stato'])) {
        $stato = $conn->real_escape_string($search_params['search_stato']);
        $where_clause .= " AND l.stato = '$stato'";
    }
    
    return $where_clause;
}

/**
 * Recupera i libri dal database
 */
function getBooks($conn, $search_params, $pagination) {
    // Costruisce la clausola WHERE
    $where_clause = buildWhereClause($search_params);
    
    // Query per recuperare i libri con paginazione
    $sql = "SELECT l.*, e.nome AS nome_edificio
            FROM libri l
            LEFT JOIN edifici e ON l.id_edificio = e.id
            WHERE 1=1 $where_clause
            ORDER BY l.inventario ASC
            LIMIT {$pagination['offset']}, {$pagination['per_pagina']}";
    
    return $conn->query($sql);
}

/**
 * Recupera tutti gli edifici
 */
function getBuildings($conn) {
    $edifici = array();
    $edifici_query = "SELECT id, nome FROM edifici ORDER BY nome ASC";
    $edifici_result = $conn->query($edifici_query);
    
    if ($edifici_result->num_rows > 0) {
        while($edificio = $edifici_result->fetch_assoc()) {
            $edifici[$edificio['id']] = $edificio['nome'];
        }
    }
    
    return $edifici;
}

/**
 * Genera i controlli di paginazione
 */
function generatePaginationControls($pagination, $search_params) {
    $pagina_corrente = $pagination['pagina_corrente'];
    $total_pagine = $pagination['total_pagine'];
    
    // Non mostrare la paginazione se c'è solo una pagina
    if($total_pagine <= 1) {
        return '';
    }
    
    // Costruisci la query string per i link di paginazione
    $query_params = array();
    foreach($search_params as $key => $value) {
        if(!empty($value)) {
            $query_params[] = $key . '=' . urlencode($value);
        }
    }
    $query_string = implode('&', $query_params);
    if(!empty($query_string)) {
        $query_string = '&' . $query_string;
    }
    
    $html = '<nav aria-label="Paginazione risultati">';
    $html .= '<ul class="pagination justify-content-center">';
    
    // Pulsante "Precedente"
    $disabled = ($pagina_corrente <= 1) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $disabled . '">';
    if($pagina_corrente > 1) {
        $html .= '<a class="page-link" href="?pagina=' . ($pagina_corrente - 1) . $query_string . '" aria-label="Precedente">';
    } else {
        $html .= '<span class="page-link" aria-label="Precedente">';
    }
    $html .= '<span aria-hidden="true">&laquo;</span>';
    if($pagina_corrente > 1) {
        $html .= '</a>';
    } else {
        $html .= '</span>';
    }
    $html .= '</li>';
    
    // Determina il range di pagine da mostrare
    $range = 2;
    $start_page = max(1, $pagina_corrente - $range);
    $end_page = min($total_pagine, $pagina_corrente + $range);
    
    // Aggiungi pulsante per la prima pagina se necessario
    if($start_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?pagina=1' . $query_string . '">1</a></li>';
        if($start_page > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Pagine numerate
    for($i = $start_page; $i <= $end_page; $i++) {
        $active = ($pagina_corrente == $i) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">';
        if($active) {
            $html .= '<span class="page-link">' . $i . '</span>';
        } else {
            $html .= '<a class="page-link" href="?pagina=' . $i . $query_string . '">' . $i . '</a>';
        }
        $html .= '</li>';
    }
    
    // Aggiungi pulsante per l'ultima pagina se necessario
    if($end_page < $total_pagine) {
        if($end_page < $total_pagine - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="?pagina=' . $total_pagine . $query_string . '">' . $total_pagine . '</a></li>';
    }
    
    // Pulsante "Successivo"
    $disabled = ($pagina_corrente >= $total_pagine) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $disabled . '">';
    if($pagina_corrente < $total_pagine) {
        $html .= '<a class="page-link" href="?pagina=' . ($pagina_corrente + 1) . $query_string . '" aria-label="Successivo">';
    } else {
        $html .= '<span class="page-link" aria-label="Successivo">';
    }
    $html .= '<span aria-hidden="true">&raquo;</span>';
    if($pagina_corrente < $total_pagine) {
        $html .= '</a>';
    } else {
        $html .= '</span>';
    }
    $html .= '</li>';
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="mb-4"><i class="bi bi-journal-text"></i> Gestione Libri</h1>
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

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-search"></i> Cerca Libri</h2>
            </div>
            <div class="card-body">
                <form action="manage_books.php" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="search_inventario" class="form-label">Inventario:</label>
                        <input type="text" class="form-control" id="search_inventario" name="search_inventario" 
                               value="<?php echo htmlspecialchars($search_params['search_inventario']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="search_sezione" class="form-label">Sezione:</label>
                        <input type="text" class="form-control" id="search_sezione" name="search_sezione" 
                               value="<?php echo htmlspecialchars($search_params['search_sezione']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="search_stanza" class="form-label">Stanza:</label>
                        <input type="text" class="form-control" id="search_stanza" name="search_stanza" 
                               value="<?php echo htmlspecialchars($search_params['search_stanza']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="search_edificio" class="form-label">Edificio:</label>
                        <select class="form-select" id="search_edificio" name="search_edificio">
                            <option value="">Tutti gli edifici</option>
                            <?php foreach($edifici as $id => $nome): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($search_params['search_edificio'] == $id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($nome); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search_stato" class="form-label">Stato:</label>
                        <select class="form-select" id="search_stato" name="search_stato">
                            <option value="">Tutti gli stati</option>
                            <option value="disponibile" <?php echo ($search_params['search_stato'] == 'disponibile') ? 'selected' : ''; ?>>Disponibile</option>
                            <option value="prestito" <?php echo ($search_params['search_stato'] == 'prestito') ? 'selected' : ''; ?>>In Prestito</option>
                            <option value="manutenzione" <?php echo ($search_params['search_stato'] == 'manutenzione') ? 'selected' : ''; ?>>In Manutenzione</option>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-1"></i> Cerca
                        </button>
                        <a href="manage_books.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><i class="bi bi-list-check"></i> Elenco Libri</h2>
                <div>
                    <span class="badge bg-primary me-2">
                        <?php echo $pagination['total_records']; ?> libri trovati
                    </span>
                    <a href="add_book.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Aggiungi Nuovo Libro
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if($books->num_rows > 0): ?>
                    <!-- Paginazione superiore -->
                    <?php echo generatePaginationControls($pagination, $search_params); ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Inventario</th>
                                    <th>Edificio</th>
                                    <th>Sezione</th>
                                    <th>Collocazione</th>
                                    <th>Sequenza</th>
                                    <th class="d-none d-md-table-cell">Specificazione</th>
                                    <th>Stanza</th>
                                    <th>Scaffale</th>
                                    <th>Stato</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $books->fetch_assoc()): ?>
                                    <?php
                                    // Determina la classe CSS per lo stato
                                    $statoClass = '';
                                    switch($row["stato"]) {
                                        case 'prestito':
                                            $statoClass = 'text-danger';
                                            break;
                                        case 'manutenzione':
                                            $statoClass = 'text-warning';
                                            break;
                                        default: // disponibile
                                            $statoClass = 'text-success';
                                            break;
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $row["inventario"]; ?></td>
                                        <td><?php echo $row["nome_edificio"] ? $row["nome_edificio"] : "Nessuno"; ?></td>
                                        <td><?php echo $row["sezione"]; ?></td>
                                        <td><?php echo $row["collocazione"]; ?></td>
                                        <td><?php echo $row["sequenza"]; ?></td>
                                        <td class="d-none d-md-table-cell"><?php echo $row["specificazione"]; ?></td>
                                        <td><?php echo $row["stanza"]; ?></td>
                                        <td><?php echo $row["scaffale"]; ?></td>
                                        <td class="<?php echo $statoClass; ?>"><?php echo ucfirst($row["stato"]); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-book" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editBookModal"
                                                    data-inventario="<?php echo $row["inventario"]; ?>"
                                                    data-id-edificio="<?php echo $row["id_edificio"]; ?>"
                                                    data-sezione="<?php echo htmlspecialchars($row["sezione"]); ?>"
                                                    data-collocazione="<?php echo htmlspecialchars($row["collocazione"]); ?>"
                                                    data-sequenza="<?php echo htmlspecialchars($row["sequenza"]); ?>"
                                                    data-specificazione="<?php echo htmlspecialchars($row["specificazione"]); ?>"
                                                    data-stanza="<?php echo htmlspecialchars($row["stanza"]); ?>"
                                                    data-scaffale="<?php echo htmlspecialchars($row["scaffale"]); ?>"
                                                    data-stato="<?php echo htmlspecialchars($row["stato"]); ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-danger delete-book" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteBookModal"
                                                    data-inventario="<?php echo $row["inventario"]; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginazione inferiore -->
                    <?php echo generatePaginationControls($pagination, $search_params); ?>
                    
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Nessun libro trovato<?php echo !empty($where_clause) ? ' con i criteri di ricerca specificati' : ''; ?>.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal per la modifica del libro -->
<div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editBookModalLabel">Modifica Libro</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="manage_books.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="inventario" id="edit_inventario">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_id_edificio" class="form-label">Edificio:</label>
                            <select class="form-select" id="edit_id_edificio" name="id_edificio">
                                <option value="">-- Nessun Edificio --</option>
                                <?php foreach($edifici as $id => $nome): ?>
                                    <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nome); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_sezione" class="form-label">Sezione:*</label>
                            <input type="text" class="form-control" id="edit_sezione" name="sezione" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_collocazione" class="form-label">Collocazione:*</label>
                            <input type="text" class="form-control" id="edit_collocazione" name="collocazione" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_sequenza" class="form-label">Sequenza:*</label>
                            <input type="text" class="form-control" id="edit_sequenza" name="sequenza" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_specificazione" class="form-label">Specificazione:</label>
                        <input type="text" class="form-control" id="edit_specificazione" name="specificazione">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="edit_stanza" class="form-label">Stanza:*</label>
                            <input type="text" class="form-control" id="edit_stanza" name="stanza" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_scaffale" class="form-label">Scaffale:*</label>
                            <input type="text" class="form-control" id="edit_scaffale" name="scaffale" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="edit_stato" class="form-label">Stato:*</label>
                            <select class="form-select" id="edit_stato" name="stato" required>
                                <option value="disponibile">Disponibile</option>
                                <option value="prestito">In Prestito</option>
                                <option value="manutenzione">In Manutenzione</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary" name="update_book">Salva Modifiche</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per la conferma di eliminazione -->
<div class="modal fade" id="deleteBookModal" tabindex="-1" aria-labelledby="deleteBookModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteBookModalLabel">Conferma Eliminazione</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Sei sicuro di voler eliminare il libro con inventario <strong id="delete_book_inventario"></strong>?</p>
                <p class="text-danger">Attenzione: questa azione non può essere annullata.</p>
            </div>
            <div class="modal-footer">
                <form action="manage_books.php" method="POST">
                    <input type="hidden" name="inventario" id="delete_inventario">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger" name="delete_book">Elimina</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Script per popolare i modal
document.addEventListener('DOMContentLoaded', function() {
    // Gestione del modal di modifica
    const editButtons = document.querySelectorAll('.edit-book');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const inventario = this.getAttribute('data-inventario');
            const idEdificio = this.getAttribute('data-id-edificio');
            const sezione = this.getAttribute('data-sezione');
            const collocazione = this.getAttribute('data-collocazione');
            const sequenza = this.getAttribute('data-sequenza');
            const specificazione = this.getAttribute('data-specificazione');
            const stanza = this.getAttribute('data-stanza');
            const scaffale = this.getAttribute('data-scaffale');
            const stato = this.getAttribute('data-stato');
            
            document.getElementById('edit_inventario').value = inventario;
            document.getElementById('edit_id_edificio').value = idEdificio;
            document.getElementById('edit_sezione').value = sezione;
            document.getElementById('edit_collocazione').value = collocazione;
            document.getElementById('edit_sequenza').value = sequenza;
            document.getElementById('edit_specificazione').value = specificazione;
            document.getElementById('edit_stanza').value = stanza;
            document.getElementById('edit_scaffale').value = scaffale;
            document.getElementById('edit_stato').value = stato;
        });
    });
    
    // Gestione del modal di eliminazione
    const deleteButtons = document.querySelectorAll('.delete-book');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const inventario = this.getAttribute('data-inventario');
            
            document.getElementById('delete_inventario').value = inventario;
            document.getElementById('delete_book_inventario').textContent = inventario;
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