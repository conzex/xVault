# xVault Enterprise Password Manager (PHP Native)

Production-grade enterprise password manager built with native PHP 8.x, PDO, HTML5, CSS3, and Vanilla JavaScript for Apache / cPanel / shared hosting environments.

## 🚀 Quick Start & Deployment

### Apache / cPanel Hosting
1. Upload all repository files to your web server document root (e.g., `public_html/`).
2. Ensure `mod_rewrite` and `mod_headers` are enabled in Apache.
3. Configure your database settings in `config.php` or via environment variables (`DB_DRIVER`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
4. Import `schema.sql` into MySQL/MariaDB (or let PHP auto-initialize SQLite at `storage/xvault.db`).

### Local Development Server
To run locally using PHP's built-in web server:
```bash
php -S 127.0.0.1:8000 index.php
```

## 🔐 Production Security Features
- **AES-256-CBC Encryption**: All vault entries encrypted at rest using OpenSSL with random IVs and 256-bit secret keys.
- **Bcrypt Password Hashing**: Master passwords hashed with `password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12])`.
- **PDO Prepared Statements**: 100% parameterized SQL queries protecting against SQL injection vulnerabilities.
- **XSS & CSRF Defense**: Output escaping via `htmlspecialchars()` and session-bound CSRF token validation.
- **Secure Sessions**: HTTP-Only, SameSite=Strict cookies with session ID regeneration on authentication.
- **Enterprise Share Center**: One-time use and expiring tokens sent via secure transactional mailer.
- **High-Entropy Password Generator**: Uses `window.crypto.getRandomValues()` for strong password creation.

## 📁 Architecture Structure
- `config.php`: Master production settings, database credentials, & encryption keys.
- `db.php`: Singleton PDO database manager supporting MySQL & SQLite.
- `helpers.php`: Security, session, encryption, and template helpers.
- `index.php`: Master URL router.
- `controllers/`: PHP action controllers (`AuthController`, `VaultController`, `FolderController`, `AddressController`, `NoteController`, `ShareController`).
- `templates/`: Modern, responsive HTML5 views and modals matching xVault visual identity (`#D32F2F` brand color).
- `assets/`: Custom stylesheet (`main.css`) and client-side interactivity (`app.js`).
- `.htaccess`: Apache URL rewriting and HTTP security header enforcement.
