import express from 'express';
import db from '../db.js';
import { authMiddleware } from '../auth.js';

const router = express.Router();

router.get('/', authMiddleware, (req, res) => {
  const folders = db.prepare('SELECT * FROM folders WHERE user_id = ? ORDER BY created_at DESC').all(req.user.id);
  res.json(folders);
});

router.post('/', authMiddleware, (req, res) => {
  const { name, customer_name, customer_email } = req.body;
  const result = db.prepare(`
    INSERT INTO folders (user_id, name, customer_name, customer_email)
    VALUES (?, ?, ?, ?)
  `).run(req.user.id, name, customer_name, customer_email);
  
  res.status(201).json({ id: result.lastInsertRowid, name, customer_name, customer_email });
});

router.put('/:id', authMiddleware, (req, res) => {
  const { name, customer_name, customer_email } = req.body;
  db.prepare(`
    UPDATE folders SET name = ?, customer_name = ?, customer_email = ?
    WHERE id = ? AND user_id = ?
  `).run(name, customer_name, customer_email, req.params.id, req.user.id);
  
  res.json({ success: true });
});

router.delete('/:id', authMiddleware, (req, res) => {
  db.prepare('DELETE FROM folders WHERE id = ? AND user_id = ?').run(req.params.id, req.user.id);
  res.json({ success: true });
});

export default router;
