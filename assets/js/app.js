/**
 * xVault Enterprise Password Manager
 * Frontend Interactivity & Security Engine (Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initPasswordGenerator();
    initModals();
    initCopyButtons();
    initSecurityPolling();
});

/* Centralized Bottom-Right Toast Notification System */
window.toast = {
    show(message, type = 'info') {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span>${escapeHtml(message)}</span>
            <button type="button" class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        // Stack newest on top
        container.insertBefore(toast, container.firstChild);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(100%)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }
        }, 4000);
    },
    success(msg) { this.show(msg, 'success'); },
    error(msg) { this.show(msg, 'error'); },
    warning(msg) { this.show(msg, 'warning'); },
    info(msg) { this.show(msg, 'info'); }
};

function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/* Copy to Clipboard Utility */
function copyToClipboard(text, successMsg = 'Copied to clipboard') {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        toast.success(successMsg);
    }).catch(err => {
        toast.error('Failed to copy');
    });
}

/* Password Generator & Live Strength Meter */
function initPasswordGenerator() {
    const genModal = document.getElementById('generator-modal');
    if (!genModal) return;

    const lengthInput = document.getElementById('gen-length');
    const lengthVal = document.getElementById('gen-length-val');
    const passOutput = document.getElementById('gen-output');
    const refreshBtn = document.getElementById('gen-refresh');
    const copyBtn = document.getElementById('gen-copy');
    const optUpper = document.getElementById('gen-uppercase');
    const optLower = document.getElementById('gen-lowercase');
    const optNum = document.getElementById('gen-numbers');
    const optSym = document.getElementById('gen-symbols');
    const strengthMeter = document.getElementById('gen-strength-meter');
    const strengthLabel = document.getElementById('gen-strength-label');

    function generate() {
        if (!lengthInput || !passOutput) return;

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
        if (!strengthMeter || !strengthLabel) return;
        
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

        strengthMeter.style.width = score + '%';
        strengthMeter.style.backgroundColor = color;
        strengthLabel.textContent = label;
        strengthLabel.style.color = color;
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
                copyToClipboard(pass, 'Generated password copied');
                closeModal('generator-modal');
            }
        });
    }

    window.openPasswordGenerator = function() {
        openModal('generator-modal');
        generate();
    };
}

/* Modal Controls */
function initModals() {
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const backdrop = e.target.closest('.modal-backdrop');
            if (backdrop) closeModal(backdrop.id);
        });
    });

    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) closeModal(backdrop.id);
        });
    });
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('open');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('open');
}

/* Copy Buttons */
function initCopyButtons() {
    document.addEventListener('click', (e) => {
        const copyBtn = e.target.closest('[data-copy]');
        if (copyBtn) {
            const text = copyBtn.getAttribute('data-copy');
            const msg = copyBtn.getAttribute('data-copy-msg') || 'Copied to clipboard';
            copyToClipboard(text, msg);
        }
    });
}

/* Decrypt Password AJAX handler */
async function decryptAndShowPassword(itemId, textElementId) {
    const el = document.getElementById(textElementId);
    if (!el) return;

    if (el.dataset.decrypted) {
        el.textContent = '••••••••';
        delete el.dataset.decrypted;
        return;
    }

    try {
        const res = await fetch(`/api/vault/decrypt/${itemId}`);
        const data = await res.json();
        if (data.success && data.password) {
            el.textContent = data.password;
            el.dataset.decrypted = 'true';
        } else {
            toast.error(data.error || 'Failed to decrypt password');
        }
    } catch (err) {
        toast.error('Network error during decryption');
    }
}

/* Toggle Favorite AJAX handler */
async function toggleFavoriteItem(itemId, btnElement) {
    try {
        const res = await fetch(`/api/vault/favorite/${itemId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            if (data.is_favorite) {
                btnElement.classList.add('active');
                toast.success('Added to favorites');
            } else {
                btnElement.classList.remove('active');
                toast.info('Removed from favorites');
            }
        }
    } catch (err) {
        toast.error('Failed to update favorite status');
    }
}

/* Delete Vault Item AJAX handler */
async function deleteVaultItem(itemId, itemType = 'vault') {
    if (!confirm('Are you sure you want to delete this item?')) return;

    try {
        const res = await fetch(`/api/${itemType}/${itemId}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            toast.success('Item deleted');
            const row = document.getElementById(`item-row-${itemId}`);
            if (row) row.remove();
            else location.reload();
        } else {
            toast.error(data.error || 'Delete failed');
        }
    } catch (err) {
        toast.error('Network error');
    }
}

/* Real-Time Security Dashboard Polling (every 15 seconds) */
function initSecurityPolling() {
    const secDash = document.getElementById('sec-dashboard-page');
    if (!secDash) return;

    fetchSecurityDashboardData();
    setInterval(fetchSecurityDashboardData, 15000);
}

async function fetchSecurityDashboardData() {
    try {
        const [statsRes, logsRes] = await Promise.all([
            fetch('/api/security/stats'),
            fetch('/api/security/logs')
        ]);

        if (statsRes.ok) {
            const stats = await statsRes.json();
            const scoreEl = document.getElementById('sec-score-val');
            const weakEl = document.getElementById('sec-weak-val');
            const reusedEl = document.getElementById('sec-reused-val');
            const totalEl = document.getElementById('sec-total-val');
            const updatedEl = document.getElementById('sec-last-updated');

            if (scoreEl) scoreEl.textContent = stats.score + '%';
            if (weakEl) weakEl.textContent = stats.weak_passwords;
            if (reusedEl) reusedEl.textContent = stats.reused_passwords;
            if (totalEl) totalEl.textContent = stats.total_passwords;
            if (updatedEl) updatedEl.textContent = 'Updated at ' + stats.last_updated;
        }

        if (logsRes.ok) {
            const logsData = await logsRes.json();
            const tbody = document.getElementById('sec-logs-tbody');
            if (tbody && logsData.logs) {
                if (logsData.logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 24px; color: #94A3B8;">No security events logged yet.</td></tr>';
                } else {
                    tbody.innerHTML = logsData.logs.map(log => `
                        <tr>
                            <td><span class="sidebar-badge" style="font-weight: 700;">${escapeHtml(log.event_type)}</span></td>
                            <td style="font-size: 13px; color: #334155;">${escapeHtml(log.details || '-')}</td>
                            <td style="font-size: 12px; font-family: monospace; color: #64748B;">${escapeHtml(log.ip_address || '-')}</td>
                            <td style="font-size: 12px; color: #94A3B8;">${escapeHtml(log.time_ago)}</td>
                        </tr>
                    `).join('');
                }
            }
        }
    } catch (err) {
        console.error('Security poll error:', err);
    }
}
