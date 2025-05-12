<?php
// Imposta il titolo della pagina
$page_title = "Carrello Libri";

// Includi l'header
include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="mb-4"><i class="bi bi-cart"></i> Carrello Libri</h1>
        <p class="lead">Qui puoi gestire i libri che hai selezionato per il prelievo.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 col-md-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><i class="bi bi-list-check"></i> Libri Selezionati</h2>
                <span class="badge bg-primary" id="total-books">0 libri</span>
            </div>
            <div class="card-body">
                <div id="cart-list">
                    <!-- Qui verranno caricati dinamicamente i libri dal carrello -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Caricamento...</span>
                        </div>
                        <p class="mt-2">Caricamento del carrello...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-gear-fill"></i> Azioni</h2>
            </div>
            <div class="card-body">
                <div id="cart-actions" class="d-none">
                    <div class="d-grid gap-2">
                        <button id="print-cart" class="btn btn-primary">
                            <i class="bi bi-printer me-1"></i> Stampa Lista
                        </button>
                        <button id="sort-by-location" class="btn btn-success">
                            <i class="bi bi-sort-alpha-down me-1"></i> Ordina per Posizione
                        </button>
                        <button id="clear-cart" class="btn btn-danger">
                            <i class="bi bi-trash me-1"></i> Svuota Carrello
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary mt-3">
                            <i class="bi bi-plus-circle me-1"></i> Aggiungi Altri Libri
                        </a>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Suggerimento:</strong> Usa "Ordina per Posizione" per ottimizzare il percorso di raccolta dei libri.
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h3 class="h6 mb-0">Legenda Stati</h3>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Disponibile
                                <span class="badge bg-success">Prelevabile</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                In Prestito
                                <span class="badge bg-danger">Non Disponibile</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                In Manutenzione
                                <span class="badge bg-warning text-dark">Temporaneamente Non Disponibile</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div id="empty-cart-actions">
                    <div class="text-center py-4">
                        <i class="bi bi-cart-x text-muted" style="font-size: 4rem;"></i>
                        <p class="mt-3">Il carrello è vuoto.</p>
                        <a href="index.php" class="btn btn-primary mt-2">
                            <i class="bi bi-search me-1"></i> Inizia a cercare libri
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- La pagina per la stampa -->
<div id="print-content" style="display: none;">
    <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1>Lista Libri da Prelevare</h1>
            <p>Data: <span id="print-date"></span></p>
        </div>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Inventario</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Collocazione</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Edificio</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Stanza</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Scaffale</th>
                </tr>
            </thead>
            <tbody id="print-table-body">
                <!-- Qui verranno inseriti i libri -->
            </tbody>
        </table>
        
        <div style="text-align: center; font-style: italic; color: #666;">
            <p>Documento generato da Bookfind - Sistema di Gestione Biblioteca</p>
        </div>
    </div>
</div>

<?php
// Includi il footer
include 'includes/footer.php';
?>

<!-- Script specifico per la pagina del carrello -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aggiorna contatore totale libri
    function updateTotalCounter() {
        const cart = JSON.parse(localStorage.getItem('bookfind_cart') || '[]');
        const totalBadge = document.getElementById('total-books');
        if (totalBadge) {
            totalBadge.textContent = cart.length + ' ' + (cart.length === 1 ? 'libro' : 'libri');
        }
        
        // Mostra/nascondi azioni per carrello vuoto
        const emptyCartActions = document.getElementById('empty-cart-actions');
        if (emptyCartActions) {
            if (cart.length === 0) {
                emptyCartActions.style.display = 'block';
            } else {
                emptyCartActions.style.display = 'none';
            }
        }
    }
    
    // Carica il contenuto del carrello
    updateCartUI();
    updateTotalCounter();
    
    // Gestione del pulsante di stampa
    document.getElementById('print-cart').addEventListener('click', function() {
        printCart();
    });
    
    // Gestione del pulsante di ordinamento
    document.getElementById('sort-by-location').addEventListener('click', function() {
        sortCartByLocation();
        updateTotalCounter();
    });
    
    // Gestione del pulsante di svuotamento
    document.getElementById('clear-cart').addEventListener('click', function() {
        clearCart();
        updateTotalCounter();
    });
    
    // Aggiungi event delegation per i pulsanti di rimozione che verranno aggiunti dinamicamente
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-from-cart') || e.target.closest('.remove-from-cart')) {
            const button = e.target.classList.contains('remove-from-cart') ? e.target : e.target.closest('.remove-from-cart');
            const bookId = button.getAttribute('data-book-id');
            removeFromCart(bookId);
            updateTotalCounter();
        }
    });
});
</script>