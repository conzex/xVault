import express from 'express';
import db from '../db.js';
import { authMiddleware } from '../auth.js';
import { encrypt, decrypt } from '../crypto.js';

const router = express.Router();

router.use(authMiddleware);

router.get('/', (req, res) => {
  const entries = db.prepare('SELECT * FROM password_entries WHERE user_id = ?').all(req.user.id);
  res.json(entries.map(e => ({
    ...e,
    is_favorite: !!e.is_favorite
  })));
});

router.post('/', (req, res) => {
  const { app_name, login_url, username, password, folder_id } = req.body;
  const encrypted = encrypt(password);
  
  const result = db.prepare(`
    INSERT INTO password_entries (user_id, app_name, login_url, username, encrypted_password, folder_id)
    VALUES (?, ?, ?, ?, ?, ?)
  `).run(req.user.id, app_name, login_url, username, encrypted, folder_id || null);
  
  res.status(201).json({ id: result.lastInsertRowid });
});

router.patch('/:id/favorite', (req, res) => {
  const entry = db.prepare('SELECT * FROM password_entries WHERE id = ? AND user_id = ?').get(req.params.id, req.user.id);
  if (!entry) return res.status(404).json({ error: 'Not found' });
  
  db.prepare('UPDATE password_entries SET is_favorite = ? WHERE id = ?').run(entry.is_favorite ? 0 : 1, req.params.id);
  res.json({ success: true });
});

router.get('/:id/decrypt', (req, res) => {
  const entry = db.prepare('SELECT encrypted_password FROM password_entries WHERE id = ? AND user_id = ?').get(req.params.id, req.user.id);
  if (!entry) return res.status(404).json({ error: 'Not found' });
  
  res.json({ password: decrypt(entry.encrypted_password) });
});

router.delete('/:id', (req, res) => {
  db.prepare('DELETE FROM password_entries WHERE id = ? AND user_id = ?').run(req.params.id, req.user.id);
  res.json({ success: true });
});

export default router;
