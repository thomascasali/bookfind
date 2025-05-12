<?php
// Protegge la pagina di amministrazione richiedendo l'autenticazione
session_start();

// Imposta il titolo della pagina
$page_title = "Area Amministrativa";

// Controlla se l'utente è autenticato
$is_authenticated = false;

// Controlla se l'utente ha inviato le credenziali
if (isset($_POST['login'])) {
    // Includi il file di connessione al database
    require_once 'includes/db_connect.php';
    
    // Recupera username e password dal form
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Query per verificare le credenziali
    $sql = "SELECT id, username, password, ruolo FROM utenti WHERE username = ?";
    
    // Prepara la query
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verifica la password (in un'app reale, dovresti usare password_verify)
        if (password_verify($password, $user['password'])) {
            // Credenziali corrette, imposta la sessione
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['ruolo'];
            $is_authenticated = true;
            
            // Imposta il messaggio di successo
            $login_message = "Accesso effettuato con successo!";
            $login_status = "success";
        } else {
            // Password errata
            $login_message = "Password non valida.";
            $login_status = "danger";
        }
    } else {
        // Username non trovato
        $login_message = "Username non trovato.";
        $login_status = "danger";
    }
    
    // Chiudi la connessione
    $stmt->close();
    $conn->close();
} else {
    // Controlla se l'utente è già autenticato tramite sessione
    if (isset($_SESSION['user_id'])) {
        $is_authenticated = true;
    }
}

// Gestisci il logout
if (isset($_GET['logout'])) {
    // Distruggi la sessione
    session_unset();
    session_destroy();
    
    // Reindirizza alla pagina di login
    header("Location: admin.php");
    exit();
}

// Includi l'header
include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="mb-4"><i class="bi bi-shield-lock"></i> Area Amministrativa</h1>
    </div>
</div>

<?php if (isset($login_message)): ?>
<div class="alert alert-<?php echo $login_status; ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-<?php echo $login_status === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>-fill me-2"></i>
    <?php echo $login_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (!$is_authenticated): ?>
<!-- Form di login -->
<div class="row">
    <div class="col-md-6 col-lg-4 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-key"></i> Accesso Amministratore</h2>
            </div>
            <div class="card-body">
                <form action="admin.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password:</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" name="login">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Accedi
                        </button>
                    </div>
                </form>
                
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Nota:</strong> Per accedere all'area amministrativa, utilizza le credenziali fornite. L'accesso predefinito è:<br>
                    <strong>Username:</strong> admin<br>
                    <strong>Password:</strong> admin
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Dashboard amministrativa -->
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-person-circle"></i> Profilo</h2>
            </div>
            <div class="card-body">
                <p class="card-text">
                    <strong>Utente:</strong> <?php echo $_SESSION['username']; ?><br>
                    <strong>Ruolo:</strong> <?php echo ucfirst($_SESSION['role']); ?>
                </p>
                <div class="d-grid">
                    <a href="admin.php?logout=1" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-9 mb-4">
        <div class="card shadow-sm">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-tools"></i> Funzioni Amministrative</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6 col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-journal-plus text-primary mb-3" style="font-size: 2rem;"></i>
                                <h5 class="card-title">Aggiungi Libro</h5>
                                <p class="card-text">Aggiungi un nuovo libro al catalogo della biblioteca.</p>
                                <a href="add_book.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Aggiungi
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-pencil-square text-success mb-3" style="font-size: 2rem;"></i>
                                <h5 class="card-title">Gestisci Libri</h5>
                                <p class="card-text">Modifica, aggiorna o elimina i libri esistenti.</p>
                                <a href="manage_books.php" class="btn btn-sm btn-success">
                                    <i class="bi bi-pencil me-1"></i> Gestisci
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-building text-info mb-3" style="font-size: 2rem;"></i>
                                <h5 class="card-title">Gestisci Edifici</h5>
                                <p class="card-text">Aggiungi o modifica gli edifici della biblioteca.</p>
                                <a href="manage_buildings.php" class="btn btn-sm btn-info">
                                    <i class="bi bi-building me-1"></i> Gestisci
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-arrow-up text-warning mb-3" style="font-size: 2rem;"></i>
                                <h5 class="card-title">Importa da CSV</h5>
                                <p class="card-text">Importa libri da un file CSV formattato.</p>
                                <a href="import.php" class="btn btn-sm btn-warning">
                                    <i class="bi bi-upload me-1"></i> Importa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Includi il footer
include 'includes/footer.php';
?>