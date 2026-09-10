<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
?>
<div class="modal-backdrop" id="generator-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Cryptographic Password Generator</h3>
            <button type="button" class="modal-close" data-close-modal aria-label="Close modal">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>
        <div class="modal-body">
            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 16px; border-radius: 10px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <span id="gen-output" class="font-mono" style="font-size: 16px; font-weight: 700; color: #0F172A; word-break: break-all;">----------------</span>
                <button type="button" id="gen-refresh" class="btn btn-secondary btn-sm" title="Regenerate Password">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                </button>
            </div>

            <!-- Password Strength Bar -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; font-size: 12px; font-weight: 600;">
                    <span style="color: #64748B;">Password Strength</span>
                    <span id="gen-strength-label" style="font-weight: 700; color: #22C55E;">Strong</span>
                </div>
                <div style="width: 100%; height: 6px; background: #E2E8F0; border-radius: 3px; overflow: hidden;">
                    <div id="gen-strength-meter" style="width: 100%; height: 100%; background: #22C55E; transition: all 0.3s ease;"></div>
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <label class="form-label" style="margin-bottom: 0;">Password Length</label>
                    <span id="gen-length-val" style="font-size: 13px; font-weight: 800; color: var(--color-brand-red);">16</span>
                </div>
                <input type="range" id="gen-length" min="8" max="64" value="16" style="width: 100%; accent-color: var(--color-brand-red);">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="gen-uppercase" checked style="accent-color: var(--color-brand-red);"> Uppercase (A-Z)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="gen-lowercase" checked style="accent-color: var(--color-brand-red);"> Lowercase (a-z)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="gen-numbers" checked style="accent-color: var(--color-brand-red);"> Numbers (0-9)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
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
