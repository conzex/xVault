import CryptoJS from 'crypto-js';
import { PasswordEntry, SharedLink, User } from '../types';

const ENCRYPTION_KEY = 'conzex-vault-secret-key';

export const encrypt = (text: string): string => {
  return CryptoJS.AES.encrypt(text, ENCRYPTION_KEY).toString();
};

export const decrypt = (ciphertext: string): string => {
  const bytes = CryptoJS.AES.decrypt(ciphertext, ENCRYPTION_KEY);
  return bytes.toString(CryptoJS.enc.Utf8);
};

export const api = {
  login: async (email: string, password: string): Promise<{ success: boolean; user?: User; message?: string }> => {
    const res = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });
    return res.json();
  },

  register: async (data: any): Promise<{ success: boolean; message: string }> => {
    const res = await fetch('/api/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    return res.json();
  },

  getPasswords: async (userId: string): Promise<PasswordEntry[]> => {
    const res = await fetch(`/api/passwords/${userId}`);
    return res.json();
  },

  savePassword: async (password: PasswordEntry): Promise<void> => {
    await fetch('/api/passwords', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(password),
    });
  },

  getSharedLinks: async (): Promise<SharedLink[]> => {
    const res = await fetch('/api/shared-links');
    return res.json();
  },

  createSharedLink: async (link: SharedLink): Promise<void> => {
    await fetch('/api/shared-links', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(link),
    });
  }
};
