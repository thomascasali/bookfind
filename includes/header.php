<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Bookfind' : 'Bookfind'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <?php
    // Funzione per ottenere l'URL base dinamicamente - dichiarata solo se non esiste già
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
    
    <!-- CSS personalizzato -->
    <link rel="stylesheet" href="<?php echo getBaseUrl(); ?>css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo getBaseUrl(); ?>img/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Includiamo la navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <!-- Contenitore principale -->
    <div class="container py-4">