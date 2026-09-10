<?php
if (!defined('XVAULT_EXEC')) die('Direct access denied');
$pageTitle = 'Login - xVault Enterprise Password Manager';
require __DIR__ . '/../header.php';
?>
<div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
    <div class="card" style="width: 100%; max-width: 440px; padding: 36px;">
        <div style="text-align: center; margin-bottom: 28px;">
            <div class="logo-container" style="justify-content: center; margin-bottom: 12px;">
                <div class="logo-icon" style="width: 42px; height: 42px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <div class="logo-text" style="font-size: 28px; display: flex; align-items: center; justify-content: center;">
                    <span class="x">x</span><span class="vault">Vault</span>
                    <span class="version-badge" style="font-size: 11px; font-weight: 700; background: rgba(211, 47, 47, 0.08); color: var(--color-brand-red); border: 1px solid rgba(211, 47, 47, 0.2); padding: 2px 7px; border-radius: 12px; margin-left: 8px;">v<?= APP_VERSION ?></span>
                </div>
            </div>
            <p id="auth-subtitle" style="font-size: 14px; color: var(--color-text-muted);">Log in to your secure enterprise vault</p>
        </div>

        <!-- Auth Tabs -->
        <div style="display: flex; border-bottom: 1px solid var(--color-border); margin-bottom: 24px;">
            <button type="button" id="tab-btn-login" onclick="switchAuthMode('login')" style="flex: 1; padding: 10px; font-weight: 700; font-size: 14px; background: none; border: none; border-bottom: 2px solid var(--color-brand-red); color: var(--color-brand-red); cursor: pointer;">Log In</button>
            <button type="button" id="tab-btn-register" onclick="switchAuthMode('register')" style="flex: 1; padding: 10px; font-weight: 700; font-size: 14px; background: none; border: none; border-bottom: 2px solid transparent; color: #64748B; cursor: pointer;">Create Account</button>
        </div>

        <!-- UNVERIFIED NOTICE BANNER -->
        <div id="unverified-banner" style="display: none; background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: left;">
            <div style="font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <span>⚠️</span> Email Verification Required
            </div>
            <p id="unverified-banner-msg" style="margin: 0 0 10px 0; color: #78350F; line-height: 1.4;">Please verify your email address before logging in.</p>
            <button type="button" id="btn-login-resend" onclick="resendFromLoginBanner()" class="btn btn-secondary btn-full" style="padding: 8px 14px; font-size: 13px; font-weight: 600; border-color: #F59E0B; color: #92400E; background: #FEF3C7;">Resend Verification Email</button>
        </div>

        <!-- LOGIN FORM -->
        <form id="form-login" onsubmit="handleLoginSubmit(event)">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" id="login-email" class="form-control" placeholder="Enter email address" required>
            </div>
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <label class="form-label" style="margin-bottom: 0;">Master Password</label>
                    <button type="button" onclick="showForgotPassword()" style="background: none; border: none; font-size: 12px; color: var(--color-brand-red); cursor: pointer;">Forgot?</button>
                </div>
                <input type="password" name="password" id="login-password" class="form-control" placeholder="Enter master password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="padding: 12px; font-size: 15px; margin-top: 8px;">Log In</button>
        </form>

        <!-- REGISTER FORM -->
        <form id="form-register" onsubmit="handleRegisterSubmit(event)" style="display: none;">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
            </div>
            <div class="form-group">
                <label class="form-label">Master Password *</label>
                <input type="password" name="password" class="form-control" placeholder="Enter a strong password (min 6 chars)" minlength="6" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="padding: 12px; font-size: 15px; margin-top: 8px;">Create Account</button>
        </form>

        <!-- FORGOT PASSWORD FORM -->
        <form id="form-forgot" onsubmit="handleForgotSubmit(event)" style="display: none;">
            <p style="font-size: 13px; color: #475569; margin-bottom: 16px;">Enter your account email to receive a password reset link.</p>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="padding: 12px; font-size: 15px; margin-top: 8px;">Send Reset Link</button>
            <button type="button" onclick="switchAuthMode('login')" class="btn btn-secondary btn-full" style="padding: 10px; margin-top: 10px;">Back to Login</button>
        </form>


    </div>
</div>

<script>
function switchAuthMode(mode) {
    document.getElementById('form-login').style.display = mode === 'login' ? 'block' : 'none';
    document.getElementById('form-register').style.display = mode === 'register' ? 'block' : 'none';
    document.getElementById('form-forgot').style.display = mode === 'forgot' ? 'block' : 'none';

    document.getElementById('tab-btn-login').style.borderBottomColor = mode === 'login' ? 'var(--color-brand-red)' : 'transparent';
    document.getElementById('tab-btn-login').style.color = mode === 'login' ? 'var(--color-brand-red)' : '#64748B';

    document.getElementById('tab-btn-register').style.borderBottomColor = mode === 'register' ? 'var(--color-brand-red)' : 'transparent';
    document.getElementById('tab-btn-register').style.color = mode === 'register' ? 'var(--color-brand-red)' : '#64748B';

    const sub = document.getElementById('auth-subtitle');
    if (mode === 'login') sub.textContent = 'Log in to your secure enterprise vault';
    else if (mode === 'register') sub.textContent = 'Create your secure vault account';
    else if (mode === 'forgot') sub.textContent = 'Reset your master password';
}

function showForgotPassword() {
    switchAuthMode('forgot');
}

let lastUnverifiedEmail = '';

async function handleLoginSubmit(e) {
    e.preventDefault();
    document.getElementById('unverified-banner').style.display = 'none';
    const data = Object.fromEntries(new FormData(e.target));
    try {
        const res = await fetch('<?= APP_URL ?>/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok && result.success) {
            toast.success('Welcome back, ' + (result.user.name || result.user.email));
            setTimeout(() => location.href = '<?= APP_URL ?>/dashboard', 500);
        } else if (result.unverified) {
            lastUnverifiedEmail = data.email;
            document.getElementById('unverified-banner-msg').textContent = result.error || 'Please verify your email address before logging in.';
            document.getElementById('unverified-banner').style.display = 'block';
            toast.error(result.error || 'Email verification required.');
        } else {
            toast.error(result.error || 'Invalid credentials');
        }
    } catch (err) {
        toast.error('Login error. Please try again.');
    }
}

async function resendFromLoginBanner() {
    const email = lastUnverifiedEmail || document.getElementById('login-email').value;
    if (!email) {
        toast.error('Please enter your email address');
        return;
    }
    const btn = document.getElementById('btn-login-resend');
    btn.disabled = true;
    btn.textContent = 'Sending...';

    try {
        const res = await fetch('<?= APP_URL ?>/api/auth/resend-verification', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        });
        const result = await res.json();
        if (res.ok && result.success) {
            toast.success(result.message || 'Verification email resent successfully!');
            btn.textContent = 'Email Sent!';
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = 'Resend Verification Email';
            }, 5000);
        } else {
            toast.error(result.error || 'Failed to resend verification email.');
            btn.disabled = false;
            btn.textContent = 'Resend Verification Email';
        }
    } catch (err) {
        toast.error('Network error. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Resend Verification Email';
    }
}

async function handleRegisterSubmit(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    try {
        const res = await fetch('<?= APP_URL ?>/api/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (res.ok && result.success) {
            toast.success(result.message);
            switchAuthMode('login');
            document.getElementById('login-email').value = data.email;
        } else {
            toast.error(result.error || 'Registration failed');
        }
    } catch (err) {
        toast.error('Registration error');
    }
}

async function handleForgotSubmit(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    try {
        const res = await fetch('<?= APP_URL ?>/api/auth/forgot-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await res.json();
        toast.info(result.message || result.error);
        switchAuthMode('login');
    } catch (err) {
        toast.error('Network error');
    }
}
</script>
<?php require __DIR__ . '/../footer.php'; ?>
