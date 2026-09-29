<?php
/**
 * Global Footer Component
 */
if (!defined('APP_INIT')) {
    require_once __DIR__ . '/../config/config.php';
}
?>
            </div> <!-- /.page-content -->
        </div> <!-- /.app-main -->
    </div> <!-- /.app-container -->

    <!-- Master Application Scripts -->
    <script src="<?= BASE_URL ?>/assets/js/app.js?v=<?= file_exists(ROOT_PATH . '/assets/js/app.js') ? filemtime(ROOT_PATH . '/assets/js/app.js') : time() ?>"></script>
    
    <?php if (isset($extraScripts) && is_array($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <?php 
            $scriptUrl = $script;
            if (strpos($script, '?') === false) {
                $localPath = ROOT_PATH . str_replace(BASE_URL, '', $script);
                $ver = file_exists($localPath) ? filemtime($localPath) : time();
                $scriptUrl .= '?v=' . $ver;
            }
            ?>
            <script src="<?= $scriptUrl ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
