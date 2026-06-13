import React, { useState } from 'react';
import { Lock, User, Shield, Mail } from 'lucide-react';
import { motion } from 'motion/react';
import { toast } from 'sonner';
import api from '../lib/api';

interface LoginProps {
  onLoginSuccess: (user: any) => void;
}

export const Login: React.FC<LoginProps> = ({ onLoginSuccess }) => {
  const [mode, setMode] = useState<'login' | 'register' | 'forgot'>('login');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [name, setName] = useState('');
  const [loading, setLoading] = useState(false);
  const [unverified, setUnverified] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      if (mode === 'register') {
        await api.post('/auth/register', { email, password, name });
        toast.success('Registration successful! Please check your email to verify your account.');
        setMode('login');
      } else if (mode === 'forgot') {
        await api.post('/auth/forgot-password', { email });
        toast.success('Password reset link sent to your email.');
        setMode('login');
      } else {
        const res = await api.post('/auth/login', { email, password });
        if (res.data.token) {
          localStorage.setItem('token', res.data.token);
        }
        onLoginSuccess(res.data.user);
        toast.success(`Welcome back, ${res.data.user.name}`);
      }
    } catch (err: any) {
      const errorMsg = err.response?.data?.error || 'Authentication failed';
      toast.error(errorMsg);
      if (err.response?.data?.unverified) {
        setUnverified(true);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleResendVerification = async () => {
    try {
      await api.post('/auth/resend-verification', { email });
      toast.success('Verification email resent!');
    } catch (err: any) {
      toast.error(err.response?.data?.error || 'Failed to resend email');
    }
  };

  return (
    <div className="min-h-screen flex flex-col bg-slate-50">
      <div className="flex-1 flex items-center justify-center p-4">
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-100"
        >
          <div className="flex flex-col items-center mb-8">
            <div className="flex items-center gap-2 mb-2">
              <img 
                src="https://cdn.conzex.com/files/logo/icon.svg" 
                alt="xVault Logo" 
                className="w-12 h-12"
                referrerPolicy="no-referrer"
              />
              <div className="flex items-baseline gap-0.5">
                <span className="font-bold text-3xl tracking-tight text-brand-black">x</span>
                <span className="font-bold text-3xl tracking-tight text-brand-red">Vault</span>
              </div>
            </div>
            <p className="text-slate-500 text-sm">
              {mode === 'register' ? 'Create your secure enterprise account' : 
               mode === 'forgot' ? 'Reset your master password' : 'Log in to your secure vault'}
            </p>
          </div>
          
          <form onSubmit={handleSubmit} className="space-y-4">
            {mode === 'register' && (
              <div>
                <label className="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                <div className="relative">
                  <User className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                  <input 
                    type="text" 
                    required
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    placeholder="John Doe"
                    className="input-field pl-10"
                  />
                </div>
              </div>
            )}
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                <input 
                  type="text" 
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="admin or email@example.com"
                  className="input-field pl-10"
                />
              </div>
            </div>
            {mode !== 'forgot' && (
              <div>
                <div className="flex justify-between items-center mb-1">
                  <label className="block text-sm font-medium text-slate-700">Master Password</label>
                  {mode === 'login' && (
                    <button 
                      type="button" 
                      onClick={() => setMode('forgot')}
                      className="text-xs text-brand-red hover:underline"
                    >
                      Forgot Password?
                    </button>
                  )}
                </div>
                <div className="relative">
                  <Shield className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                  <input 
                    type="password" 
                    required
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="••••••••"
                    className="input-field pl-10"
                  />
                </div>
              </div>
            )}

            {unverified && mode === 'login' && (
              <div className="bg-amber-50 border border-amber-200 p-3 rounded-lg text-xs text-amber-700">
                Your email is not verified. 
                <button 
                  type="button" 
                  onClick={handleResendVerification}
                  className="ml-1 font-bold underline"
                >
                  Resend verification email
                </button>
              </div>
            )}

            <button 
              type="submit" 
              disabled={loading}
              className="w-full btn-primary py-3 rounded-lg text-lg shadow-lg shadow-red-500/20 mt-2"
            >
              {loading ? 'Processing...' : 
               mode === 'register' ? 'Create Account' : 
               mode === 'forgot' ? 'Send Reset Link' : 'Log In'}
            </button>
            
            <div className="mt-8 pt-6 border-t border-slate-100 text-center">
              <p className="text-sm text-slate-500">
                {mode === 'register' ? 'Already have an account?' : 
                 mode === 'forgot' ? 'Remembered your password?' : 'New to xVault?'} 
                <button 
                  type="button" 
                  onClick={() => {
                    setMode(mode === 'login' ? 'register' : 'login');
                    setUnverified(false);
                  }} 
                  className="text-brand-red font-semibold hover:underline ml-1"
                >
                  {mode === 'register' ? 'Log in' : 
                   mode === 'forgot' ? 'Back to login' : 'Create an account'}
                </button>
              </p>
            </div>
          </form>
        </motion.div>
      </div>
      
      <footer className="bg-white border-t border-slate-200 py-6 px-8">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
          <p className="text-sm text-slate-500 font-medium">
            © 2024 Xvault
          </p>
          <p className="text-sm font-bold bg-gradient-to-r from-red-600 to-red-400 bg-clip-text text-transparent">
            A Cogent Global Product
          </p>
        </div>
      </footer>
    </div>
  );
};
