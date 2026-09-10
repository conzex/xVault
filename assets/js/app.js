/**
 * xVault Enterprise Password Manager
 * Frontend Interactivity Engine (Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    initPasswordGenerator();
    initModals();
    initCopyButtons();
    initPasswordVisibilityToggles();
});

/* Toast Notification Utility */
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
        toast.innerHTML = `<span>${escapeHtml(message)}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    },
    success(msg) { this.show(msg, 'success'); },
    error(msg) { this.show(msg, 'error'); },
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

/* Password Generator */
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
            return;
        }

        const array = new Uint32Array(length);
        window.crypto.getRandomValues(array);

        let result = '';
        for (let i = 0; i < length; i++) {
            result += charset[array[i] % charset.length];
        }

        passOutput.textContent = result;
    }

    if (lengthInput) {
        lengthInput.addEventListener('input', generate);
    }

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

/* Modal Helpers */
function initModals() {
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const modalId = e.target.closest('.modal-backdrop').id;
            closeModal(modalId);
        });
    });

    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                closeModal(backdrop.id);
            }
        });
    });
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('open');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('open');
    }
}

/* Copy Buttons Event Delegation */
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
        const res = await fetch(`/api/vault/decrypt/${itemId}`, {
            headers: { 'Accept': 'application/json' }
        });
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
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN || ''
            }
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

/* Delete Item AJAX handler */
async function deleteVaultItem(itemId, itemType = 'vault') {
    if (!confirm('Are you sure you want to delete this item?')) return;

    try {
        const res = await fetch(`/api/${itemType}/${itemId}`, {
            method: 'DELETE',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN || ''
            }
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

function initPasswordVisibilityToggles() {
    // Optional global hooks
}
