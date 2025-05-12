<?php
// Imposta il titolo della pagina
$page_title = "Importa Libri";

// Includi l'header
include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="mb-4"><i class="bi bi-file-earmark-arrow-up"></i> Importa Libri da CSV</h1>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card shadow-sm mb-4">
            <div class="card-header card-header-custom">
                <h2 class="h5 mb-0"><i class="bi bi-upload"></i> Seleziona File e Destinazione</h2>
            </div>
            <div class="card-body">
                <form action="import.php" method="POST" enctype="multipart/form-data">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Esporta il tuo foglio Google Sheet in formato CSV e caricalo qui.
                    </div>
                    
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Seleziona file CSV:</label>
                        <input type="file" class="form-control" name="csv_file" id="csv_file" accept=".csv" required>
                        <div class="form-text">Il file deve contenere intestazioni nella prima riga.</div>
                    </div>

                    <div class="mb-3">
                        <label for="id_edificio" class="form-label">Edificio di destinazione:</label>
                        <select class="form-select" name="id_edificio" id="id_edificio" required>
                            <option value="">-- Seleziona Edificio --</option>
                            <?php
                            // Includi il file di connessione al database per recuperare gli edifici
                            require_once 'includes/db_connect.php';

                            // Recupera gli edifici dal database
                            $sql_edifici = "SELECT id, nome FROM edifici ORDER BY nome ASC";
                            $result_edifici = $conn->query($sql_edifici);

                            if ($result_edifici->num_rows > 0) {
                                while($row_edificio = $result_edifici->fetch_assoc()) {
                                    echo '<option value="' . $row_edificio["id"] . '">' . htmlspecialchars($row_edificio["nome"]) . '</option>';
                                }
                            } else {
                                echo '<option value="" disabled>Nessun edificio trovato. Aggiungine uno prima.</option>';
                            }
                            
                            ?>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" name="submit_import">
                            <i class="bi bi-upload me-1"></i> Importa Dati
                        </button>
                        <a href="manage_buildings.php" class="btn btn-outline-secondary">
                            <i class="bi bi-building me-1"></i> Gestisci Edifici
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php
        // La connessione al database verrà ristabilita qui se il form viene inviato
        if (isset($_POST['submit_import'])) {
            // Includi nuovamente il file di connessione per l'elaborazione dell'importazione
            if (!isset($conn) || $conn->connect_error) {
                require_once 'includes/db_connect.php';
            }

            $target_dir = "uploads/"; // Directory temporanea per salvare il file caricato
            // Crea la directory di upload se non esiste
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $target_file = $target_dir . basename($_FILES["csv_file"]["name"]);
            $uploadOk = 1;
            $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Controlla se il file è effettivamente un CSV
            if($fileType != "csv") {
                echo "<div class='alert alert-danger'>";
                echo "<i class='bi bi-exclamation-triangle-fill me-2'></i>";
                echo "Errore: è consentito solo l'upload di file CSV.";
                echo "</div>";
                $uploadOk = 0;
            }

            // Controlla se $uploadOk è impostato a 0 da un errore
            if ($uploadOk == 0) {
                echo "<div class='alert alert-danger'>";
                echo "<i class='bi bi-exclamation-triangle-fill me-2'></i>";
                echo "Il file non è stato caricato.";
                echo "</div>";
            } else {
                if (move_uploaded_file($_FILES["csv_file"]["tmp_name"], $target_file)) {
                    echo "<div class='alert alert-success'>";
                    echo "<i class='bi bi-check-circle-fill me-2'></i>";
                    echo "Il file ". htmlspecialchars( basename( $_FILES["csv_file"]["name"])). " è stato caricato.";
                    echo "</div>";

                    // Ottieni l'ID dell'edificio selezionato dall'utente
                    $id_edificio_selezionato = $conn->real_escape_string($_POST['id_edificio']);

                    // Inizia la lettura del file CSV
                    if (($handle = fopen($target_file, "r")) !== FALSE) {
                        $row_count = 0;
                        $imported_count = 0;
                        $skipped_count = 0;
                        $errors = [];

                        // Salta la prima riga se contiene le intestazioni
                        fgetcsv($handle);

                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            $row_count++;
                            
                            // Assicurati che la riga abbia il numero atteso di colonne in base al tuo CSV
                            // Esempio basato sull'ordine delle colonne nel documento: Sezione,Collocazione,Specificazione,Sequenza,Inventario,Stanza,Scaffale
                            // Il CSV esportato dal tuo foglio Google dovrebbe rispettare questo ordine o simile.
                            
                            if (count($data) >= 7) { // Controlla che ci siano almeno le colonne principali
                                $inventario = isset($data[4]) ? trim($data[4]) : '';
                                
                                // Verifica che l'inventario non sia vuoto
                                if (empty($inventario)) {
                                    $errors[] = "Errore alla riga $row_count: Numero di inventario vuoto. Riga saltata.";
                                    $skipped_count++;
                                    continue;
                                }
                                
                                // Verifica che l'inventario non esista già nel database
                                $check_query = "SELECT inventario FROM libri WHERE inventario = '" . $conn->real_escape_string($inventario) . "'";
                                $check_result = $conn->query($check_query);
                                
                                if ($check_result->num_rows > 0) {
                                    $errors[] = "Errore alla riga $row_count: Inventario '$inventario' già esistente nel database. Riga saltata.";
                                    $skipped_count++;
                                    continue;
                                }
                                
                                // Ora procediamo con l'importazione
                                $inventario = $conn->real_escape_string($inventario);
                                $sezione = isset($data[0]) ? $conn->real_escape_string(trim($data[0])) : '';
                                $collocazione = isset($data[1]) ? $conn->real_escape_string(trim($data[1])) : '';
                                $specificazione = isset($data[2]) ? $conn->real_escape_string(trim($data[2])) : '';
                                $sequenza = isset($data[3]) ? $conn->real_escape_string(trim($data[3])) : '';
                                $stanza = isset($data[5]) ? $conn->real_escape_string(trim($data[5])) : '';
                                $scaffale = isset($data[6]) ? $conn->real_escape_string(trim($data[6])) : '';
                                
                                // Query per inserire i dati nel database
                                $sql_insert = "INSERT INTO libri (inventario, id_edificio, sezione, collocazione, sequenza, specificazione, stanza, scaffale) 
                                            VALUES ('$inventario', " . ($id_edificio_selezionato ? "'$id_edificio_selezionato'" : "NULL") . ", 
                                            '$sezione', '$collocazione', '$sequenza', '$specificazione', '$stanza', '$scaffale')";

                                try {
                                    if ($conn->query($sql_insert) === TRUE) {
                                        $imported_count++;
                                    } else {
                                        $errors[] = "Errore alla riga $row_count (Inventario: $inventario): " . $conn->error;
                                        $skipped_count++;
                                    }
                                } catch (mysqli_sql_exception $e) {
                                    $errors[] = "Errore alla riga $row_count (Inventario: $inventario): " . $e->getMessage();
                                    $skipped_count++;
                                }
                            } else {
                                $errors[] = "Errore alla riga $row_count: Numero di colonne insufficiente. Riga saltata.";
                                $skipped_count++;
                            }
                        }
                        fclose($handle); // Chiudi il file CSV

                        echo "<div class='alert alert-success'>";
                        echo "<i class='bi bi-check-circle-fill me-2'></i>";
                        echo "Importazione completata. Importati $imported_count libri su $row_count righe processate. ";
                        if ($skipped_count > 0) {
                            echo "$skipped_count righe sono state saltate.";
                        }
                        echo "</div>";

                        if (!empty($errors)) {
                            echo "<div class='alert alert-warning'>";
                            echo "<i class='bi bi-exclamation-triangle-fill me-2'></i>";
                            echo "<strong>Dettaglio errori durante l'importazione:</strong>";
                            echo "<ul class='mb-0 mt-2'>";
                            // Limita il numero di errori visualizzati per non sovraccaricare la pagina
                            $max_errors_to_show = 10;
                            $errorsToShow = array_slice($errors, 0, $max_errors_to_show);
                            foreach ($errorsToShow as $error) {
                                echo "<li>$error</li>";
                            }
                            if (count($errors) > $max_errors_to_show) {
                                echo "<li>... e altri " . (count($errors) - $max_errors_to_show) . " errori. Controlla il file di log per dettagli completi.</li>";
                            }
                            echo "</ul>";
                            echo "</div>";
                        }

                        // Opzionale: elimina il file temporaneo dopo l'importazione
                        unlink($target_file);

                    } else {
                        echo "<div class='alert alert-danger'>";
                        echo "<i class='bi bi-exclamation-triangle-fill me-2'></i>";
                        echo "Errore nell'apertura del file CSV.";
                        echo "</div>";
                    }

                } else {
                    echo "<div class='alert alert-danger'>";
                    echo "<i class='bi bi-exclamation-triangle-fill me-2'></i>";
                    echo "Errore nel caricamento del file.";
                    echo "</div>";
                }
            }
            // Chiudi la connessione al database alla fine dell'elaborazione
            $conn->close();
        }
        ?>
    </div>
</div>

<?php
// Includi il footer
include 'includes/footer.php';
?>