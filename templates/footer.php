<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
?>
    <footer class="footer">
        <div class="footer-inner">
            <p>© <?= date('Y') ?> xVault v<?= APP_VERSION ?> Enterprise. All rights reserved.</p>
            <a href="https://www.conzex.com" target="_blank" rel="noopener noreferrer" class="rainbow-text">
                A Conzex Global Product
            </a>
        </div>
    </footer>
</div><!-- .app-container -->

<div id="toast-container"></div>

<?php require __DIR__ . '/modals/generator.php'; ?>
<?php require __DIR__ . '/modals/add_item.php'; ?>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
