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
            e.stopPropagation(); // Impedisce il bubbling dell'evento (importante per le righe cliccabili)
            
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
    
    // Se siamo nella pagina del carrello, aggiorna la lista dei libri
    const cartList = document.getElementById('cart-list');
    if (cartList) {
        // Se il carrello è vuoto
        if (cart.length === 0) {
            cartList.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-cart-x me-2"></i>
                    Il carrello è vuoto. Aggiungi i libri da raccogliere nella pagina di ricerca.
                </div>
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Vai alla ricerca
                    </a>
                </div>
            `;
            
            // Nascondi i pulsanti di azione
            const cartActions = document.getElementById('cart-actions');
            if (cartActions) {
                cartActions.classList.add('d-none');
            }
            
            // Mostra le azioni per carrello vuoto
            const emptyCartActions = document.getElementById('empty-cart-actions');
            if (emptyCartActions) {
                emptyCartActions.style.display = 'block';
            }
        } else {
            // Altrimenti, mostra i pulsanti di azione
            const cartActions = document.getElementById('cart-actions');
            if (cartActions) {
                cartActions.classList.remove('d-none');
            }
            
            // Nascondi le azioni per carrello vuoto
            const emptyCartActions = document.getElementById('empty-cart-actions');
            if (emptyCartActions) {
                emptyCartActions.style.display = 'none';
            }
            
            // Mostra un loader durante il caricamento
            cartList.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                    </div>
                    <p class="mt-2">Caricamento del carrello...</p>
                </div>
            `;
            
            // Carica i libri nel carrello tramite AJAX
            fetch('cart_items.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ bookIds: cart }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.books && data.books.length > 0) {
                    // Genera l'HTML per i libri nel carrello
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Inventario</th>
                                        <th>Collocazione</th>
                                        <th>Edificio</th>
                                        <th>Stanza</th>
                                        <th>Scaffale</th>
                                        <th>Stato</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;
                    
                    data.books.forEach(book => {
                        // Determina la classe CSS per lo stato
                        let statoClass = '';
                        switch(book.stato) {
                            case 'prestito':
                                statoClass = 'text-danger';
                                break;
                            case 'manutenzione':
                                statoClass = 'text-warning';
                                break;
                            default: // disponibile
                                statoClass = 'text-success';
                                break;
                        }
                        
                        html += `
                            <tr>
                                <td>${book.inventario}</td>
                                <td>${book.sezione} ${book.collocazione} ${book.sequenza}${book.specificazione ? ' ' + book.specificazione : ''}</td>
                                <td>${book.nome_edificio || 'Nessuno'}</td>
                                <td>${book.stanza}</td>
                                <td>${book.scaffale}</td>
                                <td class="${statoClass}">${book.stato.charAt(0).toUpperCase() + book.stato.slice(1)}</td>
                                <td>
                                    <button class="btn btn-sm btn-danger remove-from-cart" 
                                            data-book-id="${book.inventario}" title="Rimuovi dal carrello">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                    
                    cartList.innerHTML = html;
                    
                    // Aggiungi event listener ai pulsanti di rimozione
                    document.querySelectorAll('.remove-from-cart').forEach(button => {
                        button.addEventListener('click', function() {
                            removeFromCart(this.getAttribute('data-book-id'));
                        });
                    });
                } else {
                    cartList.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Errore nel caricamento dei libri o nessun libro trovato.
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                cartList.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-x-circle-fill me-2"></i>
                        Errore nel caricamento dei libri: ${error.message}
                    </div>
                `;
            });
        }
    }
    
    // Aggiorna il contatore totale dei libri
    const totalBadge = document.getElementById('total-books');
    if (totalBadge) {
        totalBadge.textContent = cart.length + ' ' + (cart.length === 1 ? 'libro' : 'libri');
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
 * Ordina il carrello per posizione (edificio, stanza, scaffale)
 */
function sortCartByLocation() {
    // Ottieni il carrello
    let cart = JSON.parse(localStorage.getItem('bookfind_cart') || '[]');
    
    if (cart.length === 0) {
        showToast('Il carrello è vuoto', 'warning');
        return;
    }
    
    // Mostra un loader durante l'ordinamento
    const cartList = document.getElementById('cart-list');
    if (cartList) {
        cartList.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Ordinamento in corso...</span>
                </div>
                <p class="mt-2">Ordinamento dei libri per posizione...</p>
            </div>
        `;
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
        if (data.success && data.books && data.books.length > 0) {
            // Ordina i libri per edificio, stanza, scaffale
            data.books.sort((a, b) => {
                // Prima per edificio (gestisce anche i valori null)
                const edificioA = a.nome_edificio || '';
                const edificioB = b.nome_edificio || '';
                if (edificioA !== edificioB) {
                    return edificioA.localeCompare(edificioB);
                }
                
                // Poi per stanza (converte in numeri se possibile)
                const stanzaA = isNaN(a.stanza) ? a.stanza : parseInt(a.stanza);
                const stanzaB = isNaN(b.stanza) ? b.stanza : parseInt(b.stanza);
                
                // Se entrambi sono numeri, confronta numericamente
                if (typeof stanzaA === 'number' && typeof stanzaB === 'number') {
                    if (stanzaA !== stanzaB) {
                        return stanzaA - stanzaB;
                    }
                } else if (stanzaA !== stanzaB) { // Altrimenti confronta come stringhe
                    return String(stanzaA).localeCompare(String(stanzaB));
                }
                
                // Infine per scaffale (converte in numeri se possibile)
                const scaffaleA = isNaN(a.scaffale) ? a.scaffale : parseInt(a.scaffale);
                const scaffaleB = isNaN(b.scaffale) ? b.scaffale : parseInt(b.scaffale);
                
                // Se entrambi sono numeri, confronta numericamente
                if (typeof scaffaleA === 'number' && typeof scaffaleB === 'number') {
                    return scaffaleA - scaffaleB;
                } else { // Altrimenti confronta come stringhe
                    return String(scaffaleA).localeCompare(String(scaffaleB));
                }
            });
            
            // Aggiorna il carrello con l'ordine nuovo
            cart = data.books.map(book => book.inventario);
            localStorage.setItem('bookfind_cart', JSON.stringify(cart));
            
            // Aggiorna l'interfaccia utente
            updateCartUI();
            
            showToast('Carrello ordinato per posizione', 'success');
        } else {
            updateCartUI(); // Ripristina l'interfaccia utente normale
            showToast('Errore nell\'ordinamento dei libri', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        updateCartUI(); // Ripristina l'interfaccia utente normale
        showToast('Errore nell\'ordinamento', 'error');
    });
}

/**
 * Stampa il contenuto del carrello
 */
function printCart() {
    // Ottieni il carrello
    let cart = JSON.parse(localStorage.getItem('bookfind_cart') || '[]');
    
    if (cart.length === 0) {
        showToast('Il carrello è vuoto', 'warning');
        return;
    }
    
    const now = new Date();
    document.getElementById('print-date').textContent = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
    
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
        if (data.success && data.books && data.books.length > 0) {
            // Popola la tabella di stampa
            const cartTableBody = document.getElementById('print-table-body');
            cartTableBody.innerHTML = '';
            
            data.books.forEach(book => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td style="border: 1px solid #ddd; padding: 8px;">${book.inventario}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${book.sezione} ${book.collocazione} ${book.sequenza}${book.specificazione ? ' ' + book.specificazione : ''}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${book.nome_edificio || 'Nessuno'}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${book.stanza}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${book.scaffale}</td>
                `;
                cartTableBody.appendChild(row);
            });
            
            // Crea una finestra di stampa
            const printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>Stampa Carrello Libri</title>');
            printWindow.document.write('<style>');
            printWindow.document.write(`
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                h1 { text-align: center; margin-bottom: 10px; }
                p { text-align: center; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background-color: #f2f2f2; text-align: left; }
                th, td { border: 1px solid #ddd; padding: 8px; }
                .footer { text-align: center; font-style: italic; color: #666; margin-top: 30px; }
                @media print {
                    button { display: none; }
                    .no-print { display: none; }
                }
            `);
            printWindow.document.write('</style></head><body>');
            printWindow.document.write(document.getElementById('print-content').innerHTML);
            printWindow.document.write(`
                <div class="no-print" style="text-align: center; margin-top: 20px;">
                    <button onclick="window.print()" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Stampa
                    </button>
                </div>
            `);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
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
 * Svuota il carrello
 */
function clearCart() {
    // Conferma con l'utente
    if (confirm('Sei sicuro di voler svuotare il carrello? Tutti i libri selezionati verranno rimossi.')) {
        localStorage.removeItem('bookfind_cart');
        updateCartUI();
        showToast('Carrello svuotato', 'info');
    }
}

/**
 * Mostra un messaggio toast
 * @param {string} message - Il messaggio da mostrare
 * @param {string} type - Il tipo di messaggio (success, info, warning, error)
 */
function showToast(message, type) {
    // Mappa i tipi di messaggio ai colori di Bootstrap
    const typeMap = {
        'success': 'success',
        'info': 'info',
        'warning': 'warning',
        'error': 'danger'
    };
    
    const bsType = typeMap[type] || 'secondary';
    
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
        <div class="toast-header bg-${bsType} text-white">
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
            row.addEventListener('click', function(e) {
                // Non procedere se è stato cliccato un pulsante all'interno della riga
                if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                    return;
                }
                
                // Ottieni l'inventario dalla riga
                const inventario = this.cells[0].textContent.trim();
                
                // In un'applicazione reale potresti reindirizzare a una pagina di dettaglio
                // window.location.href = `book_detail.php?inventario=${inventario}`;
                
                // Per ora, mostriamo un messaggio con i dettagli del libro
                showToast(`Hai selezionato il libro ${inventario}`, 'info');
            });
        });
    }
}