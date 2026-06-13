import express from 'express';
import db from '../db.js';
import { authMiddleware, adminMiddleware } from '../auth.js';
import { sendShareLink } from '../mailer.js';
import crypto from 'crypto';

const router = express.Router();

router.post('/generate', authMiddleware, adminMiddleware, async (req, res) => {
  const { email, expiry, oneTime, customHours } = req.body;
  const token = crypto.randomBytes(32).toString('hex');
  
  let expiresAt = null;
  let expiresInText = 'Lifetime';
  
  if (expiry !== 'lifetime') {
    expiresAt = new Date();
    if (expiry === 'custom' && customHours) {
      expiresAt.setHours(expiresAt.getHours() + parseInt(customHours));
      expiresInText = `${customHours} Hours`;
    } else {
      switch(expiry) {
        case '24h': expiresAt.setHours(expiresAt.getHours() + 24); expiresInText = '24 Hours'; break;
        case '7d': expiresAt.setDate(expiresAt.getDate() + 7); expiresInText = '7 Days'; break;
        case '30d': expiresAt.setDate(expiresAt.getDate() + 30); expiresInText = '30 Days'; break;
        default: expiresAt.setHours(expiresAt.getHours() + 24); expiresInText = '24 Hours';
      }
    }
  }

  db.prepare(`
    INSERT INTO shared_links (token, created_by, target_email, expires_at, one_time)
    VALUES (?, ?, ?, ?, ?)
  `).run(token, req.user.id, email, expiresAt ? expiresAt.toISOString() : null, oneTime ? 1 : 0);

  const link = `${process.env.CLIENT_URL || req.headers.origin}/share/${token}`;
  const sent = await sendShareLink(email, link, expiresInText);

  res.json({ success: true, token, sent });
});

router.get('/validate/:token', async (req, res) => {
  const link = db.prepare('SELECT * FROM shared_links WHERE token = ?').get(req.params.token);
  
  if (!link) return res.status(404).json({ error: 'Invalid link' });
  if (link.expires_at && new Date(link.expires_at) < new Date()) return res.status(410).json({ error: 'Link expired' });
  if (link.one_time && link.used) return res.status(410).json({ error: 'Link already used' });

  // Mark as used if one-time
  if (link.one_time) {
    db.prepare('UPDATE shared_links SET used = 1 WHERE id = ?').run(link.id);
  }

  res.json({ success: true, email: link.target_email });
});

export default router;
