<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
?>
<div class="modal-backdrop" id="generator-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Generate High-Entropy Password</h3>
            <button type="button" class="modal-close" data-close-modal>&times;</button>
        </div>
        <div class="modal-body">
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 16px; border-radius: 10px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <span id="gen-output" class="font-mono" style="font-size: 16px; font-weight: 600; color: #0F172A; word-break: break-all;">----------------</span>
                <button type="button" id="gen-refresh" class="btn btn-secondary btn-sm" title="Refresh">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                </button>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <label class="form-label" style="margin-bottom: 0;">Length</label>
                    <span id="gen-length-val" style="font-size: 13px; font-weight: 700; color: var(--color-brand-red);">16</span>
                </div>
                <input type="range" id="gen-length" min="8" max="64" value="16" style="width: 100%; accent-color: var(--color-brand-red);">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" id="gen-uppercase" checked style="accent-color: var(--color-brand-red);"> Uppercase (A-Z)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" id="gen-lowercase" checked style="accent-color: var(--color-brand-red);"> Lowercase (a-z)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" id="gen-numbers" checked style="accent-color: var(--color-brand-red);"> Numbers (0-9)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" id="gen-symbols" checked style="accent-color: var(--color-brand-red);"> Symbols (!@#$)
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-close-modal>Cancel</button>
            <button type="button" id="gen-copy" class="btn btn-primary">Copy & Close</button>
        </div>
    </div>
</div>
