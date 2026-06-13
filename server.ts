import express from 'express';
import { createServer as createViteServer } from 'vite';
import path from 'path';
import { fileURLToPath } from 'url';
import nodemailer from 'nodemailer';
import cors from 'cors';
import dotenv from 'dotenv';
import Database from 'better-sqlite3';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Initialize SQLite Database
const db = new Database('vault.db');

// Create tables
db.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    email TEXT UNIQUE,
    name TEXT,
    role TEXT,
    password TEXT
  );

  CREATE TABLE IF NOT EXISTS passwords (
    id TEXT PRIMARY KEY,
    appName TEXT,
    loginUrl TEXT,
    username TEXT,
    encryptedPassword TEXT,
    userId TEXT,
    isFavorite INTEGER,
    logoUrl TEXT,
    FOREIGN KEY(userId) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS shared_links (
    id TEXT PRIMARY KEY,
    token TEXT UNIQUE,
    customerEmail TEXT,
    expiresAt TEXT,
    isOneTime INTEGER,
    isUsed INTEGER,
    sharedItemIds TEXT,
    createdBy TEXT,
    FOREIGN KEY(createdBy) REFERENCES users(id)
  );
`);

// Seed Admin if not exists
const seedAdmin = db.prepare('SELECT * FROM users WHERE email = ?').get('admin');
if (!seedAdmin) {
  db.prepare('INSERT INTO users (id, email, name, role, password) VALUES (?, ?, ?, ?, ?)').run(
    'admin-1', 'admin', 'Admin User', 'admin', 'admin'
  );
}

async function startServer() {
  const app = express();
  const PORT = 3000;

  app.use(cors());
  app.use(express.json());

  // SMTP Transporter
  const transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST || 'mail.sumitkumawat.com',
    port: parseInt(process.env.SMTP_PORT || '465'),
    secure: true,
    auth: {
      user: process.env.SMTP_USER || 'info@sumitkumawat.com',
      pass: process.env.SMTP_PASS || 'Sumit@123',
    },
  });

  // --- API Routes ---

  // Auth
  app.post('/api/auth/login', (req, res) => {
    const { email, password } = req.body;
    const user = db.prepare('SELECT * FROM users WHERE email = ? AND password = ?').get(email, password);
    if (user) {
      const { password: _, ...userWithoutPassword } = user as any;
      res.json({ success: true, user: userWithoutPassword });
    } else {
      res.status(401).json({ success: false, message: 'Invalid credentials' });
    }
  });

  app.post('/api/auth/register', (req, res) => {
    const { email, name, password } = req.body;
    try {
      const id = Math.random().toString(36).substr(2, 9);
      db.prepare('INSERT INTO users (id, email, name, role, password) VALUES (?, ?, ?, ?, ?)').run(
        id, email, name, 'customer', password
      );
      res.json({ success: true, message: 'User registered' });
    } catch (error) {
      res.status(400).json({ success: false, message: 'Email already exists' });
    }
  });

  // Passwords
  app.get('/api/passwords/:userId', (req, res) => {
    const passwords = db.prepare('SELECT * FROM passwords WHERE userId = ?').all(req.params.userId);
    res.json(passwords.map((p: any) => ({ ...p, isFavorite: !!p.isFavorite })));
  });

  app.post('/api/passwords', (req, res) => {
    const { id, appName, loginUrl, username, encryptedPassword, userId, isFavorite, logoUrl } = req.body;
    db.prepare(`
      INSERT OR REPLACE INTO passwords (id, appName, loginUrl, username, encryptedPassword, userId, isFavorite, logoUrl)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    `).run(id, appName, loginUrl, username, encryptedPassword, userId, isFavorite ? 1 : 0, logoUrl);
    res.json({ success: true });
  });

  // Shared Links
  app.get('/api/shared-links', (req, res) => {
    const links = db.prepare('SELECT * FROM shared_links').all();
    res.json(links.map((l: any) => ({ ...l, isOneTime: !!l.isOneTime, isUsed: !!l.isUsed, sharedItemIds: JSON.parse(l.sharedItemIds) })));
  });

  app.post('/api/shared-links', async (req, res) => {
    const { id, token, customerEmail, expiresAt, isOneTime, sharedItemIds, createdBy } = req.body;
    db.prepare(`
      INSERT INTO shared_links (id, token, customerEmail, expiresAt, isOneTime, isUsed, sharedItemIds, createdBy)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    `).run(id, token, customerEmail, expiresAt, isOneTime ? 1 : 0, 0, JSON.stringify(sharedItemIds), createdBy);

    // Send Email
    const link = `${req.headers.origin}/share/${token}`;
    try {
      await transporter.sendMail({
        from: `"xVault Admin" <${process.env.SMTP_USER || 'info@sumitkumawat.com'}>`,
        to: customerEmail,
        subject: 'Secure Vault Access Link',
        html: `
          <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
            <h2 style="color: #1e293b;">Secure Vault Access</h2>
            <p style="color: #475569;">You have been granted access to a secure vault via xVault.</p>
            <div style="margin: 30px 0;">
              <a href="${link}" style="background-color: #D32F2F; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">View Shared Items</a>
            </div>
            <p style="color: #94a3b8; font-size: 12px;">© 2024 Defendx. All rights reserved. A Conzex Global Product</p>
          </div>
        `,
      });
    } catch (error) {
      console.error('Email send failed:', error);
    }

    res.json({ success: true });
  });

  // Vite middleware for development
  if (process.env.NODE_ENV !== 'production') {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: 'spa',
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.join(process.cwd(), 'dist');
    app.use(express.static(distPath));
    app.get('*', (req, res) => {
      res.sendFile(path.join(distPath, 'index.html'));
    });
  }

  app.listen(PORT, '0.0.0.0', () => {
    console.log(`Server running on http://localhost:${PORT}`);
  });
}

startServer();
