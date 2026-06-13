# xVault Enterprise Password Manager

Production-grade password manager with enterprise sharing capabilities.

## 🚀 Quick Start

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Setup Environment**
   Copy `.env.example` to `.env` and fill in your credentials.
   ```bash
   cp .env.example .env
   ```

3. **Run Development Server**
   ```bash
   npm run dev
   ```

## 🔐 Security Features
- **AES-256-CBC Encryption**: All passwords are encrypted at rest using a 32-byte secret key.
- **JWT Authentication**: Secure session management with HTTP-only cookies.
- **Enterprise Sharing**: Expiring, one-time use links sent via secure SMTP.
- **Password Generator**: Uses `crypto.getRandomValues` for high-entropy password generation.

## 📁 Project Structure
- `client/`: React + Tailwind 4 frontend.
- `server/`: Express + Better-SQLite3 backend.
- `vault.db`: SQLite database (auto-created and seeded).

## 👤 Default Credentials
- **Admin**: `admin` / `admin`
- **Demo User**: `customer@example.com` / `password123`

## 🛠️ Production Deployment
1. Build the frontend: `npm run build`
2. Set `NODE_ENV=production`
3. Run the server: `npm start`
