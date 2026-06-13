import express from 'express';
import db from '../db.js';
import { authMiddleware } from '../auth.js';

const router = express.Router();

router.use(authMiddleware);

router.get('/', (req, res) => {
  const notes = db.prepare('SELECT * FROM secure_notes WHERE user_id = ?').all(req.user.id);
  res.json(notes);
});

router.post('/', (req, res) => {
  const { title, content, type, folder_id } = req.body;
  
  const result = db.prepare(`
    INSERT INTO secure_notes (user_id, title, content, type, folder_id)
    VALUES (?, ?, ?, ?, ?)
  `).run(req.user.id, title, content, type, folder_id || null);
  
  res.status(201).json({ id: result.lastInsertRowid });
});

router.delete('/:id', (req, res) => {
  db.prepare('DELETE FROM secure_notes WHERE id = ? AND user_id = ?').run(req.params.id, req.user.id);
  res.json({ success: true });
});

export default router;
