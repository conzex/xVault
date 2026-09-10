<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
?>
    <footer class="footer">
        <div class="footer-inner">
            <div style="display: flex; items-center; justify-content: center; gap: 8px; font-weight: 700; font-size: 16px;">
                <span style="color: var(--color-brand-black);">x</span><span style="color: var(--color-brand-red);">Vault</span>
            </div>
            <p>© <?= date('Y') ?> Xvault. All rights reserved.</p>
            <p class="rainbow-text">
                A Conzex Global Product
            </p>
        </div>
    </footer>
</div><!-- .app-container -->

<div id="toast-container"></div>

<?php require __DIR__ . '/modals/generator.php'; ?>
<?php require __DIR__ . '/modals/add_item.php'; ?>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
