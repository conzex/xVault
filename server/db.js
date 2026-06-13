import Database from 'better-sqlite3';
import bcrypt from 'bcryptjs';
import { encrypt } from './crypto.js';

const db = new Database('xvault.db');

// Initialize Schema
db.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    name TEXT,
    is_verified BOOLEAN DEFAULT 0,
    verification_token TEXT,
    reset_token TEXT,
    reset_token_expiry TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS folders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    customer_name TEXT,
    customer_email TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS password_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    folder_id INTEGER REFERENCES folders(id) ON DELETE SET NULL,
    app_name TEXT NOT NULL,
    login_url TEXT,
    username TEXT,
    encrypted_password TEXT NOT NULL,
    is_favorite BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS addresses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    folder_id INTEGER REFERENCES folders(id) ON DELETE SET NULL,
    label TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    address_line1 TEXT,
    address_line2 TEXT,
    city TEXT,
    state TEXT,
    zip_code TEXT,
    country TEXT,
    phone TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS secure_notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    folder_id INTEGER REFERENCES folders(id) ON DELETE SET NULL,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    type TEXT DEFAULT 'note', -- 'note' or 'token'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS shared_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    token TEXT UNIQUE NOT NULL,
    created_by INTEGER REFERENCES users(id),
    target_email TEXT,
    expires_at TIMESTAMP, -- NULL means lifetime
    one_time BOOLEAN DEFAULT 0,
    used BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );
`);

// Seed Data
async function seed() {
  const userCount = db.prepare('SELECT COUNT(*) as count FROM users').get().count;
  
  if (userCount === 0) {
    console.log('Seeding initial data...');
    
    // Admin
    const adminHash = await bcrypt.hash('admin', 12);
    db.prepare('INSERT INTO users (email, password_hash, role, name, is_verified) VALUES (?, ?, ?, ?, ?)').run(
      'admin', adminHash, 'admin', 'Admin User', 1
    );

    // Demo User
    const userHash = await bcrypt.hash('password123', 12);
    const userResult = db.prepare('INSERT INTO users (email, password_hash, role, name, is_verified) VALUES (?, ?, ?, ?, ?)').run(
      'customer@example.com', userHash, 'user', 'Demo Customer', 1
    );
    const userId = userResult.lastInsertRowid;

    // Sample Folders
    const insertFolder = db.prepare(`
      INSERT INTO folders (user_id, name, customer_name, customer_email)
      VALUES (?, ?, ?, ?)
    `);
    const personalFolder = insertFolder.run(userId, 'Personal', 'Demo Customer', 'customer@example.com');
    const workFolder = insertFolder.run(userId, 'Work', 'Conzex Team', 'team@conzex.com');

    // Sample Passwords (14 total now)
    const samples = [
      ['Amazon', 'https://amazon.com', 'adprod@sukumawa', 'AmazonPass123', personalFolder.lastInsertRowid],
      ['bmc.com', 'https://bmc.com', 'admin@conze.com', 'BMCPass456', workFolder.lastInsertRowid],
      ['bmc.com', 'https://bmc.com', 'administrator@vsphere.local', 'VSphereAdmin789', workFolder.lastInsertRowid],
      ['bmc.com', 'https://bmc.com', 'kumawatsumit45@gmail.com', 'KumawatPass000', workFolder.lastInsertRowid],
      ['conze.com', 'https://conze.com', 'shyamk1525@gmail.com', 'ConzeShyam111', workFolder.lastInsertRowid],
      ['conze.com', 'https://conze.com', 'sharma.ankit0067@gmail.com', 'AnkitPass222', workFolder.lastInsertRowid],
      ['Google', 'https://accounts.google.com', 'sumit@conzex.com', 'GoogleSecure!123', personalFolder.lastInsertRowid],
      ['GitHub', 'https://github.com', 'sukumawa45', 'GitPass_2024', workFolder.lastInsertRowid],
      ['LinkedIn', 'https://linkedin.com', 'sumit.kumawat', 'LinkIn_9988', personalFolder.lastInsertRowid],
      ['Netflix', 'https://netflix.com', 'family@home.com', 'ChillPass_001', personalFolder.lastInsertRowid],
      ['Spotify', 'https://spotify.com', 'music_lover', 'BeatPass_77', personalFolder.lastInsertRowid],
      ['Dropbox', 'https://dropbox.com', 'files@work.com', 'DropBox_Secure_1', workFolder.lastInsertRowid],
      ['Slack', 'https://slack.com', 'team_lead', 'Slack_Workspace_2024', workFolder.lastInsertRowid],
      ['Microsoft 365', 'https://office.com', 'work@corp.com', 'Office_365_Pass', workFolder.lastInsertRowid]
    ];

    const insertPass = db.prepare(`
      INSERT INTO password_entries (user_id, app_name, login_url, username, encrypted_password, folder_id)
      VALUES (?, ?, ?, ?, ?, ?)
    `);

    for (const [app, url, user, pass, folderId] of samples) {
      insertPass.run(userId, app, url, user, encrypt(pass), folderId);
    }

    // Sample Addresses
    const insertAddr = db.prepare(`
      INSERT INTO addresses (user_id, label, first_name, last_name, address_line1, city, state, zip_code, country)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `);
    insertAddr.run(userId, 'Home', 'Sumit', 'Kumawat', '123 Main St', 'New York', 'NY', '10001', 'USA');

    // Sample Tokens/Notes
    const insertNote = db.prepare(`
      INSERT INTO secure_notes (user_id, title, content, type)
      VALUES (?, ?, ?, ?)
    `);
    insertNote.run(userId, 'API Token', 'sk_live_51P...abc123', 'token');
    insertNote.run(userId, 'Server SSH Key', '-----BEGIN RSA PRIVATE KEY-----...', 'note');
    
    console.log('Seeding complete.');
  }
}

seed();

export default db;
