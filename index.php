<?php
// Imposta il titolo della pagina
$page_title = "Ricerca Libri";

// Includi l'header
include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="mb-4"><i class="bi bi-search"></i> Ricerca Libri</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-upc-scan"></i> Cerca per Inventario</h2>
            </div>
            <div class="card-body">
                <form action="index.php" method="GET" class="search-form">
                    <div class="mb-3">
                        <label for="inventario" class="form-label">Numero Inventario:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-hash"></i></span>
                            <input type="text" class="form-control" id="inventario" name="inventario" 
                                   placeholder="Inserisci il numero di inventario" 
                                   value="<?php echo isset($_GET['inventario']) ? htmlspecialchars($_GET['inventario']) : ''; ?>">
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Cerca
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-folder"></i> Cerca per Collocazione</h2>
            </div>
            <div class="card-body">
                <form action="index.php" method="GET" class="search-form">
                    <div class="mb-3">
                        <label for="sezione" class="form-label">Sezione:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-bookmark"></i></span>
                            <input type="text" class="form-control" id="sezione" name="sezione" 
                                   placeholder="Es. N-V." 
                                   value="<?php echo isset($_GET['sezione']) ? htmlspecialchars($_GET['sezione']) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="collocazione" class="form-label">Collocazione:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-collection"></i></span>
                            <input type="text" class="form-control" id="collocazione" name="collocazione" 
                                   placeholder="Es. A." 
                                   value="<?php echo isset($_GET['collocazione']) ? htmlspecialchars($_GET['collocazione']) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="sequenza" class="form-label">Sequenza:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-sort-numeric-down"></i></span>
                            <input type="text" class="form-control" id="sequenza" name="sequenza" 
                                   placeholder="Es. 1." 
                                   value="<?php echo isset($_GET['sequenza']) ? htmlspecialchars($_GET['sequenza']) : ''; ?>">
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i> Cerca
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cestino/Carrello flottante -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
    <div class="card cart-float shadow" id="cart-float" style="display: none;">
        <div class="card-body d-flex align-items-center p-2">
            <div class="me-3 position-relative">
                <i class="bi bi-cart-fill text-primary" style="font-size: 2rem;"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-float-count">
                    0
                </span>
            </div>
            <div>
                <h5 class="card-title mb-0 fs-6">Libri nel carrello</h5>
                <a href="cart.php" class="btn btn-sm btn-primary mt-1">
                    <i class="bi bi-eye me-1"></i> Visualizza
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Includi il file di connessione al database
require_once 'includes/db_connect.php';

$results = null; // Variabile per memorizzare i risultati della query

// Controlla se è stata effettuata una ricerca per inventario
if (isset($_GET['inventario']) && $_GET['inventario'] != '') {
    $inventario = $conn->real_escape_string($_GET['inventario']); // Pulisce l'input per sicurezza

    // Query per cercare per inventario
    // Uniamo le tabelle libri ed edifici per mostrare il nome dell'edificio
    $sql = "SELECT l.*, e.nome AS nome_edificio
            FROM libri l
            LEFT JOIN edifici e ON l.id_edificio = e.id
            WHERE l.inventario = '$inventario'
            ORDER BY l.stanza ASC"; // Ordina per stanza come richiesto

    $results = $conn->query($sql);

}
// Controlla se è stata effettuata una ricerca per collocazione (richiede tutti e tre i campi)
elseif (isset($_GET['sezione']) && $_GET['sezione'] != '' &&
          isset($_GET['collocazione']) && $_GET['collocazione'] != '' &&
          isset($_GET['sequenza']) && $_GET['sequenza'] != '') {

    $sezione = $conn->real_escape_string($_GET['sezione']);
    $collocazione = $conn->real_escape_string($_GET['collocazione']);
    $sequenza = $conn->real_escape_string($_GET['sequenza']);

    // Query per cercare per combinazione sezione, collocazione, sequenza
    $sql = "SELECT l.*, e.nome AS nome_edificio
            FROM libri l
            LEFT JOIN edifici e ON l.id_edificio = e.id
            WHERE l.sezione = '$sezione'
            AND l.collocazione = '$collocazione'
            AND l.sequenza = '$sequenza'
            ORDER BY l.stanza ASC"; // Ordina per stanza come richiesto

    $results = $conn->query($sql);
}

// Visualizza i risultati se la query è stata eseguita
if ($results !== null) {
    if ($results->num_rows > 0) {
        echo '<div class="row mt-4">';
        echo '<div class="col-12">';
        echo '<div class="card shadow-sm">';
        echo '<div class="card-header card-header-custom d-flex justify-content-between align-items-center">';
        echo '<h2 class="h5 mb-0"><i class="bi bi-list-check"></i> Risultati della Ricerca</h2>';
        echo '<span class="badge bg-primary">' . $results->num_rows . ' libro/i trovato/i</span>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped table-bordered results-table">';
        echo '<thead class="table-primary">';
        echo '<tr>';
        echo '<th>Inventario</th>';
        echo '<th>Edificio</th>';
        echo '<th>Sezione</th>';
        echo '<th>Collocazione</th>';
        echo '<th>Sequenza</th>';
        echo '<th>Specificazione</th>';
        echo '<th>Stanza</th>';
        echo '<th>Scaffale</th>';
        echo '<th>Stato</th>';
        echo '<th>Azioni</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        // Itera sui risultati e li visualizza
        while($row = $results->fetch_assoc()) {
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

            echo '<tr data-inventario="' . $row["inventario"] . '">';
            echo '<td>' . $row["inventario"] . '</td>';
            // Mostra il nome dell'edificio o "Nessuno" se non associato
            echo '<td>' . ($row["nome_edificio"] ? $row["nome_edificio"] : "Nessuno") . '</td>';
            echo '<td>' . $row["sezione"] . '</td>';
            echo '<td>' . $row["collocazione"] . '</td>';
            echo '<td>' . $row["sequenza"] . '</td>';
            echo '<td>' . $row["specificazione"] . '</td>';
            echo '<td>' . $row["stanza"] . '</td>';
            echo '<td>' . $row["scaffale"] . '</td>';
            echo '<td class="' . $statoClass . '">' . ucfirst($row["stato"]) . '</td>';
            echo '<td>';
            
            // Abilita o disabilita il pulsante in base allo stato
            if ($row["stato"] === 'disponibile') {
                echo '<button class="btn btn-sm btn-success btn-add-to-cart" data-book-id="' . $row["inventario"] . '" data-bs-toggle="tooltip" title="Aggiungi al carrello">';
            } else {
                echo '<button class="btn btn-sm btn-secondary" disabled data-bs-toggle="tooltip" title="Libro non disponibile">';
            }
            
            echo '<i class="bi bi-cart-plus"></i>';
            echo '</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>'; // fine table-responsive
        echo '</div>'; // fine card-body
        echo '</div>'; // fine card
        echo '</div>'; // fine col-12
        echo '</div>'; // fine row
    } else {
        echo '<div class="alert alert-warning mt-4">';
        echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
        echo 'Nessun risultato trovato per i criteri di ricerca specificati.';
        echo '</div>';
    }
}

// Chiudi la connessione al database alla fine dello script
$conn->close();
?>

<script>
// Aggiorna il contatore e il carrello flottante quando si carica la pagina
document.addEventListener('DOMContentLoaded', function() {
    // Ottieni il carrello
    let cart = JSON.parse(localStorage.getItem('bookfind_cart') || '[]');
    
    // Aggiorna il contatore nel carrello flottante
    const cartFloatCount = document.getElementById('cart-float-count');
    const cartFloat = document.getElementById('cart-float');
    
    if (cartFloatCount && cartFloat) {
        cartFloatCount.textContent = cart.length;
        
        // Mostra il carrello flottante solo se ci sono elementi
        if (cart.length > 0) {
            cartFloat.style.display = 'block';
        } else {
            cartFloat.style.display = 'none';
        }
    }
    
    // Evidenzia le righe che hanno libri già nel carrello
    cart.forEach(bookId => {
        const row = document.querySelector(`tr[data-inventario="${bookId}"]`);
        if (row) {
            row.classList.add('table-info');
            
            // Aggiorna il pulsante
            const button = row.querySelector('.btn-add-to-cart');
            if (button) {
                button.classList.remove('btn-success');
                button.classList.add('btn-secondary');
                button.setAttribute('disabled', 'disabled');
                button.setAttribute('title', 'Già nel carrello');
                // Aggiorna l'icona
                button.innerHTML = '<i class="bi bi-check-circle"></i>';
            }
        }
    });
    
    // Abilita i tooltip
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
});
</script>

<?php
// Includi il footer
include 'includes/footer.php';
?>