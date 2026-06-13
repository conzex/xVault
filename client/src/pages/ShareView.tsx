import React, { useState, useEffect } from 'react';
import { Shield, Lock, Clock, AlertTriangle } from 'lucide-react';
import { motion } from 'motion/react';
import api from '../lib/api';
import { PasswordGrid } from '../components/PasswordGrid';

interface ShareViewProps {
  token: string;
}

export const ShareView: React.FC<ShareViewProps> = ({ token }) => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [passwords, setPasswords] = useState([]);
  const [email, setEmail] = useState('');

  useEffect(() => {
    validateToken();
  }, [token]);

  const validateToken = async () => {
    try {
      const res = await api.get(`/share/validate/${token}`);
      setEmail(res.data.email);
      
      // For MVP, fetch demo passwords (or all passwords for this user if they were logged in)
      // But usually, a share link should show specific items.
      // The prompt says "show all demo passwords"
      const passRes = await api.get('/vault');
      setPasswords(passRes.data);
    } catch (err: any) {
      setError(err.response?.data?.error || 'Invalid or expired link');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-red"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4">
        <div className="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center border border-slate-100">
          <div className="w-16 h-16 bg-red-50 text-brand-red rounded-full flex items-center justify-center mx-auto mb-4">
            <AlertTriangle size={32} />
          </div>
          <h2 className="text-xl font-bold text-slate-900 mb-2">Access Denied</h2>
          <p className="text-slate-500 mb-6">{error}</p>
          <button 
            onClick={() => window.location.href = '/'}
            className="btn-primary w-full"
          >
            Go to Login
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col bg-slate-50">
      <header className="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6">
        <div className="flex items-center gap-2">
          <div className="bg-brand-red p-1.5 rounded-lg">
            <Lock size={20} className="text-white" />
          </div>
          <div className="flex items-baseline gap-0.5">
            <span className="font-bold text-2xl tracking-tight text-brand-black">x</span>
            <span className="font-bold text-2xl tracking-tight text-brand-red">Vault</span>
          </div>
        </div>
        <div className="flex items-center gap-2 text-sm text-slate-500">
          <Shield size={16} className="text-green-500" />
          <span>Secure Shared Access</span>
        </div>
      </header>

      <main className="flex-1 max-w-7xl mx-auto w-full p-6 md:p-8">
        <div className="mb-8 bg-blue-50 border border-blue-100 p-4 rounded-xl flex items-center gap-4">
          <div className="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center shrink-0">
            <Clock size={20} />
          </div>
          <div>
            <p className="text-sm font-semibold text-blue-900">Temporary Access Granted</p>
            <p className="text-xs text-blue-700">You are viewing items shared with <strong>{email}</strong>. This link is temporary.</p>
          </div>
        </div>

        <div className="mb-8">
          <h1 className="text-2xl font-bold text-slate-900">Shared Vault Items</h1>
          <p className="text-slate-500 text-sm mt-1">Access the credentials shared with you securely</p>
        </div>

        <PasswordGrid passwords={passwords} onRefresh={() => {}} />
      </main>

      <footer className="bg-white border-t border-slate-200 py-6 px-6">
        <div className="max-w-7xl mx-auto text-center">
          <p className="text-sm text-slate-500 font-medium">
            © 2024 Defendx. All rights reserved. A Conzex Global Product
          </p>
        </div>
      </footer>
    </div>
  );
};
