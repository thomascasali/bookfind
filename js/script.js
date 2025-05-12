// Script principale per Bookfind

// Attendi che il DOM sia completamente caricato
document.addEventListener('DOMContentLoaded', function() {
    
    // Gestione del carrello dei libri
    initBookCart();
    
    // Abilita i tooltip di Bootstrap
    enableTooltips();
    
    // Gestione dei form di ricerca
    setupSearchForms();
    
    // Gestione della tabella dei risultati
    setupResultsTable();
});

/**
 * Inizializza la funzionalità del carrello dei libri
 */
function initBookCart() {
    // Ottieni tutti i pulsanti di aggiunta al carrello
    const addButtons = document.querySelectorAll('.btn-add-to-cart');
    
    // Aggiungi event listener a ciascun pulsante
    addButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Ottieni l'ID del libro dal pulsante
            const bookId = this.getAttribute('data-book-id');
            
            // Ottieni il carrello corrente o inizializzane uno nuovo
            let cart = JSON.parse(localStorage.getItem('bookfind_cart') || '[]');
            
            // Controlla se il libro è già nel carrello
            if (!cart.includes(bookId)) {
                // Aggiungi il libro al carrello
                cart.push(bookId);
                
                // Salva il carrello aggiornato
                localStorage.setItem('bookfind_cart', JSON.stringify(cart));
                
                // Aggiorna l'interfaccia utente
                updateCartUI();
                
                // Mostra un messaggio di conferma
                showToast('Libro aggiunto al carrello!', 'success');
            } else {
                showToast('Questo libro è già nel carrello', 'warning');
            }
        });
    });
    
    // Aggiorna l'interfaccia utente del carrello all'inizio
    updateCartUI();
}

/**
 * Aggiorna l'interfaccia utente del carrello
 */
function updateCartUI() {
    // Ottieni il carrello
    let cart = JSON.parse(localStorage.getItem('bookfind_cart') || '[]');
    
    // Aggiorna il contatore nel menu
    const cartCounter = document.getElementById('cart-counter');
    if (cartCounter) {
        cartCounter.textContent = cart.length;
        
        // Mostra o nascondi il contatore in base alla presenza di elementi
        if (cart.length > 0) {
            cartCounter.classList.remove('d-none');
        } else {
            cartCounter.classList.add('d-none');
        }
    }
    
    // Se siamo nella pagina del carrello, aggiorna la lista dei libri
    const cartList = document.getElementById('cart-list');
    if (cartList) {
        // Se il carrello è vuoto
        if (cart.length === 0) {
            cartList.innerHTML = '<div class="alert alert-info">Il carrello è vuoto.</div>';
            
            // Nascondi i pulsanti di azione
            const cartActions = document.getElementById('cart-actions');
            if (cartActions) {
                cartActions.classList.add('d-none');
            }
        } else {
            // Altrimenti, mostra i pulsanti di azione
            const cartActions = document.getElementById('cart-actions');
            if (cartActions) {
                cartActions.classList.remove('d-none');
            }
            
            // Carica i libri nel carrello tramite AJAX
            // Questo esempio presuppone un endpoint cart_items.php che restituisce i dettagli dei libri
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
                    // Genera l'HTML per i libri nel carrello
                    let html = '';
                    data.books.forEach(book => {
                        html += `
                        <div class="card mb-2">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">${book.inventario}</h5>
                                    <p class="card-text mb-0">
                                        <small class="text-muted">
                                            ${book.sezione} ${book.collocazione} ${book.sequenza}
                                            <span class="badge bg-info ms-2">${book.stanza} - ${book.scaffale}</span>
                                        </small>
                                    </p>
                                </div>
                                <button class="btn btn-sm btn-danger remove-from-cart" 
                                        data-book-id="${book.inventario}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        `;
                    });
                    cartList.innerHTML = html;
                    
                    // Aggiungi event listener ai pulsanti di rimozione
                    document.querySelectorAll('.remove-from-cart').forEach(button => {
                        button.addEventListener('click', function() {
                            removeFromCart(this.getAttribute('data-book-id'));
                        });
                    });
                } else {
                    cartList.innerHTML = '<div class="alert alert-warning">Errore nel caricamento dei libri.</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                cartList.innerHTML = '<div class="alert alert-danger">Errore nel caricamento dei libri.</div>';
            });
        }
    }
}

/**
 * Rimuove un libro dal carrello
 * @param {string} bookId - L'ID del libro da rimuovere
 */
function removeFromCart(bookId) {
    // Ottieni il carrello
    let cart = JSON.parse(localStorage.getItem('bookfind_cart') || '[]');
    
    // Rimuovi il libro dal carrello
    const index = cart.indexOf(bookId);
    if (index !== -1) {
        cart.splice(index, 1);
        
        // Salva il carrello aggiornato
        localStorage.setItem('bookfind_cart', JSON.stringify(cart));
        
        // Aggiorna l'interfaccia utente
        updateCartUI();
        
        // Mostra un messaggio di conferma
        showToast('Libro rimosso dal carrello', 'info');
    }
}

/**
 * Mostra un messaggio toast
 * @param {string} message - Il messaggio da mostrare
 * @param {string} type - Il tipo di messaggio (success, info, warning, danger)
 */
function showToast(message, type) {
    // Crea l'elemento toast se non esiste
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    // Genera un ID univoco per il toast
    const toastId = 'toast-' + Date.now();
    
    // Crea l'HTML del toast
    const toastHtml = `
    <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-${type} text-white">
            <strong class="me-auto">Bookfind</strong>
            <small>Adesso</small>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            ${message}
        </div>
    </div>
    `;
    
    // Aggiungi il toast al container
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Inizializza il toast con Bootstrap
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        delay: 3000
    });
    
    // Mostra il toast
    toast.show();
    
    // Rimuovi il toast dal DOM dopo che è stato nascosto
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

/**
 * Abilita i tooltip di Bootstrap
 */
function enableTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
}

/**
 * Configura i form di ricerca
 */
function setupSearchForms() {
    // Aggiungi la classe per lo stile agli input di ricerca
    document.querySelectorAll('.search-form input[type="text"]').forEach(input => {
        input.classList.add('search-input');
    });
    
    // Ottieni il form di ricerca avanzata, se esiste
    const advancedSearchForm = document.getElementById('advanced-search-form');
    
    if (advancedSearchForm) {
        // Aggiungi event listener per il form di ricerca avanzata
        advancedSearchForm.addEventListener('submit', function(e) {
            // Verifica che almeno un campo sia compilato
            let hasValue = false;
            
            this.querySelectorAll('input[type="text"], select').forEach(field => {
                if (field.value.trim() !== '') {
                    hasValue = true;
                }
            });
            
            if (!hasValue) {
                e.preventDefault();
                showToast('Inserisci almeno un criterio di ricerca', 'warning');
            }
        });
    }
}

/**
 * Configura la tabella dei risultati
 */
function setupResultsTable() {
    // Ottieni la tabella dei risultati, se esiste
    const resultsTable = document.querySelector('.results-table');
    
    if (resultsTable) {
        // Aggiungi la classe per l'hover
        resultsTable.classList.add('table-hover');
        
        // Aggiungi event listener per il click sulle righe
        resultsTable.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('click', function() {
                // Ottieni l'inventario dalla riga
                const inventario = this.cells[0].textContent.trim();
                
                // Reindirizza alla pagina di dettaglio del libro
                window.location.href = `book_detail.php?inventario=${inventario}`;
            });
        });
    }
}