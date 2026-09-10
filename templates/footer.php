<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
?>
    <footer class="footer">
        <div class="footer-inner">
            <p>© <?= date('Y') ?> Xvault. All rights reserved.</p>
            <a href="https://www.conzex.com" target="_blank" rel="noopener noreferrer" class="rainbow-text">
                A Conzex Global Product
            </a>
        </div>
    </footer>
</div><!-- .app-container -->

<?php if (current_user()): ?>
<div class="fab-container">
    <button type="button" class="fab-btn" onclick="openPasswordGenerator()" title="Generate High-Entropy Password">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
        <span>Generate Password</span>
    </button>
</div>
<?php endif; ?>

<div id="toast-container"></div>

<?php require __DIR__ . '/modals/generator.php'; ?>
<?php require __DIR__ . '/modals/add_item.php'; ?>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
