<?php
// Determina la pagina attiva per evidenziarla nel menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">
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
                       href="index.php">
                        <i class="bi bi-search"></i> Ricerca Libri
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'import.php') ? 'active' : ''; ?>" 
                       href="import.php">
                        <i class="bi bi-file-earmark-arrow-up"></i> Importa da CSV
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'add_book.php') ? 'active' : ''; ?>" 
                       href="add_book.php">
                        <i class="bi bi-plus-circle"></i> Aggiungi Libro
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'manage_books.php') ? 'active' : ''; ?>" 
                       href="manage_books.php">
                        <i class="bi bi-pencil-square"></i> Gestisci Libri
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'manage_buildings.php') ? 'active' : ''; ?>" 
                       href="manage_buildings.php">
                        <i class="bi bi-building"></i> Gestisci Edifici
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'admin.php') ? 'active' : ''; ?>" 
                       href="admin.php">
                        <i class="bi bi-gear"></i> Area Admin
                    </a>
                </li>
            </ul>
            
            <!-- Eventuale menu utente a destra -->
            <div class="d-flex">
                <a class="btn btn-outline-light" href="help.php">
                    <i class="bi bi-question-circle"></i> Aiuto
                </a>
            </div>
        </div>
    </div>
</nav>