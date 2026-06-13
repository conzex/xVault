import express from 'express';
import crypto from 'crypto';
import db from '../db.js';
import { hashPassword, verifyPassword, generateToken, authMiddleware } from '../auth.js';
import { sendVerificationEmail, sendPasswordResetEmail } from '../mailer.js';

const router = express.Router();

const ABUSIVE_EMAILS = ['spam', 'abuse', 'admin', 'root', 'webmaster', 'support'];
const RESTRICTED_PASSWORDS = ['admin', 'root', 'password', '123456'];

router.post('/register', async (req, res) => {
  const { email, password, name } = req.body;
  
  if (!email || !password || !name) {
    return res.status(400).json({ error: 'All fields are required' });
  }

  const emailUser = email.split('@')[0].toLowerCase();
  if (ABUSIVE_EMAILS.includes(emailUser)) {
    return res.status(400).json({ error: 'This email address is not allowed' });
  }

  if (RESTRICTED_PASSWORDS.includes(password.toLowerCase())) {
    return res.status(400).json({ error: 'Password is too weak or restricted' });
  }

  try {
    const hashedPassword = await hashPassword(password);
    const verificationToken = crypto.randomBytes(32).toString('hex');
    
    db.prepare('INSERT INTO users (email, password_hash, name, verification_token) VALUES (?, ?, ?, ?)').run(
      email, hashedPassword, name, verificationToken
    );

    await sendVerificationEmail(email, verificationToken);
    
    res.status(201).json({ success: true, message: 'Verification email sent' });
  } catch (err) {
    res.status(400).json({ error: 'Email already exists' });
  }
});

router.post('/verify-email', async (req, res) => {
  const { token } = req.body;
  const user = db.prepare('SELECT * FROM users WHERE verification_token = ?').get(token);
  
  if (!user) {
    return res.status(400).json({ error: 'Invalid or expired verification token' });
  }

  db.prepare('UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?').run(user.id);
  res.json({ success: true, message: 'Email verified successfully' });
});

router.post('/resend-verification', async (req, res) => {
  const { email } = req.body;
  const user = db.prepare('SELECT * FROM users WHERE email = ? AND is_verified = 0').get(email);
  
  if (!user) {
    return res.status(404).json({ error: 'User not found or already verified' });
  }

  const verificationToken = crypto.randomBytes(32).toString('hex');
  db.prepare('UPDATE users SET verification_token = ? WHERE id = ?').run(verificationToken, user.id);
  
  await sendVerificationEmail(email, verificationToken);
  res.json({ success: true, message: 'Verification email resent' });
});

router.post('/login', async (req, res) => {
  const { email, password } = req.body;
  
  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email);
  
  if (!user || !(await verifyPassword(password, user.password_hash))) {
    return res.status(401).json({ error: 'Invalid credentials' });
  }

  if (user.role !== 'admin' && !user.is_verified) {
    return res.status(403).json({ error: 'Please verify your email before logging in', unverified: true });
  }

  const token = generateToken(user.id, user.role);
  
  res.cookie('token', token, {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'strict',
    maxAge: 7 * 24 * 60 * 60 * 1000 // 7 days
  });

  res.json({
    token,
    user: {
      id: user.id,
      email: user.email,
      role: user.role,
      name: user.name
    }
  });
});

router.post('/forgot-password', async (req, res) => {
  const { email } = req.body;
  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email);
  
  if (!user) {
    return res.status(404).json({ error: 'Email not found' });
  }

  const resetToken = crypto.randomBytes(32).toString('hex');
  const expiry = new Date(Date.now() + 3600000).toISOString(); // 1 hour
  
  db.prepare('UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?').run(
    resetToken, expiry, user.id
  );

  await sendPasswordResetEmail(email, resetToken);
  res.json({ success: true, message: 'Password reset email sent' });
});

router.post('/reset-password', async (req, res) => {
  const { token, password } = req.body;
  
  if (RESTRICTED_PASSWORDS.includes(password.toLowerCase())) {
    return res.status(400).json({ error: 'Password is too weak or restricted' });
  }

  const user = db.prepare('SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > ?').get(
    token, new Date().toISOString()
  );
  
  if (!user) {
    return res.status(400).json({ error: 'Invalid or expired reset token' });
  }

  const hashedPassword = await hashPassword(password);
  db.prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?').run(
    hashedPassword, user.id
  );

  res.json({ success: true, message: 'Password reset successfully' });
});

router.post('/update-profile', authMiddleware, async (req, res) => {
  const { name, email } = req.body;
  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(req.user.id);

  if (email && email !== user.email) {
    const existing = db.prepare('SELECT * FROM users WHERE email = ?').get(email);
    if (existing) return res.status(400).json({ error: 'Email already in use' });

    const verificationToken = crypto.randomBytes(32).toString('hex');
    db.prepare('UPDATE users SET name = ?, email = ?, is_verified = 0, verification_token = ? WHERE id = ?').run(
      name || user.name, email, verificationToken, user.id
    );
    await sendVerificationEmail(email, verificationToken);
    return res.json({ success: true, message: 'Profile updated. Please verify your new email.', reverify: true });
  }

  db.prepare('UPDATE users SET name = ? WHERE id = ?').run(name || user.name, user.id);
  res.json({ success: true, message: 'Profile updated' });
});

router.post('/change-password', authMiddleware, async (req, res) => {
  const { currentPassword, newPassword } = req.body;
  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(req.user.id);

  if (!(await verifyPassword(currentPassword, user.password_hash))) {
    return res.status(401).json({ error: 'Incorrect current password' });
  }

  if (RESTRICTED_PASSWORDS.includes(newPassword.toLowerCase())) {
    return res.status(400).json({ error: 'New password is too weak or restricted' });
  }

  const resetToken = crypto.randomBytes(32).toString('hex');
  const expiry = new Date(Date.now() + 3600000).toISOString();
  
  db.prepare('UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?').run(
    resetToken, expiry, user.id
  );

  await sendPasswordResetEmail(user.email, resetToken);
  res.json({ success: true, message: 'Verification email sent. Please confirm to change password.' });
});

router.post('/logout', (req, res) => {
  res.clearCookie('token');
  res.json({ success: true });
});

router.get('/me', authMiddleware, (req, res) => {
  const user = db.prepare('SELECT id, email, role, name, is_verified FROM users WHERE id = ?').get(req.user.id);
  res.json(user);
});

export default router;
