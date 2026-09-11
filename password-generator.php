<?php
define('XVAULT_EXEC', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = 'Free Password Generator - xVault Enterprise';
require __DIR__ . '/templates/header.php';
?>
<div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
    <div class="card" style="width: 100%; max-width: 540px; padding: 36px; background: #FFFFFF; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.06);">
        <div style="text-align: center; margin-bottom: 24px;">
            <div class="logo-container" style="justify-content: center; margin-bottom: 12px;">
                <div class="logo-icon" style="width: 44px; height: 44px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <div class="logo-text" style="font-size: 28px; display: flex; align-items: center;">
                    <span class="x">x</span><span class="vault">Vault</span>
                    <span class="version-badge" style="font-size: 11px; font-weight: 700; background: rgba(211, 47, 47, 0.08); color: var(--color-brand-red); border: 1px solid rgba(211, 47, 47, 0.2); padding: 2px 7px; border-radius: 12px; margin-left: 8px;">v<?= APP_VERSION ?></span>
                </div>
            </div>
            <h2 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0 0 4px 0;">Strong Password Generator</h2>
            <p style="font-size: 13px; color: #64748B; margin: 0;">Generate 100% client-side cryptographically secure passwords. Zero server logging.</p>
        </div>

        <!-- Generated Password Display Box -->
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 24px; position: relative;">
            <div id="pub-pass-output" class="font-mono" style="font-size: 20px; font-weight: 700; color: #0F172A; letter-spacing: 1.5px; word-break: break-all; min-height: 30px; display: flex; align-items: center; justify-content: center;">
                Loading...
            </div>

            <!-- Strength Indicator Bar -->
            <div style="margin-top: 16px;">
                <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; margin-bottom: 4px;">
                    <span style="color: #64748B;">Password Strength</span>
                    <span id="pub-strength-label" style="color: #22C55E;">Strong</span>
                </div>
                <div style="width: 100%; height: 6px; background: #E2E8F0; border-radius: 3px; overflow: hidden;">
                    <div id="pub-strength-meter" style="width: 100%; height: 100%; background: #22C55E; transition: all 0.3s ease;"></div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 12px; margin-bottom: 28px;">
            <button type="button" id="pub-refresh-btn" class="btn btn-secondary" style="flex: 1; padding: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"></path></svg>
                Regenerate
            </button>
            <button type="button" id="pub-copy-btn" class="btn btn-primary" style="flex: 1; padding: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                Copy Password
            </button>
        </div>

        <!-- Customization Controls -->
        <div style="display: grid; gap: 16px; border-top: 1px solid #F1F5F9; padding-top: 24px;">
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <label style="font-size: 13px; font-weight: 700; color: #334155;">Password Length: <span id="pub-length-val" style="color: var(--color-brand-red);">16</span> characters</label>
                </div>
                <input type="range" id="pub-length" min="8" max="64" value="16" style="width: 100%; accent-color: var(--color-brand-red); cursor: pointer;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="pub-uppercase" checked style="accent-color: var(--color-brand-red);">
                    Uppercase (A-Z)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="pub-lowercase" checked style="accent-color: var(--color-brand-red);">
                    Lowercase (a-z)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="pub-numbers" checked style="accent-color: var(--color-brand-red);">
                    Numbers (0-9)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="pub-symbols" checked style="accent-color: var(--color-brand-red);">
                    Special (!@#$)
                </label>
            </div>
        </div>

        <div style="margin-top: 24px; text-align: center; border-top: 1px solid #F1F5F9; padding-top: 16px;">
            <?php if (current_user()): ?>
                <a href="<?= APP_URL ?>/dashboard" class="btn btn-secondary btn-sm">Go to Vault Dashboard</a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/login" style="font-size: 13px; color: var(--color-brand-red); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                    Sign in to save passwords in your vault
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const lengthInput = document.getElementById('pub-length');
    const lengthVal = document.getElementById('pub-length-val');
    const passOutput = document.getElementById('pub-pass-output');
    const refreshBtn = document.getElementById('pub-refresh-btn');
    const copyBtn = document.getElementById('pub-copy-btn');
    const optUpper = document.getElementById('pub-uppercase');
    const optLower = document.getElementById('pub-lowercase');
    const optNum = document.getElementById('pub-numbers');
    const optSym = document.getElementById('pub-symbols');
    const strengthMeter = document.getElementById('pub-strength-meter');
    const strengthLabel = document.getElementById('pub-strength-label');

    function generate() {
        const length = parseInt(lengthInput.value, 10) || 16;
        if (lengthVal) lengthVal.textContent = length;

        let charset = '';
        if (optUpper && optUpper.checked) charset += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if (optLower && optLower.checked) charset += 'abcdefghijklmnopqrstuvwxyz';
        if (optNum && optNum.checked) charset += '0123456789';
        if (optSym && optSym.checked) charset += '!@#$%^&*()_+~`|}{[]:;?><,./-=';

        if (!charset) {
            passOutput.textContent = 'Select at least one option';
            if (strengthMeter) strengthMeter.style.width = '0%';
            if (strengthLabel) strengthLabel.textContent = 'None';
            return;
        }

        const array = new Uint32Array(length);
        window.crypto.getRandomValues(array);

        let result = '';
        for (let i = 0; i < length; i++) {
            result += charset[array[i] % charset.length];
        }

        passOutput.textContent = result;
        updateStrength(result);
    }

    function updateStrength(pass) {
        let score = 0;
        if (pass.length >= 10) score += 25;
        if (pass.length >= 16) score += 25;
        if (/[A-Z]/.test(pass)) score += 15;
        if (/[a-z]/.test(pass)) score += 15;
        if (/[0-9]/.test(pass)) score += 10;
        if (/[^A-Za-z0-9]/.test(pass)) score += 10;

        let label = 'Weak';
        let color = '#EF4444';

        if (score >= 80) {
            label = 'Strong';
            color = '#22C55E';
        } else if (score >= 60) {
            label = 'Good';
            color = '#3B82F6';
        } else if (score >= 40) {
            label = 'Fair';
            color = '#F59E0B';
        }

        if (strengthMeter) {
            strengthMeter.style.width = score + '%';
            strengthMeter.style.backgroundColor = color;
        }
        if (strengthLabel) {
            strengthLabel.textContent = label;
            strengthLabel.style.color = color;
        }
    }

    if (lengthInput) lengthInput.addEventListener('input', generate);
    [optUpper, optLower, optNum, optSym].forEach(opt => {
        if (opt) opt.addEventListener('change', generate);
    });

    if (refreshBtn) refreshBtn.addEventListener('click', generate);

    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const pass = passOutput ? passOutput.textContent : '';
            if (pass && pass !== 'Select at least one option') {
                copyToClipboard(pass, 'Generated password copied!');
            }
        });
    }

    generate();
});
</script>
<?php require __DIR__ . '/templates/footer.php'; ?>
