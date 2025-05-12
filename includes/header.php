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
    
    <!-- CSS personalizzato -->
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" href="../img/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Includiamo la navbar -->
    <?php include 'navbar.php'; ?>
    
    <!-- Contenitore principale -->
    <div class="container py-4">