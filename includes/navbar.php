<?php
// Determina la pagina attiva per evidenziarla nel menu
$current_page = basename($_SERVER['PHP_SELF']);

// Definisci la funzione per ottenere l'URL base se non è ancora definita
if (!function_exists('getBaseUrl')) {
    function getBaseUrl() {
        $base_dir = dirname($_SERVER['SCRIPT_NAME']);
        $base_url = '';
        if($base_dir != '/' && $base_dir != '\\') {
            $base_url = $base_dir;
            if(substr($base_url, -1) != '/') $base_url .= '/';
        }
        return $base_url;
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?php echo getBaseUrl(); ?>index.php">
            <i class="bi bi-book"></i> Bookfind
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" 
                aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" 
                       href="<?php echo getBaseUrl(); ?>index.php">
                        <i class="bi bi-search"></i> Ricerca Libri
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>" 
                       href="<?php echo getBaseUrl(); ?>cart.php">
                        <i class="bi bi-cart"></i> Carrello
                        <span class="badge bg-light text-primary rounded-pill d-none" id="cart-counter">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'import.php') ? 'active' : ''; ?>" 
                       href="<?php echo getBaseUrl(); ?>import.php">
                        <i class="bi bi-file-earmark-arrow-up"></i> Importa da CSV
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'add_book.php') ? 'active' : ''; ?>" 
                       href="<?php echo getBaseUrl(); ?>add_book.php">
                        <i class="bi bi-plus-circle"></i> Aggiungi Libro
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'manage_books.php') ? 'active' : ''; ?>" 
                       href="<?php echo getBaseUrl(); ?>manage_books.php">
                        <i class="bi bi-pencil-square"></i> Gestisci Libri
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'manage_buildings.php') ? 'active' : ''; ?>" 
                       href="<?php echo getBaseUrl(); ?>manage_buildings.php">
                        <i class="bi bi-building"></i> Gestisci Edifici
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin.php') ? 'active' : ''; ?>" 
                       href="<?php echo getBaseUrl(); ?>admin.php">
                        <i class="bi bi-gear"></i> Area Admin
                    </a>
                </li>
            </ul>
            
            <!-- Eventuale menu utente a destra -->
            <div class="d-flex">
                <a class="btn btn-outline-light" href="<?php echo getBaseUrl(); ?>help.php">
                    <i class="bi bi-question-circle"></i> Aiuto
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Script per aggiornare il contatore del carrello -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aggiorna il contatore del carrello
    const cartCounter = document.getElementById('cart-counter');
    if (cartCounter) {
        const cart = JSON.parse(localStorage.getItem('bookfind_cart') || '[]');
        cartCounter.textContent = cart.length;
        
        // Mostra o nascondi il contatore in base alla presenza di elementi
        if (cart.length > 0) {
            cartCounter.classList.remove('d-none');
        } else {
            cartCounter.classList.add('d-none');
        }
    }
});
</script>