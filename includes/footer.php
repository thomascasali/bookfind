</div><!-- fine contenitore principale -->
    
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">© <?php echo date('Y'); ?> Bookfind - Biblioteca Digitale</span>
        </div>
    </footer>
    
    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script personalizzato -->
    <script src="<?php echo getBaseUrl(); ?>js/script.js"></script>
    
    <?php
    // Se la funzione getBaseUrl non è definita, la definiamo qui
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
</body>
</html>