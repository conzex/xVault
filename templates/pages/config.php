<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Platform Configuration - xVault Enterprise Password Manager';
$currentView = 'config';

$user = require_admin();
$db = getDB();

// Fetch initial database metrics
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$totalClients = (int)$db->query("SELECT COUNT(*) FROM folders WHERE customer_email IS NOT NULL AND customer_email != ''")->fetchColumn();
$totalCredentials = (int)$db->query("SELECT COUNT(*) FROM password_entries")->fetchColumn();
$totalNotes = (int)$db->query("SELECT COUNT(*) FROM secure_notes")->fetchColumn();
$totalAddresses = (int)$db->query("SELECT COUNT(*) FROM addresses")->fetchColumn();

$now = date('Y-m-d H:i:s');
$totalLinks = (int)$db->query("SELECT COUNT(*) FROM shared_links")->fetchColumn();
$activeLinks = (int)$db->query("SELECT COUNT(*) FROM shared_links WHERE used = 0 AND (expires_at IS NULL OR expires_at > '{$now}')")->fetchColumn();
$expiredLinks = (int)$db->query("SELECT COUNT(*) FROM shared_links WHERE expires_at IS NOT NULL AND expires_at <= '{$now}' AND used = 0")->fetchColumn();
$revokedLinks = (int)$db->query("SELECT COUNT(*) FROM shared_links WHERE used = 1")->fetchColumn();

$totalEmails = (int)$db->query("SELECT COUNT(*) FROM email_logs")->fetchColumn();
$successfulEmails = (int)$db->query("SELECT COUNT(*) FROM email_logs WHERE status = 'sent'")->fetchColumn();
$failedEmails = (int)$db->query("SELECT COUNT(*) FROM email_logs WHERE status = 'failed'")->fetchColumn();

$totalSecurityEvents = (int)$db->query("SELECT COUNT(*) FROM security_logs")->fetchColumn();
$failedLogins = (int)$db->query("SELECT COUNT(*) FROM security_logs WHERE event_type = 'LOGIN_FAILED'")->fetchColumn();

require __DIR__ . '/../header.php';
?>
<div class="main-body" id="platform-config-page">
    <?php require __DIR__ . '/../sidebar.php'; ?>

    <main class="content-area">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px;">
                <div>
                    <h1 style="font-size: 24px; font-weight: 800; color: #0F172A;">Platform Configuration</h1>
                    <p style="font-size: 14px; color: #64748B; margin-top: 2px;">Centralized enterprise administration, system controls & platform metrics</p>
                </div>
                <div style="font-size: 12px; color: #64748B; background: #F1F5F9; padding: 6px 12px; border-radius: 20px; font-weight: 600;">
                    Super Administrator Mode
                </div>
            </div>

            <!-- Tabbed Navigation Header -->
            <div style="display: flex; gap: 8px; border-bottom: 1px solid #E2E8F0; margin-bottom: 24px; overflow-x: auto; padding-bottom: 2px;">
                <button type="button" class="cfg-tab-btn active" onclick="switchConfigTab('overview')" id="tab-overview" style="padding: 10px 16px; font-weight: 700; font-size: 13px; background: none; border: none; border-bottom: 2px solid #D32F2F; color: #D32F2F; cursor: pointer; white-space: nowrap;">
                    Overview & Metrics
                </button>
                <button type="button" class="cfg-tab-btn" onclick="switchConfigTab('shared-links')" id="tab-shared-links" style="padding: 10px 16px; font-weight: 600; font-size: 13px; background: none; border: none; color: #64748B; cursor: pointer; white-space: nowrap;">
                    Shared Links Management
                </button>
                <button type="button" class="cfg-tab-btn" onclick="switchConfigTab('email')" id="tab-email" style="padding: 10px 16px; font-weight: 600; font-size: 13px; background: none; border: none; color: #64748B; cursor: pointer; white-space: nowrap;">
                    Email & SMTP Configuration
                </button>
                <button type="button" class="cfg-tab-btn" onclick="switchConfigTab('users')" id="tab-users" style="padding: 10px 16px; font-weight: 600; font-size: 13px; background: none; border: none; color: #64748B; cursor: pointer; white-space: nowrap;">
                    User Management
                </button>
                <button type="button" class="cfg-tab-btn" onclick="switchConfigTab('system')" id="tab-system" style="padding: 10px 16px; font-weight: 600; font-size: 13px; background: none; border: none; color: #64748B; cursor: pointer; white-space: nowrap;">
                    System & Database
                </button>
            </div>

            <!-- Tab 1: Overview & Metrics -->
            <div class="cfg-pane" id="pane-overview">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
                    <div class="card" style="padding: 20px;">
                        <span style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Users</span>
                        <div style="font-size: 28px; font-weight: 800; color: #0F172A; margin-top: 4px;"><?= $totalUsers ?></div>
                        <span style="font-size: 11px; color: #16A34A; font-weight: 600;"><?= $activeUsers ?> Active Accounts</span>
                    </div>
                    <div class="card" style="padding: 20px;">
                        <span style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Credentials</span>
                        <div style="font-size: 28px; font-weight: 800; color: #0F172A; margin-top: 4px;"><?= $totalCredentials ?></div>
                        <span style="font-size: 11px; color: #64748B;"><?= $totalNotes ?> Notes / <?= $totalAddresses ?> Addresses</span>
                    </div>
                    <div class="card" style="padding: 20px;">
                        <span style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Shared Links</span>
                        <div style="font-size: 28px; font-weight: 800; color: #0F172A; margin-top: 4px;"><?= $totalLinks ?></div>
                        <span style="font-size: 11px; color: #2563EB; font-weight: 600;"><?= $activeLinks ?> Active / <?= $revokedLinks ?> Revoked</span>
                    </div>
                    <div class="card" style="padding: 20px;">
                        <span style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Email Delivery</span>
                        <div style="font-size: 28px; font-weight: 800; color: #0F172A; margin-top: 4px;"><?= $totalEmails ?></div>
                        <span style="font-size: 11px; color: <?= $failedEmails > 0 ? '#DC2626' : '#16A34A' ?>; font-weight: 600;"><?= $successfulEmails ?> Sent / <?= $failedEmails ?> Failed</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div class="card" style="padding: 24px;">
                        <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 16px;">Platform Summary</h3>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F1F5F9; font-size: 13px;">
                            <span style="color: #64748B;">Application URL:</span>
                            <strong style="color: #0F172A; font-family: monospace;"><?= e(get_app_url()) ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F1F5F9; font-size: 13px;">
                            <span style="color: #64748B;">Super Administrator:</span>
                            <strong style="color: #0F172A; font-family: monospace;">admin</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F1F5F9; font-size: 13px;">
                            <span style="color: #64748B;">Database Driver:</span>
                            <strong style="color: #0F172A; font-family: monospace;"><?= e(DB_DRIVER) ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; font-size: 13px;">
                            <span style="color: #64748B;">Database Schema Version:</span>
                            <strong style="color: #0F172A; font-family: monospace;"><?= e(APP_DB_VERSION) ?></strong>
                        </div>
                    </div>

                    <div class="card" style="padding: 24px;">
                        <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 16px;">Security Audit Summary</h3>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F1F5F9; font-size: 13px;">
                            <span style="color: #64748B;">Total Security Events Logged:</span>
                            <strong style="color: #0F172A;"><?= $totalSecurityEvents ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F1F5F9; font-size: 13px;">
                            <span style="color: #64748B;">Failed Login Attempts:</span>
                            <strong style="color: <?= $failedLogins > 0 ? '#DC2626' : '#16A34A' ?>;"><?= $failedLogins ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 10px 0; font-size: 13px;">
                            <span style="color: #64748B;">SMTP Transport Status:</span>
                            <span class="sidebar-badge" style="background: <?= (defined('SMTP_ENABLED') && SMTP_ENABLED) ? '#D1FAE5; color: #065F46;' : '#FEF3C7; color: #92400E;' ?>">
                                <?= (defined('SMTP_ENABLED') && SMTP_ENABLED) ? 'Enabled' : 'Disabled' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Shared Links Management -->
            <div class="cfg-pane" id="pane-shared-links" style="display: none;">
                <div class="card" style="padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div>
                            <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">Administrative Shared Links Control</h3>
                            <p style="font-size: 12px; color: #64748B; margin: 2px 0 0 0;">Monitor, audit, and revoke active secret sharing tokens. Credential secrets are never exposed.</p>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="loadSharedLinksAdmin()">Refresh Links</button>
                    </div>

                    <div style="max-height: 50vh; overflow-y: auto;" class="table-wrapper">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                            <thead>
                                <tr style="border-bottom: 1px solid #E2E8F0; color: #64748B; font-size: 11px; text-transform: uppercase;">
                                    <th style="padding: 10px;">Token Link</th>
                                    <th style="padding: 10px;">Owner</th>
                                    <th style="padding: 10px;">Recipient</th>
                                    <th style="padding: 10px;">Expires At</th>
                                    <th style="padding: 10px;">Status</th>
                                    <th style="padding: 10px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="cfg-shared-links-tbody">
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px; color: #94A3B8;">Loading shared links...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Email & SMTP Configuration -->
            <div class="cfg-pane" id="pane-email" style="display: none;">
                <div class="card" style="padding: 28px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #F1F5F9; padding-bottom: 16px;">
                        <div>
                            <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">Production SMTP Mailer & Email Statistics</h3>
                            <p style="font-size: 12px; color: #64748B; margin: 4px 0 0 0;">Configure transactional email transport and review delivery metrics.</p>
                        </div>
                        <span class="sidebar-badge" style="background: <?= (defined('SMTP_ENABLED') && SMTP_ENABLED) ? '#D1FAE5; color: #065F46;' : '#FEF3C7; color: #92400E;' ?>; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"></circle></svg>
                            <?= (defined('SMTP_ENABLED') && SMTP_ENABLED) ? 'SMTP Active' : 'SMTP Disabled' ?>
                        </span>
                    </div>

                    <!-- Email Stats Row -->
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                        <div style="background: #F8FAFC; padding: 16px; border-radius: 8px; border: 1px solid #E2E8F0;">
                            <span style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Emails Processed</span>
                            <div style="font-size: 24px; font-weight: 800; color: #0F172A; margin-top: 4px;"><?= $totalEmails ?></div>
                        </div>
                        <div style="background: #F8FAFC; padding: 16px; border-radius: 8px; border: 1px solid #E2E8F0;">
                            <span style="font-size: 11px; font-weight: 700; color: #16A34A; text-transform: uppercase;">Successful Deliveries</span>
                            <div style="font-size: 24px; font-weight: 800; color: #16A34A; margin-top: 4px;"><?= $successfulEmails ?></div>
                        </div>
                        <div style="background: #F8FAFC; padding: 16px; border-radius: 8px; border: 1px solid #E2E8F0;">
                            <span style="font-size: 11px; font-weight: 700; color: #DC2626; text-transform: uppercase;">Failed Deliveries</span>
                            <div style="font-size: 24px; font-weight: 800; color: #DC2626; margin-top: 4px;"><?= $failedEmails ?></div>
                        </div>
                    </div>

                    <form onsubmit="savePlatformSMTPSettings(event)">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; background: #F8FAFC; padding: 12px 16px; border-radius: 8px; border: 1px solid #E2E8F0;">
                            <input type="checkbox" id="cfg_smtp_enabled" <?= (defined('SMTP_ENABLED') && SMTP_ENABLED) ? 'checked' : '' ?> style="width: auto; cursor: pointer;">
                            <label for="cfg_smtp_enabled" style="font-size: 13px; font-weight: 700; color: #0F172A; margin: 0; cursor: pointer;">Enable Transactional SMTP Email Delivery</label>
                        </div>

                        <!-- 1-Click Mail Provider Presets -->
                        <div style="margin-bottom: 20px; background: #F8FAFC; padding: 16px; border-radius: 8px; border: 1px solid #E2E8F0;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 10px;">Select Mail Provider Preset:</label>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button type="button" onclick="applySmtpPreset('google')" style="padding: 8px 16px; font-size: 12px; font-weight: 700; border: 1px solid #CBD5E1; border-radius: 6px; background: #FFFFFF; color: #1E293B; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M21.35 11.1h-9.17v2.73h6.51c-.33 1.76-1.82 3.08-3.79 3.08-2.3 0-4.16-1.86-4.16-4.16s1.86-4.16 4.16-4.16c1.07 0 2.05.41 2.8 1.09l2.06-2.06C18.42 6.3 16.7 5.5 14.7 5.5 10.45 5.5 7 8.95 7 13.2s3.45 7.7 7.7 7.7c4.41 0 7.37-3.1 7.37-7.5 0-.61-.06-1.12-.14-1.6z"/></svg> Google / Gmail
                                </button>
                                <button type="button" onclick="applySmtpPreset('outlook')" style="padding: 8px 16px; font-size: 12px; font-weight: 700; border: 1px solid #CBD5E1; border-radius: 6px; background: #FFFFFF; color: #1E293B; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#0078D4"><path d="M1 17.5l7.5 2.5V4L1 6.5v11zM9.5 3v18L23 21V3L9.5 3z"/></svg> Outlook / Office 365
                                </button>
                                <button type="button" onclick="applySmtpPreset('zoho')" style="padding: 8px 16px; font-size: 12px; font-weight: 700; border: 1px solid #CBD5E1; border-radius: 6px; background: #FFFFFF; color: #1E293B; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="#D97706"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg> Zoho Mail
                                </button>
                                <button type="button" onclick="applySmtpPreset('custom')" style="padding: 8px 16px; font-size: 12px; font-weight: 700; border: 1px solid #CBD5E1; border-radius: 6px; background: #FFFFFF; color: #1E293B; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M122v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Custom / Other
                                </button>
                            </div>
                            <div id="cfg-smtp-provider-hint" style="margin-top: 12px; padding: 12px 14px; background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 6px; font-size: 12px; color: #1E40AF; line-height: 1.5; display: none;"></div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">SMTP Host</label>
                                <input type="text" id="cfg_smtp_host" value="<?= e(defined('SMTP_HOST') ? SMTP_HOST : '') ?>" placeholder="e.g. smtp.gmail.com" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">SMTP Port</label>
                                <input type="number" id="cfg_smtp_port" value="<?= e(defined('SMTP_PORT') ? SMTP_PORT : 587) ?>" placeholder="e.g. 587" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Encryption Security</label>
                                <select id="cfg_smtp_enc" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                                    <option value="tls" <?= (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'tls') ? 'selected' : '' ?>>TLS / STARTTLS (Port 587)</option>
                                    <option value="ssl" <?= (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'ssl') ? 'selected' : '' ?>>SSL (Port 465)</option>
                                    <option value="none" <?= (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'none') ? 'selected' : '' ?>>None (Port 25)</option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">SMTP Username</label>
                                <input type="text" id="cfg_smtp_user" value="<?= e(defined('SMTP_USER') ? SMTP_USER : '') ?>" placeholder="e.g. user@gmail.com / user@domain.com" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">SMTP Password / App Password</label>
                                <input type="password" id="cfg_smtp_pass" placeholder="Enter password or App Password (leave blank to keep current)" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                            </div>
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Sender Name</label>
                                <input type="text" id="cfg_smtp_from_name" value="<?= e(defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'xVault Security') ?>" placeholder="Enter sender name" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                            </div>
                            <div style="grid-column: span 2;">
                                <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">Sender Email Address</label>
                                <input type="email" id="cfg_smtp_from_email" value="<?= e(defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '') ?>" placeholder="Enter sender email address" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px;">
                            </div>
                        </div>

                        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">Send Live Test Email To</label>
                            <input type="email" id="cfg_smtp_test_email" value="<?= e($user['email']) ?>" placeholder="Enter recipient email address" style="width: 100%; border: 1px solid #CBD5E1; border-radius: 8px; padding: 9px 12px; font-size: 13px; background: #fff;">
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" id="btn-save-cfg-smtp" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">Save & Test Email Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 4: User Management -->
            <div class="cfg-pane" id="pane-users" style="display: none;">
                <div class="card" style="padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div>
                            <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin: 0;">Platform User Accounts</h3>
                            <p style="font-size: 12px; color: #64748B; margin: 2px 0 0 0;">Manage registered accounts, activation status, and administrative roles.</p>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="loadUsersAdmin()">Refresh Accounts</button>
                    </div>

                    <div style="max-height: 50vh; overflow-y: auto;" class="table-wrapper">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                            <thead>
                                <tr style="border-bottom: 1px solid #E2E8F0; color: #64748B; font-size: 11px; text-transform: uppercase;">
                                    <th style="padding: 10px;">Username</th>
                                    <th style="padding: 10px;">Full Name</th>
                                    <th style="padding: 10px;">Email</th>
                                    <th style="padding: 10px;">Role</th>
                                    <th style="padding: 10px;">Status</th>
                                    <th style="padding: 10px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="cfg-users-tbody">
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px; color: #94A3B8;">Loading user accounts...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 5: System & Database -->
            <div class="cfg-pane" id="pane-system" style="display: none;">
                <div class="card" style="padding: 28px;">
                    <h3 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 16px;">System Configuration & Status</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div style="background: #F8FAFC; padding: 16px; border-radius: 8px; border: 1px solid #E2E8F0; font-size: 13px;">
                            <strong style="color: #0F172A; display: block; margin-bottom: 8px;">Environment Details</strong>
                            <div style="margin-bottom: 6px; color: #475569;">Application Name: <strong>xVault Enterprise</strong></div>
                            <div style="margin-bottom: 6px; color: #475569;">PHP Version: <strong><?= phpversion() ?></strong></div>
                            <div style="margin-bottom: 6px; color: #475569;">Server Software: <strong><?= e($_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI Server') ?></strong></div>
                            <div style="color: #475569;">Auto-Detected Base URL: <strong><?= e(get_app_url()) ?></strong></div>
                        </div>

                        <div style="background: #F8FAFC; padding: 16px; border-radius: 8px; border: 1px solid #E2E8F0; font-size: 13px;">
                            <strong style="color: #0F172A; display: block; margin-bottom: 8px;">Database & Storage</strong>
                            <div style="margin-bottom: 6px; color: #475569;">Database Driver: <strong><?= e(DB_DRIVER) ?></strong></div>
                            <div style="margin-bottom: 6px; color: #475569;">Schema Version: <strong><?= e(APP_DB_VERSION) ?></strong></div>
                            <div style="margin-bottom: 6px; color: #475569;">Storage Logs: <strong>Writable</strong></div>
                            <div style="color: #475569;">Session Security: <strong>Strict / HTTPOnly</strong></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
function switchConfigTab(tabName) {
    document.querySelectorAll('.cfg-tab-btn').forEach(btn => {
        btn.style.borderBottom = 'none';
        btn.style.color = '#64748B';
    });
    document.querySelectorAll('.cfg-pane').forEach(pane => pane.style.display = 'none');

    const activeBtn = document.getElementById('tab-' + tabName);
    const activePane = document.getElementById('pane-' + tabName);
    if (activeBtn) {
        activeBtn.style.borderBottom = '2px solid #D32F2F';
        activeBtn.style.color = '#D32F2F';
    }
    if (activePane) activePane.style.display = 'block';

    if (tabName === 'shared-links') loadSharedLinksAdmin();
    if (tabName === 'users') loadUsersAdmin();
}

async function loadSharedLinksAdmin() {
    const tbody = document.getElementById('cfg-shared-links-tbody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #94A3B8;">Loading shared links...</td></tr>';
    try {
        const res = await fetch('<?= APP_URL ?>/api/config/shared-links');
        const data = await res.json();
        if (!data.links || data.links.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 30px; color: #94A3B8;">No shared links generated yet.</td></tr>';
            return;
        }

        tbody.innerHTML = data.links.map(l => `
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 10px; font-family: monospace; font-size: 11px; color: #2563EB;">${escapeHtml(l.token)}</td>
                <td style="padding: 10px; color: #334155;">${escapeHtml(l.owner)}</td>
                <td style="padding: 10px; color: #64748B;">${escapeHtml(l.target_email)}</td>
                <td style="padding: 10px; color: #64748B;">${escapeHtml(l.expires_at)}</td>
                <td style="padding: 10px;">
                    <span class="sidebar-badge" style="background: ${l.status === 'Active' ? '#D1FAE5; color:#065F46;' : '#FEF3C7; color:#92400E;'}">${escapeHtml(l.status)}</span>
                </td>
                <td style="padding: 10px; text-align: right;">
                    ${l.status === 'Active' ? `<button type="button" class="btn btn-secondary btn-sm" onclick="revokeSharedLinkAdmin(${l.id})" style="color: #DC2626; border-color: #FCA5A5;">Revoke</button>` : '<span style="font-size: 11px; color:#94A3B8;">N/A</span>'}
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #EF4444;">Failed to load shared links.</td></tr>';
    }
}

async function revokeSharedLinkAdmin(linkId) {
    if (!confirm('Are you sure you want to revoke this shared link? Recipient access will be blocked immediately.')) return;
    try {
        const res = await fetch('<?= APP_URL ?>/api/config/revoke-link', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: linkId })
        });
        const data = await res.json();
        if (data.success) {
            toast.success('Shared link revoked');
            loadSharedLinksAdmin();
        } else {
            toast.error(data.error || 'Failed to revoke link');
        }
    } catch (e) {
        toast.error('Network error revoking shared link');
    }
}

async function loadUsersAdmin() {
    const tbody = document.getElementById('cfg-users-tbody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #94A3B8;">Loading accounts...</td></tr>';
    try {
        const res = await fetch('<?= APP_URL ?>/api/config/users');
        const data = await res.json();
        if (!data.users || data.users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 30px; color: #94A3B8;">No user accounts found.</td></tr>';
            return;
        }

        tbody.innerHTML = data.users.map(u => `
            <tr style="border-bottom: 1px solid #F1F5F9;">
                <td style="padding: 10px; font-weight: 700; color: #0F172A;">${escapeHtml(u.username || '-')}</td>
                <td style="padding: 10px; color: #334155;">${escapeHtml(u.name || u.email)}</td>
                <td style="padding: 10px; color: #64748B;">${escapeHtml(u.email)}</td>
                <td style="padding: 10px;"><span class="sidebar-badge">${escapeHtml(u.role)}</span></td>
                <td style="padding: 10px;">
                    <span class="sidebar-badge" style="background: ${u.status === 'active' ? '#D1FAE5; color:#065F46;' : (u.status === 'pending_verification' ? '#FEF9C3; color:#854D0E;' : '#FEE2E2; color:#991B1B;')}">${escapeHtml(u.status_label || (u.status === 'pending_verification' ? 'Pending Verification' : u.status))}</span>
                </td>
                <td style="padding: 10px; text-align: right;">
                    ${u.role === 'admin' ? '<span style="font-size: 11px; color:#94A3B8;">Root Admin</span>' : `
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleUserStatusAdmin(${u.id}, '${u.status === 'active' ? 'inactive' : 'active'}')">
                            ${u.status === 'active' ? 'Deactivate' : 'Activate'}
                        </button>
                    `}
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #EF4444;">Failed to load accounts.</td></tr>';
    }
}

async function toggleUserStatusAdmin(userId, newStatus) {
    try {
        const res = await fetch('<?= APP_URL ?>/api/config/toggle-user-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, status: newStatus })
        });
        const data = await res.json();
        if (data.success) {
            toast.success(`User status changed to ${newStatus}`);
            loadUsersAdmin();
        } else {
            toast.error(data.error || 'Failed to update user status');
        }
    } catch (e) {
        toast.error('Network error updating user status');
    }
}

function applySmtpPreset(provider) {
    const hostEl = document.getElementById('cfg_smtp_host') || document.getElementById('smtp_host');
    const portEl = document.getElementById('cfg_smtp_port') || document.getElementById('smtp_port');
    const encEl = document.getElementById('cfg_smtp_enc') || document.getElementById('smtp_enc');
    const hintEl = document.getElementById('cfg-smtp-provider-hint') || document.getElementById('smtp-provider-hint');

    if (!hostEl || !portEl || !encEl) return;

    if (provider === 'google') {
        hostEl.value = 'smtp.gmail.com';
        portEl.value = 587;
        encEl.value = 'tls';
        if (hintEl) {
            hintEl.style.display = 'block';
            hintEl.innerHTML = '<strong>🔴 Google / Gmail Requirements:</strong><br>1. Turn <strong>2-Step Verification</strong> ON at <a href="https://myaccount.google.com/security" target="_blank" style="color: #2563EB; text-decoration: underline;">myaccount.google.com/security</a>.<br>2. Generate a 16-character <strong>App Password</strong> under Security > App Passwords.<br>3. Enter your full Gmail address (e.g. <code>user@gmail.com</code>) as Username.<br>4. Note: Regular Google passwords are rejected by Gmail SMTP.';
        }
    } else if (provider === 'outlook') {
        hostEl.value = 'smtp.office365.com';
        portEl.value = 587;
        encEl.value = 'tls';
        if (hintEl) {
            hintEl.style.display = 'block';
            hintEl.innerHTML = '<strong>🔵 Outlook / Office 365 Requirements:</strong><br>1. Host is set to <code>smtp.office365.com</code> (Port 587 TLS).<br>2. Username must be your full email address (e.g. <code>user@outlook.com</code> or <code>user@company.com</code>).<br>3. Ensure <strong>SMTP AUTH</strong> is enabled in M365 Admin Center for your account.<br>4. If 2FA/MFA is enabled, generate and use an App Password.';
        }
    } else if (provider === 'zoho') {
        hostEl.value = 'smtp.zoho.com';
        portEl.value = 465;
        encEl.value = 'ssl';
        if (hintEl) {
            hintEl.style.display = 'block';
            hintEl.innerHTML = '<strong>🟡 Zoho Mail Requirements:</strong><br>1. Default global host is <code>smtp.zoho.com</code> (Port 465 SSL). For EU use <code>smtp.zoho.eu</code>, for India use <code>smtp.zoho.in</code>.<br>2. If 2-Factor Authentication is active, generate an <strong>Application-Specific Password</strong> in Zoho Accounts > Security.';
        }
    } else if (provider === 'custom') {
        if (hintEl) {
            hintEl.style.display = 'block';
            hintEl.innerHTML = '<strong>⚙️ Custom / Private SMTP:</strong> Enter your custom SMTP host, port (587 TLS / 465 SSL / 25), and credentials provided by your hosting control panel (cPanel, SendGrid, Mailgun, AWS SES, Postmark, etc.).';
        }
    }
}

async function savePlatformSMTPSettings(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-cfg-smtp');
    btn.disabled = true;
    btn.innerText = 'Saving Configuration...';

    const payload = {
        smtp_enabled: document.getElementById('cfg_smtp_enabled').checked,
        smtp_host: document.getElementById('cfg_smtp_host').value.trim(),
        smtp_port: document.getElementById('cfg_smtp_port').value.trim(),
        smtp_enc: document.getElementById('cfg_smtp_enc').value,
        smtp_user: document.getElementById('cfg_smtp_user').value.trim(),
        smtp_pass: document.getElementById('cfg_smtp_pass').value,
        smtp_from_email: document.getElementById('cfg_smtp_from_email').value.trim(),
        smtp_from_name: document.getElementById('cfg_smtp_from_name').value.trim(),
        test_email: document.getElementById('cfg_smtp_test_email').value.trim()
    };

    try {
        const res = await fetch('<?= APP_URL ?>/api/config/update-settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            toast.error(data.error || 'Failed to update settings');
        } else {
            toast.success(data.message || 'Platform settings updated successfully');
            setTimeout(() => location.reload(), 1200);
        }
    } catch (err) {
        toast.error('Network error saving platform configuration');
    } finally {
        btn.disabled = false;
        btn.innerText = 'Save & Test Email Settings';
    }
}
</script>
<?php require __DIR__ . '/../footer.php'; ?>
