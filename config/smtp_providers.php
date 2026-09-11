<?php
/**
 * xVault Enterprise Password Manager
 * Centralized SMTP Provider Registry & Presets
 */

if (!defined('XVAULT_EXEC')) {
    define('XVAULT_EXEC', true);
}

/**
 * Get all supported SMTP provider presets
 */
function get_smtp_providers_registry() {
    return [
        'custom' => [
            'id' => 'custom',
            'name' => 'Custom / Private SMTP',
            'category' => 'Custom',
            'host' => '',
            'port' => 587,
            'encryption' => 'tls',
            'user_hint' => 'e.g. user@yourdomain.com / username',
            'pass_hint' => 'Enter SMTP password or API token',
            'guidance' => 'Verify your SMTP hostname, port, encryption method, username, password/app password, and provider security requirements.'
        ],
        'google_gmail' => [
            'id' => 'google_gmail',
            'name' => 'Google Gmail',
            'category' => 'Google',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'user_hint' => 'Full Gmail address (e.g. user@gmail.com)',
            'pass_hint' => '16-character Google App Password',
            'guidance' => '1. Turn ON 2-Step Verification at myaccount.google.com/security. 2. Generate a 16-character App Password under Security > App Passwords. 3. Enter your full Gmail address as Username. (Note: Regular account passwords are rejected by Gmail SMTP).'
        ],
        'google_workspace' => [
            'id' => 'google_workspace',
            'name' => 'Google Workspace (Business)',
            'category' => 'Google',
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
            'user_hint' => 'Full Google Workspace email address',
            'pass_hint' => 'App Password or Service Account password',
            'guidance' => '1. Ensure 2-Step Verification is enabled or App Passwords are permitted by your Workspace Administrator. 2. Generate a 16-character App Password under Google Account Security. 3. Ensure SMTP Username is your full domain email address.'
        ],
        'microsoft_outlook' => [
            'id' => 'microsoft_outlook',
            'name' => 'Microsoft Outlook / Outlook.com',
            'category' => 'Microsoft',
            'host' => 'smtp.office365.com',
            'port' => 587,
            'encryption' => 'tls',
            'user_hint' => 'Full Outlook email (e.g. user@outlook.com)',
            'pass_hint' => 'Account password or App Password',
            'guidance' => '1. Ensure Username is your complete Outlook email address. 2. If 2-Step Verification is active on your Microsoft account, generate an App Password under Account Security > Advanced security options.'
        ],
        'microsoft_365' => [
            'id' => 'microsoft_365',
            'name' => 'Microsoft 365 / Office 365',
            'category' => 'Microsoft',
            'host' => 'smtp.office365.com',
            'port' => 587,
            'encryption' => 'tls',
            'user_hint' => 'Full M365 email (e.g. user@company.com)',
            'pass_hint' => 'Account password or App Password',
            'guidance' => '1. Ensure Username is your full organization email address. 2. Verify that SMTP AUTH is enabled for this mailbox in Microsoft 365 Admin Center (Users > Active Users > Mail > Manage email apps > Authenticated SMTP). 3. If MFA is enforced, generate an App Password.'
        ],
        'microsoft_exchange' => [
            'id' => 'microsoft_exchange',
            'name' => 'Microsoft Exchange Server',
            'category' => 'Microsoft',
            'host' => 'exchange.yourdomain.com',
            'port' => 587,
            'encryption' => 'tls',
            'user_hint' => 'DOMAIN\\username or user@domain.com',
            'pass_hint' => 'Exchange account password',
            'guidance' => '1. Ensure your Exchange Server receive connector permits SMTP client submission over TLS. 2. Verify whether your Exchange server requires domain\\username or full email format.'
        ],
        'zoho' => [
            'id' => 'zoho',
            'name' => 'Zoho Mail',
            'category' => 'Zoho',
            'host' => 'smtp.zoho.com',
            'port' => 465,
            'encryption' => 'ssl',
            'user_hint' => 'Full Zoho email (e.g. user@zoho.com)',
            'pass_hint' => 'Application-Specific Password',
            'guidance' => '1. Default global host is smtp.zoho.com (Port 465 SSL). For EU use smtp.zoho.eu, for India use smtp.zoho.in. 2. If 2-Factor Authentication is enabled, generate an Application-Specific Password in Zoho Accounts > Security > App Passwords.'
        ],
        'yahoo' => [
            'id' => 'yahoo',
            'name' => 'Yahoo Mail',
            'category' => 'Yahoo',
            'host' => 'smtp.mail.yahoo.com',
            'port' => 465,
            'encryption' => 'ssl',
            'user_hint' => 'Full Yahoo email (e.g. user@yahoo.com)',
            'pass_hint' => 'Yahoo App Password',
            'guidance' => '1. Yahoo requires an App Password for third-party SMTP. Go to Yahoo Account Security > Generate app password. 2. Ensure Username is your full Yahoo email address.'
        ],
        'cpanel' => [
            'id' => 'cpanel',
            'name' => 'cPanel / WHM Mail Server',
            'category' => 'Hosting',
            'host' => 'mail.yourdomain.com',
            'port' => 465,
            'encryption' => 'ssl',
            'user_hint' => 'Full cPanel email (e.g. user@yourdomain.com)',
            'pass_hint' => 'cPanel email account password',
            'guidance' => '1. Ensure Username is your full email address (e.g. user@yourdomain.com). 2. Confirm whether your server SSL certificate covers mail.yourdomain.com or use the hostname assigned by your host. 3. Check if your hosting provider blocks outgoing port 25 or 587.'
        ],
        'hostinger' => [
            'id' => 'hostinger',
            'name' => 'Hostinger Webmail',
            'category' => 'Hosting',
            'host' => 'smtp.hostinger.com',
            'port' => 465,
            'encryption' => 'ssl',
            'user_hint' => 'Full Hostinger email address',
            'pass_hint' => 'Hostinger webmail password',
            'guidance' => '1. Hostinger SMTP host is smtp.hostinger.com (Port 465 SSL or Port 587 TLS). 2. Username must be your full Hostinger email address. 3. Verify mailbox is active in Hostinger hPanel.'
        ],
        'godaddy' => [
            'id' => 'godaddy',
            'name' => 'GoDaddy Workspace / cPanel Mail',
            'category' => 'Hosting',
            'host' => 'smtpout.secureserver.net',
            'port' => 465,
            'encryption' => 'ssl',
            'user_hint' => 'Full GoDaddy email address',
            'pass_hint' => 'GoDaddy email password',
            'guidance' => '1. GoDaddy Workspace SMTP is smtpout.secureserver.net (Port 465 SSL or Port 587 TLS). 2. If using GoDaddy Microsoft 365, select Microsoft 365 from provider options instead.'
        ]
    ];
}

/**
 * Get info for a specific provider by key
 */
function get_smtp_provider_info($providerKey) {
    $registry = get_smtp_providers_registry();
    return $registry[$providerKey] ?? $registry['custom'];
}

/**
 * Get provider-specific or generic failure guidance
 */
function get_smtp_failure_guidance($providerKey = 'custom') {
    $info = get_smtp_provider_info($providerKey);
    return $info['guidance'];
}
