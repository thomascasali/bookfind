<?php
// Imposta il titolo della pagina
$page_title = "Carrello Libri";

// Includi l'header
include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="mb-4"><i class="bi bi-cart"></i> Carrello Libri</h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 col-md-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-list-check"></i> Libri Selezionati</h2>
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
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Suggerimento:</strong> Usa "Ordina per Posizione" per ottimizzare il percorso di raccolta dei libri.
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
    // Carica il contenuto del carrello
    updateCartUI();
    
    // Gestione del pulsante di stampa
    document.getElementById('print-cart').addEventListener('click', function() {
        printCart();
    });
    
    // Gestione del pulsante di ordinamento
    document.getElementById('sort-by-location').addEventListener('click', function() {
        sortCartByLocation();
    });
    
    // Gestione del pulsante di svuotamento
    document.getElementById('clear-cart').addEventListener('click', function() {
        if (confirm('Sei sicuro di voler svuotare il carrello?')) {
            clearCart();
        }
    });
});

/**
 * Stampa il contenuto del carrello
 */
function printCart() {
    const now = new Date();
    document.getElementById('print-date').textContent = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
    
    // Ottieni i dati dei libri dal carrello
    const cartTableBody = document.getElementById('print-table-body');
    cartTableBody.innerHTML = '';
    
    // Ottieni il carrello
    let cart = JSON.parse(localStorage.getItem('bookfind_cart') || '[]');
    
    // Recupera i dettagli dei libri tramite AJAX
    fetch('cart_items.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ bookIds: cart }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.books && data.books.length > 0) {
            // Popola la tabella di stampa
            data.books.forEach(book => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td style="border: 1px solid #ddd; padding: 8px;">${book.inventario}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${book.sezione} ${book.collocazione} ${book.sequenza}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${book.nome_edificio || 'Nessuno'}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${book.stanza}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${book.scaffale}</td>
                `;
                cartTableBody.appendChild(row);
            });
            
            // Crea una finestra di stampa
            const printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>Stampa Carrello Libri</title></head><body>');
            printWindow.document.write(document.getElementById('print-content').innerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            
            // Attendi il caricamento del contenuto
            printWindow.onload = function() {
                printWindow.print();
                printWindow.close();
            };
        } else {
            showToast('Errore nel caricamento dei libri per la stampa', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore nella stampa', 'error');
    });
}

/**
 * Ordina il carrello per posizione (edificio, stanza, scaffale)
 */
function sortCartByLocation() {
    // Ottieni il carrello
    let cart = JSON.parse(localStorage.getItem('bookfind_cart') || '[]');
    
    if (cart.length === 0) {
        showToast('Il carrello è vuoto', 'warning');
        return;
    }
    
    // Recupera i dettagli dei libri tramite AJAX
    fetch('cart_items.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ bookIds: cart }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.books && data.books.length > 0) {
            // Ordina i libri per edificio, stanza, scaffale
            data.books.sort((a, b) => {
                // Prima per edificio
                if (a.id_edificio !== b.id_edificio) {
                    return (a.id_edificio || '').localeCompare(b.id_edificio || '');
                }
                
                // Poi per stanza
                if (a.stanza !== b.stanza) {
                    return a.stanza.localeCompare(b.stanza);
                }
                
                // Infine per scaffale
                return a.scaffale.localeCompare(b.scaffale);
            });
            
            // Aggiorna il carrello con l'ordine nuovo
            cart = data.books.map(book => book.inventario);
            localStorage.setItem('bookfind_cart', JSON.stringify(cart));
            
            // Aggiorna l'interfaccia utente
            updateCartUI();
            
            showToast('Carrello ordinato per posizione', 'success');
        } else {
            showToast('Errore nell\'ordinamento dei libri', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Errore nell\'ordinamento', 'error');
    });
}

/**
 * Svuota il carrello
 */
function clearCart() {
    localStorage.removeItem('bookfind_cart');
    updateCartUI();
    showToast('Carrello svuotato', 'info');
}
</script>