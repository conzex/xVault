import express from 'express';
import db from '../db.js';
import { authMiddleware } from '../auth.js';

const router = express.Router();

router.use(authMiddleware);

router.get('/', (req, res) => {
  const addresses = db.prepare('SELECT * FROM addresses WHERE user_id = ?').all(req.user.id);
  res.json(addresses);
});

router.post('/', (req, res) => {
  const { label, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone, folder_id } = req.body;
  
  const result = db.prepare(`
    INSERT INTO addresses (user_id, label, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone, folder_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `).run(req.user.id, label, first_name, last_name, address_line1, address_line2, city, state, zip_code, country, phone, folder_id || null);
  
  res.status(201).json({ id: result.lastInsertRowid });
});

router.delete('/:id', (req, res) => {
  db.prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?').run(req.params.id, req.user.id);
  res.json({ success: true });
});

export default router;
