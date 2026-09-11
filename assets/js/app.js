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
            <button type="button" class="toast-close" onclick="this.parentElement.remove()" aria-label="Close notification"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
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
        const res = await fetch(`${window.APP_URL || ''}/api/vault/decrypt/${itemId}`);
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
        const res = await fetch(`${window.APP_URL || ''}/api/vault/favorite/${itemId}`, {
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

/* Centralized Lightbox / Modal Overlay Notification System */
window.CustomModal = {
    confirm(options = {}) {
        return new Promise((resolve) => {
            const title = options.title || 'Confirm Action';
            const message = options.message || 'Are you sure you want to proceed?';
            const confirmText = options.confirmText || 'Confirm';
            const cancelText = options.cancelText || 'Cancel';
            const isDanger = options.isDanger !== false;

            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop open custom-modal-backdrop';
            backdrop.style.zIndex = '99999';

            backdrop.innerHTML = `
                <div class="modal" style="max-width: 440px; border-radius: 12px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: ${isDanger ? '#FEF2F2' : '#EFF6FF'}; color: ${isDanger ? '#DC2626' : '#2563EB'}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            ${isDanger ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>' : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'}
                        </div>
                        <h3 style="margin: 0; font-size: 17px; font-weight: 700; color: #0F172A;">${escapeHtml(title)}</h3>
                    </div>
                    <p style="margin: 0 0 20px 0; font-size: 14px; color: #475569; line-height: 1.5;">${escapeHtml(message)}</p>
                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary custom-modal-cancel" style="padding: 8px 16px; font-size: 13px;">${escapeHtml(cancelText)}</button>
                        <button type="button" class="btn btn-primary custom-modal-confirm" style="padding: 8px 18px; font-size: 13px; ${isDanger ? 'background: #DC2626; border-color: #DC2626;' : ''}">${escapeHtml(confirmText)}</button>
                    </div>
                </div>
            `;

            document.body.appendChild(backdrop);

            const btnCancel = backdrop.querySelector('.custom-modal-cancel');
            const btnConfirm = backdrop.querySelector('.custom-modal-confirm');

            function cleanup(result) {
                backdrop.classList.remove('open');
                setTimeout(() => backdrop.remove(), 200);
                resolve(result);
                if (result && typeof options.onConfirm === 'function') {
                    options.onConfirm();
                }
            }

            btnCancel.addEventListener('click', () => cleanup(false));
            btnConfirm.addEventListener('click', () => cleanup(true));
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) cleanup(false);
            });
        });
    },

    alert(options = {}) {
        return new Promise((resolve) => {
            const title = typeof options === 'string' ? 'Notice' : (options.title || 'Notice');
            const message = typeof options === 'string' ? options : (options.message || '');
            const okText = options.okText || 'OK';

            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop open custom-modal-backdrop';
            backdrop.style.zIndex = '99999';

            backdrop.innerHTML = `
                <div class="modal" style="max-width: 420px; border-radius: 12px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15); text-align: center;">
                    <h3 style="margin: 0 0 10px 0; font-size: 17px; font-weight: 700; color: #0F172A;">${escapeHtml(title)}</h3>
                    <p style="margin: 0 0 20px 0; font-size: 14px; color: #475569; line-height: 1.5;">${escapeHtml(message)}</p>
                    <button type="button" class="btn btn-primary custom-modal-ok" style="padding: 8px 24px; font-size: 13px; min-width: 100px;">${escapeHtml(okText)}</button>
                </div>
            `;

            document.body.appendChild(backdrop);
            const btnOk = backdrop.querySelector('.custom-modal-ok');

            function cleanup() {
                backdrop.classList.remove('open');
                setTimeout(() => backdrop.remove(), 200);
                resolve(true);
            }

            btnOk.addEventListener('click', cleanup);
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) cleanup();
            });
        });
    }
};

/* Override native alert for full consistency */
window.nativeAlert = window.alert;
window.alert = function(msg) { CustomModal.alert({ title: 'Notice', message: msg }); };

/* Delete Vault Item AJAX handler */
async function deleteVaultItem(itemId, itemType = 'vault') {
    const confirmed = await CustomModal.confirm({
        title: 'Delete Item',
        message: 'Are you sure you want to permanently delete this item? This action cannot be undone.',
        confirmText: 'Delete Item',
        isDanger: true
    });
    if (!confirmed) return;

    try {
        const res = await fetch(`${window.APP_URL || ''}/api/${itemType}/${itemId}`, {
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
            fetch(`${window.APP_URL || ''}/api/security/stats`),
            fetch(`${window.APP_URL || ''}/api/security/logs`)
        ]);

        if (statsRes.ok) {
            const stats = await statsRes.json();
            const scoreEl = document.getElementById('sec-score-val');
            const scoreMsgEl = document.getElementById('sec-score-msg');
            const gaugeCircleEl = document.getElementById('sec-gauge-circle');
            const totalEl = document.getElementById('sec-total-val');
            const strongEl = document.getElementById('sec-strong-val');
            const weakEl = document.getElementById('sec-weak-val');
            const reusedEl = document.getElementById('sec-reused-val');
            const oldEl = document.getElementById('sec-old-val');
            const attentionEl = document.getElementById('sec-attention-val');
            const updatedEl = document.getElementById('sec-last-updated');

            if (scoreEl) {
                scoreEl.textContent = stats.has_data ? (stats.score + '%') : (stats.score_text || 'N/A');
                scoreEl.style.fontSize = stats.has_data ? '36px' : '22px';
            }
            if (scoreMsgEl) scoreMsgEl.textContent = stats.score_message || '';
            if (gaugeCircleEl) {
                const color = stats.has_data ? (stats.score >= 80 ? '#22C55E' : (stats.score >= 60 ? '#3B82F6' : '#EF4444')) : '#94A3B8';
                gaugeCircleEl.style.borderColor = color;
            }
            if (totalEl) totalEl.textContent = stats.total_passwords;
            if (strongEl) strongEl.textContent = stats.strong_passwords;
            if (weakEl) weakEl.textContent = stats.weak_passwords;
            if (reusedEl) reusedEl.textContent = stats.reused_passwords;
            if (oldEl) oldEl.textContent = stats.old_passwords;
            if (attentionEl) attentionEl.textContent = stats.attention_passwords;
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

/* Individual Item Share Modal Handler */
window.openShareItemModal = function(entryId, appName) {
    let modal = document.getElementById('share-item-modal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'share-item-modal';
        modal.className = 'modal-backdrop';
        modal.innerHTML = `
            <div class="modal" style="max-width: 480px; border-radius: 12px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #E2E8F0; padding-bottom: 12px;">
                    <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #0F172A;" id="share-modal-title">Share Credential</h3>
                    <button type="button" class="toast-close" onclick="closeModal('share-item-modal')" aria-label="Close modal" style="display: flex; align-items: center; justify-content: center;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                </div>
                <input type="hidden" id="share-modal-entry-id" value="" />
                
                <div id="share-modal-form-view">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" style="font-size: 13px; font-weight: 600; color: #334155;">Expiration Time</label>
                        <select id="share-modal-expiry" class="form-control" style="font-size: 13px;">
                            <option value="1h">1 Hour</option>
                            <option value="24h" selected>24 Hours</option>
                            <option value="7d">7 Days</option>
                            <option value="30d">30 Days</option>
                            <option value="lifetime">Lifetime (No Expiry)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" style="font-size: 13px; font-weight: 600; color: #334155;">Access Limitation</label>
                        <select id="share-modal-max-uses" class="form-control" style="font-size: 13px;">
                            <option value="0" selected>Unlimited views (until expiry)</option>
                            <option value="1">One-time view only (1 view)</option>
                            <option value="5">Max 5 views</option>
                            <option value="10">Max 10 views</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label" style="font-size: 13px; font-weight: 600; color: #334155;">Recipient Email (Optional)</label>
                        <input type="email" id="share-modal-email" class="form-control" placeholder="e.g. recipient@example.com" style="font-size: 13px;" />
                        <span style="font-size: 11px; color: #64748B; margin-top: 4px; display: block;">If provided, an email with the link will be dispatched automatically.</span>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('share-item-modal')" style="font-size: 13px;">Cancel</button>
                        <button type="button" class="btn btn-primary" id="btn-generate-share-link" onclick="submitShareItemModal()" style="font-size: 13px;">Generate Share Link</button>
                    </div>
                </div>

                <div id="share-modal-result-view" style="display: none; text-align: center; padding-top: 10px;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: #DCFCE7; color: #16A34A; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                    <h4 style="font-size: 16px; font-weight: 700; color: #0F172A; margin: 0 0 6px 0;">Share Link Ready</h4>
                    <p style="font-size: 13px; color: #64748B; margin-bottom: 16px;">Anyone with this link can view only this specific credential before expiry.</p>
                    
                    <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                        <input type="text" id="share-modal-result-url" class="form-control" readonly style="font-size: 12px; font-family: monospace;" />
                        <button type="button" class="btn btn-primary" onclick="copyToClipboard(document.getElementById('share-modal-result-url').value, 'Share URL copied to clipboard')" style="white-space: nowrap; font-size: 12px;">Copy Link</button>
                    </div>

                    <button type="button" class="btn btn-secondary" onclick="closeModal('share-item-modal')" style="width: 100%; font-size: 13px;">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    document.getElementById('share-modal-entry-id').value = entryId;
    document.getElementById('share-modal-title').textContent = 'Share ' + (appName || 'Credential');
    document.getElementById('share-modal-form-view').style.display = 'block';
    document.getElementById('share-modal-result-view').style.display = 'none';
    document.getElementById('share-modal-email').value = '';
    
    openModal('share-item-modal');
};

window.submitShareItemModal = async function() {
    const entryId = document.getElementById('share-modal-entry-id').value;
    const expiry = document.getElementById('share-modal-expiry').value;
    const maxUses = parseInt(document.getElementById('share-modal-max-uses').value, 10);
    const email = document.getElementById('share-modal-email').value.trim();
    const btn = document.getElementById('btn-generate-share-link');

    btn.disabled = true;
    btn.textContent = 'Generating...';

    try {
        const res = await fetch(`${window.APP_URL || ''}/api/share/generate`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                entry_id: entryId,
                expiry: expiry,
                max_uses: maxUses,
                one_time: maxUses === 1 ? 1 : 0,
                email: email
            })
        });
        const data = await res.json();
        if (data.success && data.url) {
            document.getElementById('share-modal-result-url').value = data.url;
            document.getElementById('share-modal-form-view').style.display = 'none';
            document.getElementById('share-modal-result-view').style.display = 'block';
            toast.success('Share link generated!');
        } else {
            toast.error(data.error || 'Failed to generate share link');
        }
    } catch (err) {
        toast.error('Network error during link generation');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Generate Share Link';
    }
};

