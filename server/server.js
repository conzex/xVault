import express from 'express';
import { createServer as createViteServer } from 'vite';
import path from 'path';
import { fileURLToPath } from 'url';
import cookieParser from 'cookie-parser';
import cors from 'cors';
import helmet from 'helmet';
import rateLimit from 'express-rate-limit';
import dotenv from 'dotenv';

// Routes
import authRoutes from './routes/auth.js';
import vaultRoutes from './routes/vault.js';
import shareRoutes from './routes/share.js';
import addressRoutes from './routes/addresses.js';
import noteRoutes from './routes/notes.js';
import folderRoutes from './routes/folders.js';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function startServer() {
  const app = express();
  const PORT = process.env.PORT || 3000;

  // Trust proxy for rate limiting (Cloud Run/Nginx)
  app.set('trust proxy', 1);

  // Security Middlewares
  app.use(helmet({
    contentSecurityPolicy: false, // Disable for Vite dev
  }));
  app.use(cors({
    origin: process.env.CLIENT_URL || true,
    credentials: true
  }));
  app.use(cookieParser());
  app.use(express.json());

  // Rate Limiting
  const limiter = rateLimit({
    windowMs: 15 * 60 * 1000,
    max: 100
  });
  app.use('/api/', limiter);

  // API Routes
  app.use('/api/auth', authRoutes);
  app.use('/api/vault', vaultRoutes);
  app.use('/api/share', shareRoutes);
  app.use('/api/addresses', addressRoutes);
  app.use('/api/notes', noteRoutes);
  app.use('/api/folders', folderRoutes);

  // Health Check
  app.get('/api/health', (req, res) => res.json({ status: 'ok', version: '1.0.0' }));

  // Vite Integration
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
    console.log(`🚀 xVault Server running on http://localhost:${PORT}`);
  });
}

startServer();
