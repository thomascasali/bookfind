<!DOCTYPE html>
<html>
<head>
    <title>Area Amministrativa - Bookfind</title>
    <style>
        body { font-family: sans-serif; }
        .admin-menu a { margin-right: 15px; }
        .error { color: red; }
    </style>
</head>
<body>

    <h1>Area Amministrativa</h1>

    <?php
    // --- MECCANISMO DI ACCESSO AMMINISTRATIVO MOLTO SEMPLICE ---
    // NOTA BENE: QUESTO È UN ESEMPIO ESTREMAMENTE SEMPLICE E NON SICURO PER UN AMBIENTE DI PRODUZIONE.
    // Per un'applicazione reale, dovresti implementare un sistema di login robusto con password criptate e gestione sessioni.

    $isAdmin = false; // Flag per verificare se l'utente è amministratore

    // Esempio: Verifica una password passata via URL (ESTREMAMENTE INSECURE - SOLO PER TEST LOCALE)
    // In un'applicazione reale, useresti una sessione dopo un login verificato.
    if (isset($_GET['password']) && $_GET['password'] === 'mia_password_segreta') { // CAMBIA 'mia_password_segreta' con una password temporanea
        $isAdmin = true;
    }

    // --- FINE MECCANISMO SEMPLICE ---

    if ($isAdmin) {
        // L'utente è amministratore, mostra le opzioni
        echo "<p>Benvenuto nell'area amministrativa.</p>";
        echo "<div class='admin-menu'>";
        echo "<a href='add_book.php'>Aggiungi Nuovo Libro</a>";
        echo "<a href='manage_books.php'>Gestisci Libri Esistenti (Modifica/Elimina)</a>";
        // Potremmo aggiungere qui link per gestire edifici, utenti admin, ecc. in futuro
        echo "</div>";

    } else {
        // L'utente NON è amministratore, nega l'accesso
        echo "<p class='error'>Accesso negato. Non hai i permessi per visualizzare questa pagina.</p>";
        // In un'applicazione reale, potresti mostrare un modulo di login qui.
    }
    ?>

    <p><a href="index.php">Torna alla Ricerca</a></p>

</body>
</html>