<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>xVault Installation Wizard</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/images/favicon.svg">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/images/favicon.svg">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
    <style>
        body {
            background-color: #F8FAFC;
            color: #1E293B;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            margin: 0;
        }

        .installer-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            width: 100%;
            max-width: 900px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .installer-header {
            background: #FFFFFF;
            padding: 24px 32px;
            border-bottom: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .installer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .installer-brand svg {
            color: var(--color-brand-red, #D32F2F);
        }

        .installer-brand h1 {
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            color: #0F172A;
        }

        .installer-brand p {
            font-size: 12px;
            color: #64748B;
            margin: 0;
        }

        /* Responsive Horizontal Step Timeline - Light Theme */
        .wizard-timeline {
            display: flex;
            background: #F8FAFC;
            padding: 16px 24px;
            border-bottom: 1px solid #E2E8F0;
            overflow-x: auto;
            scrollbar-width: thin;
        }

        .timeline-step {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 110px;
            position: relative;
            opacity: 0.5;
            transition: all 0.3s ease;
        }

        .timeline-step.active {
            opacity: 1;
        }

        .timeline-step.completed {
            opacity: 0.9;
        }

        .step-number {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #E2E8F0;
            color: #64748B;
            font-weight: 700;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .timeline-step.active .step-number {
            background: var(--color-brand-red, #D32F2F);
            color: #FFFFFF;
            box-shadow: 0 0 10px rgba(211, 47, 47, 0.3);
        }

        .timeline-step.completed .step-number {
            background: #10B981;
            color: #FFFFFF;
        }

        .step-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748B;
            white-space: nowrap;
        }

        .timeline-step.active .step-label {
            color: #0F172A;
            font-weight: 700;
        }

        .installer-body {
            padding: 32px;
            min-height: 380px;
            background: #FFFFFF;
        }

        .wizard-page {
            display: none;
        }

        .wizard-page.active {
            display: block;
        }

        .step-title {
            font-size: 18px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 8px;
        }

        .step-desc {
            font-size: 13px;
            color: #64748B;
            margin-bottom: 24px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-group-full {
            grid-column: span 2;
        }

        .installer-card label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        .installer-card input, .installer-card select {
            width: 100%;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            padding: 10px 14px;
            color: #0F172A;
            font-size: 13px;
            box-sizing: border-box;
        }

        .installer-card input:focus, .installer-card select:focus {
            outline: none;
            border-color: var(--color-brand-red, #D32F2F);
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.15);
        }

        .check-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #F8FAFC;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
            margin-bottom: 10px;
            color: #334155;
            font-size: 13px;
        }

        .badge-status {
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-pass {
            background: #D1FAE5;
            color: #065F46;
        }

        .badge-fail {
            background: #FEE2E2;
            color: #991B1B;
        }

        .warning-box {
            background: #FEF2F2;
            border: 1px solid #FCA5A5;
            border-radius: 10px;
            padding: 16px;
            margin-top: 16px;
        }

        .warning-box h4 {
            margin: 0 0 8px 0;
            color: #991B1B;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .warning-box p {
            margin: 0 0 12px 0;
            font-size: 12px;
            color: #7F1D1D;
            line-height: 1.5;
        }

        .confirm-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
        }

        .confirm-checkbox input[type="checkbox"] {
            width: auto;
            margin-top: 2px;
        }

        .confirm-checkbox span {
            font-size: 12px;
            font-weight: 600;
            color: #0F172A;
        }

        .installer-footer {
            background: #F8FAFC;
            padding: 16px 32px;
            border-top: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-install {
            background: var(--color-brand-red, #D32F2F);
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-install:hover {
            background: #B71C1C;
        }

        .btn-install:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-back {
            background: #FFFFFF;
            color: #475569;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-back:hover {
            color: #0F172A;
            border-color: #94A3B8;
        }

        .log-terminal {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            color: #0284C7;
            max-height: 140px;
            overflow-y: auto;
            margin-top: 16px;
        }

        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group-full {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>

<div class="installer-card">
    <div class="installer-header">
        <div class="installer-brand">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            <div>
                <h1>xVault Installer</h1>
                <p>Enterprise Setup Wizard</p>
            </div>
        </div>
        <div style="font-size: 11px; font-weight: 700; background: #F1F5F9; padding: 4px 10px; border-radius: 20px; color: #64748B;">
            v<?= APP_VERSION ?>
        </div>
    </div>

    <!-- Timeline Steps Header -->
    <div class="wizard-timeline">
        <div class="timeline-step active" id="ts-1">
            <div class="step-number">1</div>
            <div class="step-label">Requirements</div>
        </div>
        <div class="timeline-step" id="ts-2">
            <div class="step-number">2</div>
            <div class="step-label">Database Config</div>
        </div>
        <div class="timeline-step" id="ts-3">
            <div class="step-number">3</div>
            <div class="step-label">DB Validation</div>
        </div>
        <div class="timeline-step" id="ts-4">
            <div class="step-number">4</div>
            <div class="step-label">Super Admin</div>
        </div>
        <div class="timeline-step" id="ts-5">
            <div class="step-number">5</div>
            <div class="step-label">SMTP Config</div>
        </div>
        <div class="timeline-step" id="ts-6">
            <div class="step-number">6</div>
            <div class="step-label">SMTP Test</div>
        </div>
        <div class="timeline-step" id="ts-7">
            <div class="step-number">7</div>
            <div class="step-label">Complete</div>
        </div>
    </div>

    <div class="installer-body">

        <!-- Step 1: Requirements Check -->
        <div class="wizard-page active" id="page-1">
            <div class="step-title">Step 1: System Requirements Check</div>
            <div class="step-desc">Verifying PHP version, required extensions, and file system permissions.</div>

            <div id="requirements-list">
                <div class="check-item">
                    <span>Checking environment...</span>
                    <span class="badge-status badge-pass">Scanning...</span>
                </div>
            </div>
        </div>

        <!-- Step 2: Database Configuration -->
        <div class="wizard-page" id="page-2">
            <div class="step-title">Step 2: Database Configuration</div>
            <div class="step-desc">Enter your MySQL / MariaDB connection settings for xVault.</div>

            <div class="form-grid">
                <div>
                    <label>Database Host</label>
                    <input type="text" id="db_host" placeholder="e.g. localhost or your database server hostname">
                </div>
                <div>
                    <label>Database Port</label>
                    <input type="number" id="db_port" placeholder="e.g. 3306">
                </div>
                <div>
                    <label>Database Name</label>
                    <input type="text" id="db_name" placeholder="Enter database name">
                </div>
                <div>
                    <label>Database Username</label>
                    <input type="text" id="db_user" placeholder="Enter database username">
                </div>
                <div class="form-group-full">
                    <label>Database Password</label>
                    <input type="password" id="db_pass" placeholder="Enter database password">
                </div>
            </div>
        </div>

        <!-- Step 3: Database Authentication & Overwrite Confirmation -->
        <div class="wizard-page" id="page-3">
            <div class="step-title">Step 3: Database Authentication & Verification</div>
            <div class="step-desc">Testing database connection and checking for pre-existing tables.</div>

            <div id="db-auth-status" style="margin-bottom: 16px;">
                <div style="background: #F8FAFC; padding: 16px; border-radius: 8px; border: 1px solid #E2E8F0; font-size: 13px; color: #475569;">
                    Ready to authenticate database credentials...
                </div>
            </div>

            <div id="db-overwrite-warning" class="warning-box" style="display: none;">
                <h4>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    Existing Database Data Detected
                </h4>
                <p>This database already contains application tables or data. Continuing will overwrite the existing database schema and permanently erase existing data.</p>
                <label class="confirm-checkbox">
                    <input type="checkbox" id="overwrite_confirm_check" onchange="toggleDbContinueButton()">
                    <span>I understand that continuing may overwrite the existing application database and permanently remove its existing data.</span>
                </label>
            </div>
        </div>

        <!-- Step 4: Super Administrator Setup -->
        <div class="wizard-page" id="page-4">
            <div class="step-title">Step 4: Create Super Administrator</div>
            <div class="step-desc">Configure the initial root administrator account for xVault.</div>

            <div class="form-grid">
                <div>
                    <label>Account Username (Fixed)</label>
                    <input type="text" id="admin_username" value="admin" readonly style="background: #F1F5F9; color: #475569; font-weight: 700; cursor: not-allowed;">
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" id="admin_email" placeholder="Enter administrator email address">
                </div>
                <div>
                    <label>First Name</label>
                    <input type="text" id="admin_first_name" placeholder="Enter first name">
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" id="admin_last_name" placeholder="Enter last name">
                </div>
                <div>
                    <label>Master Password</label>
                    <input type="password" id="admin_password" placeholder="Enter a strong password">
                </div>
                <div>
                    <label>Confirm Master Password</label>
                    <input type="password" id="admin_password_confirm" placeholder="Re-enter password to confirm">
                </div>
            </div>
        </div>

        <!-- Step 5: SMTP Configuration -->
        <div class="wizard-page" id="page-5">
            <div class="step-title">Step 5: SMTP Mailer Configuration — Recommended</div>
            <div class="step-desc">Configure production mailer settings for transactional security emails and password resets. SMTP is recommended for email functionality, but optional.</div>

            <div class="form-grid">
                <div>
                    <label>SMTP Host</label>
                    <input type="text" id="smtp_host" placeholder="e.g. smtp.example.com">
                </div>
                <div>
                    <label>SMTP Port</label>
                    <input type="number" id="smtp_port" placeholder="e.g. 587">
                </div>
                <div>
                    <label>Encryption Security</label>
                    <select id="smtp_enc">
                        <option value="tls" selected>TLS / STARTTLS (Port 587)</option>
                        <option value="ssl">SSL (Port 465)</option>
                        <option value="none">None (Port 25)</option>
                    </select>
                </div>
                <div>
                    <label>SMTP Username</label>
                    <input type="text" id="smtp_user" placeholder="Enter SMTP username">
                </div>
                <div>
                    <label>SMTP Password</label>
                    <input type="password" id="smtp_pass" placeholder="Enter SMTP password">
                </div>
                <div>
                    <label>From Name</label>
                    <input type="text" id="smtp_from_name" placeholder="Enter sender name">
                </div>
                <div class="form-group-full">
                    <label>From Email Address</label>
                    <input type="email" id="smtp_from_email" placeholder="Enter sender email address">
                </div>
            </div>

            <div style="margin-top: 24px; padding: 16px; background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                <div>
                    <strong style="font-size: 13px; color: #334155; display: block;">Skip SMTP Setup for Now?</strong>
                    <span style="font-size: 12px; color: #64748B;">You can complete installation without SMTP. Email functionality will remain disabled until configured later in Settings.</span>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="skipSMTPSetup()" style="white-space: nowrap; padding: 8px 16px; font-weight: 700; background: #FFFFFF; border: 1px solid #CBD5E1; color: #475569; border-radius: 6px; cursor: pointer;">Skip SMTP Setup</button>
            </div>
        </div>




        <!-- Step 6: SMTP Authentication/Test -->
        <div class="wizard-page" id="page-6">
            <div class="step-title">Step 6: SMTP Authentication Test</div>
            <div class="step-desc">Performing socket connection and credential authentication with configured SMTP server.</div>

            <div id="smtp-test-status">
                <div style="background: #F8FAFC; padding: 16px; border-radius: 8px; border: 1px solid #E2E8F0; font-size: 13px; color: #475569;">
                    Click "Run SMTP Test" to verify email server connection...
                </div>
            </div>

            <div id="smtp-logs" class="log-terminal" style="display: none;"></div>
        </div>

        <!-- Step 7: Final Configuration & Complete -->
        <div class="wizard-page" id="page-7">
            <div class="step-title">Step 7: Installation Finalization</div>
            <div class="step-desc">Writing production configuration, creating system database tables, and generating security locks.</div>

            <div style="text-align: center; padding: 32px 0;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" style="margin-bottom: 16px;">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <h3 style="font-size: 20px; font-weight: 800; margin: 0 0 8px 0; color: #0F172A;">Ready to Install xVault</h3>
                <p style="font-size: 13px; color: #64748B; max-width: 500px; margin: 0 auto 24px auto;">
                    All requirements and configuration steps have been validated. Click "Complete Installation" to finalize xVault setup.
                </p>
            </div>
        </div>

    </div>

    <!-- Installer Action Buttons Footer -->
    <div class="installer-footer">
        <button type="button" class="btn-back" id="btn-prev" onclick="prevStep()" style="display: none;">Back</button>
        <div style="flex: 1;"></div>
        <button type="button" class="btn-install" id="btn-next" onclick="nextStep()">Continue</button>
    </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
let currentStep = 1;
let existingDbDetected = false;

document.addEventListener('DOMContentLoaded', () => {
    runRequirementsCheck();
});

function updateTimelineUI() {
    for (let i = 1; i <= 7; i++) {
        const stepEl = document.getElementById(`ts-${i}`);
        const pageEl = document.getElementById(`page-${i}`);
        if (!stepEl || !pageEl) continue;

        stepEl.classList.remove('active', 'completed');
        pageEl.classList.remove('active');

        if (i < currentStep) {
            stepEl.classList.add('completed');
            stepEl.querySelector('.step-number').innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        } else if (i === currentStep) {
            stepEl.classList.add('active');
            stepEl.querySelector('.step-number').innerText = i;
            pageEl.classList.add('active');
        } else {
            stepEl.querySelector('.step-number').innerText = i;
        }
    }

    const prevBtn = document.getElementById('btn-prev');
    const nextBtn = document.getElementById('btn-next');

    prevBtn.style.display = (currentStep > 1 && currentStep < 7) ? 'block' : 'none';

    if (currentStep === 1) {
        nextBtn.innerText = 'Continue';
    } else if (currentStep === 3 && existingDbDetected) {
        nextBtn.innerText = 'Confirm & Overwrite';
        toggleDbContinueButton();
    } else if (currentStep === 6) {
        nextBtn.innerText = 'Run SMTP Test';
    } else if (currentStep === 7) {
        nextBtn.innerText = 'Complete Installation';
        nextBtn.disabled = false;
    } else {
        nextBtn.innerText = 'Continue';
        nextBtn.disabled = false;
    }
}

async function runRequirementsCheck() {
    const list = document.getElementById('requirements-list');
    list.innerHTML = '<div class="check-item"><span>Checking system requirements...</span></div>';

    try {
        const res = await fetch('/api/install/check-requirements');
        const data = await res.json();

        if (data.requirements) {
            let html = '';
            const reqs = data.requirements;

            html += `<div class="check-item">
                <span>${reqs.php.title} (${reqs.php.current})</span>
                <span class="badge-status ${reqs.php.passed ? 'badge-pass' : 'badge-fail'}">${reqs.php.passed ? 'PASS' : 'FAIL'}</span>
            </div>`;

            html += `<div class="check-item">
                <span>${reqs.extensions.title} (PDO, OpenSSL, Mbstring, JSON)</span>
                <span class="badge-status ${reqs.extensions.passed ? 'badge-pass' : 'badge-fail'}">${reqs.extensions.passed ? 'PASS' : 'FAIL'}</span>
            </div>`;

            html += `<div class="check-item">
                <span>${reqs.permissions.title} (config.php, storage/)</span>
                <span class="badge-status ${reqs.permissions.passed ? 'badge-pass' : 'badge-fail'}">${reqs.permissions.passed ? 'PASS' : 'FAIL'}</span>
            </div>`;

            list.innerHTML = html;
            document.getElementById('btn-next').disabled = !data.success;
        }
    } catch (e) {
        list.innerHTML = `<div class="check-item"><span style="color:#EF4444;">Error validating system requirements.</span></div>`;
    }
}

async function nextStep() {
    const nextBtn = document.getElementById('btn-next');

    // Step 1 -> Step 2
    if (currentStep === 1) {
        currentStep = 2;
        updateTimelineUI();
        return;
    }

    // Step 2 -> Step 3: Test Database Credentials
    if (currentStep === 2) {
        nextBtn.disabled = true;
        nextBtn.innerText = 'Connecting...';

        const payload = {
            db_driver: 'mysql',
            db_host: document.getElementById('db_host').value.trim() || '127.0.0.1',
            db_port: document.getElementById('db_port').value.trim() || '3306',
            db_name: document.getElementById('db_name').value.trim() || 'xvault_db',
            db_user: document.getElementById('db_user').value.trim() || 'root',
            db_pass: document.getElementById('db_pass').value
        };


        try {
            const res = await fetch('/api/install/test-db', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                toast.error(data.error || 'Database connection failed');
                nextBtn.disabled = false;
                nextBtn.innerText = 'Continue';
                return;
            }

            existingDbDetected = data.existing_tables;
            const statusBox = document.getElementById('db-auth-status');
            const warnBox = document.getElementById('db-overwrite-warning');

            if (data.existing_tables) {
                statusBox.innerHTML = `<div style="background: #FEF9C3; border: 1px solid #FDE047; padding: 14px; border-radius: 8px; font-size: 13px; color: #854D0E; display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Database connection authenticated successfully. System detected <strong>${data.existing_count} existing tables</strong>.
                </div>`;
                warnBox.style.display = 'block';
            } else {
                statusBox.innerHTML = `<div style="background: #D1FAE5; border: 1px solid #6EE7B7; padding: 14px; border-radius: 8px; font-size: 13px; color: #065F46; display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> Database connection authenticated successfully. Target database is clean and ready.
                </div>`;
                warnBox.style.display = 'none';
            }

            currentStep = 3;
            updateTimelineUI();
        } catch (e) {
            toast.error('Network error while authenticating database.');
            nextBtn.disabled = false;
            nextBtn.innerText = 'Continue';
        }
        return;
    }

    // Step 3 -> Step 4
    if (currentStep === 3) {
        if (existingDbDetected && !document.getElementById('overwrite_confirm_check').checked) {
            toast.error('Explicit confirmation is required to proceed with overwriting existing data.');
            return;
        }
        currentStep = 4;
        updateTimelineUI();
        return;
    }

    // Step 4 -> Step 5: Validate Super Admin
    if (currentStep === 4) {
        nextBtn.disabled = true;
        const payload = {
            username: document.getElementById('admin_username').value.trim(),
            email: document.getElementById('admin_email').value.trim(),
            first_name: document.getElementById('admin_first_name').value.trim(),
            last_name: document.getElementById('admin_last_name').value.trim(),
            password: document.getElementById('admin_password').value,
            password_confirm: document.getElementById('admin_password_confirm').value
        };

        try {
            const res = await fetch('/api/install/validate-admin', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                toast.error(data.error || 'Admin validation failed.');
                nextBtn.disabled = false;
                return;
            }

            currentStep = 5;
            updateTimelineUI();
        } catch (e) {
            toast.error('Network error during admin validation.');
            nextBtn.disabled = false;
        }
        return;
    }

    // Step 5 -> Step 6
    if (currentStep === 5) {
        currentStep = 6;
        updateTimelineUI();
        return;
    }

    // Step 6 -> Step 7: Run SMTP Test or Proceed if Skipped
    if (currentStep === 6) {
        const statusBox = document.getElementById('smtp-test-status');
        if (statusBox && statusBox.innerText.includes('SMTP setup skipped')) {
            currentStep = 7;
            updateTimelineUI();
            return;
        }

        nextBtn.disabled = true;
        nextBtn.innerText = 'Testing SMTP Connection...';

        const payload = {
            smtp_host: document.getElementById('smtp_host').value.trim(),
            smtp_port: document.getElementById('smtp_port').value.trim(),
            smtp_enc: document.getElementById('smtp_enc').value,
            smtp_user: document.getElementById('smtp_user').value.trim(),
            smtp_pass: document.getElementById('smtp_pass').value,
            smtp_from_email: document.getElementById('smtp_from_email').value.trim(),
            smtp_from_name: document.getElementById('smtp_from_name').value.trim()
        };


        try {
            const res = await fetch('/api/install/test-smtp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            const logsTerminal = document.getElementById('smtp-logs');

            if (data.logs) {
                logsTerminal.style.display = 'block';
                logsTerminal.innerHTML = data.logs.map(l => `<div>&gt; ${escapeHtml(l)}</div>`).join('');
            }

            if (!res.ok || !data.success) {
                statusBox.innerHTML = `
                    <div style="background: #FEF2F2; border: 1px solid #FCA5A5; padding: 16px; border-radius: 8px; font-size: 13px; color: #991B1B; margin-bottom: 16px;">
                        <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            <div>
                                <strong style="font-size: 14px; color: #991B1B; display: block; margin-bottom: 4px;">SMTP Connection Test Failed</strong>
                                <div style="line-height: 1.5;">${escapeHtml(data.error || 'SMTP Test Failed')}</div>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; border-top: 1px solid #FECACA; padding-top: 12px; margin-top: 8px;">
                            <button type="button" onclick="prevStep()" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                                Edit SMTP Credentials (Step 5)
                            </button>
                            <button type="button" onclick="skipSMTPSetup()" class="btn btn-secondary btn-sm" style="padding: 6px 14px; font-size: 12px; background: #475569; border-color: #475569; color: #FFFFFF;">Skip SMTP & Continue Installation</button>
                        </div>
                    </div>
                `;
                toast.error('SMTP Connection Test Failed');
                nextBtn.disabled = false;
                nextBtn.innerText = 'Retry SMTP Test';
                return;
            }

            statusBox.innerHTML = `<div style="background: #D1FAE5; border: 1px solid #6EE7B7; padding: 14px; border-radius: 8px; font-size: 13px; color: #065F46; display: flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> SMTP server authentication test passed successfully!
            </div>`;
            toast.success('SMTP Server Authentication Passed!');

            setTimeout(() => {
                currentStep = 7;
                updateTimelineUI();
            }, 1000);

        } catch (e) {
            toast.error('Network error while testing SMTP server.');
            nextBtn.disabled = false;
            nextBtn.innerText = 'Run SMTP Test';
        }
        return;
    }

    // Step 7: Complete Installation
    if (currentStep === 7) {
        nextBtn.disabled = true;
        nextBtn.innerText = 'Finalizing Installation...';

        const payload = {
            overwrite_confirmed: existingDbDetected ? document.getElementById('overwrite_confirm_check').checked : true
        };

        try {
            const res = await fetch('/api/install/finalize', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                toast.error(data.error || 'Finalization failed.');
                nextBtn.disabled = false;
                nextBtn.innerText = 'Complete Installation';
                return;
            }

            toast.success(data.message || 'Installation Complete!');
            setTimeout(() => {
                window.location.href = data.redirect || '/login';
            }, 1500);
        } catch (e) {
            toast.error('Network error during finalization.');
            nextBtn.disabled = false;
            nextBtn.innerText = 'Complete Installation';
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        updateTimelineUI();
    }
}

function toggleDbContinueButton() {
    if (currentStep === 3 && existingDbDetected) {
        const checked = document.getElementById('overwrite_confirm_check').checked;
        document.getElementById('btn-next').disabled = !checked;
    }
}

async function skipSMTPSetup() {
    const nextBtn = document.getElementById('btn-next');
    try {
        const res = await fetch('/api/install/test-smtp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ skip: true })
        });
        const data = await res.json();
        if (data.success) {
            toast.info('SMTP Configuration skipped. Email delivery will remain disabled.');
            const statusBox = document.getElementById('smtp-test-status');
            if (statusBox) {
                statusBox.innerHTML = `<div style="background: #EFF6FF; border: 1px solid #BFDBFE; padding: 14px; border-radius: 8px; font-size: 13px; color: #1E40AF; display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg> SMTP setup skipped. Email functionality will remain disabled until configured in Security Settings.
                </div>`;
            }
            currentStep = 6;
            updateTimelineUI();
            nextBtn.innerText = 'Continue to Finalization';
            nextBtn.disabled = false;
        }
    } catch (e) {
        toast.error('Error skipping SMTP setup.');
    }
}
</script>
</body>
</html>
