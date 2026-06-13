import React, { useState, useEffect } from 'react';
import { Toaster, toast } from 'sonner';
import { Layout } from './components/Layout';
import { PasswordGrid } from './components/PasswordGrid';
import { PasswordGenerator } from './components/PasswordGenerator';
import { Login } from './pages/Login';
import { AdminPanel } from './pages/AdminPanel';
import { ShareView } from './pages/ShareView';
import { AddressesView } from './pages/AddressesView';
import { NotesView } from './pages/NotesView';
import { SecurityDashboard } from './components/SecurityDashboard';
import { ProfileView } from './pages/ProfileView';
import { FoldersView } from './pages/FoldersView';
import { AddItemModal } from './components/AddItemModal';
import { useAuth } from './hooks/useAuth';
import api from './lib/api';

export default function App() {
  const { user, loading, logout, setUser } = useAuth();
  const [view, setView] = useState('dashboard');
  const [passwords, setPasswords] = useState([]);
  const [isGenModalOpen, setIsGenModalOpen] = useState(false);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [addType, setAddType] = useState<'password' | 'address' | 'note'>('password');
  const [shareToken, setShareToken] = useState<string | null>(null);
  const [verifyToken, setVerifyToken] = useState<string | null>(null);
  const [resetToken, setResetToken] = useState<string | null>(null);

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const path = window.location.pathname;
    
    if (path.startsWith('/share/')) {
      setShareToken(path.split('/')[2]);
    } else if (path === '/verify-email') {
      setVerifyToken(params.get('token'));
    } else if (path === '/reset-password') {
      setResetToken(params.get('token'));
    }
  }, []);

  useEffect(() => {
    if (user && !shareToken && !verifyToken && !resetToken) {
      fetchPasswords();
    }
  }, [user, shareToken, verifyToken, resetToken]);

  const fetchPasswords = async () => {
    try {
      const res = await api.get('/vault');
      setPasswords(res.data);
    } catch (err) {
      console.error('Failed to fetch vault');
    }
  };

  const handleLogout = async () => {
    try {
      await logout();
      toast.info('Logged out successfully');
      setView('dashboard');
    } catch (err) {
      toast.error('Logout failed');
    }
  };

  const handleVerify = async () => {
    try {
      await api.post('/auth/verify-email', { token: verifyToken });
      toast.success('Email verified! You can now log in.');
      window.location.href = '/';
    } catch (err: any) {
      toast.error(err.response?.data?.error || 'Verification failed');
      window.location.href = '/';
    }
  };

  const handleResetPassword = async (password: string) => {
    try {
      await api.post('/auth/reset-password', { token: resetToken, password });
      toast.success('Password reset successfully! Please log in.');
      window.location.href = '/';
    } catch (err: any) {
      toast.error(err.response?.data?.error || 'Reset failed');
    }
  };

  const openAddModal = (type: 'password' | 'address' | 'note') => {
    setAddType(type);
    setIsAddModalOpen(true);
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-red"></div>
      </div>
    );
  }

  if (verifyToken) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4">
        <div className="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full text-center">
          <h2 className="text-2xl font-bold text-slate-900 mb-4">Verifying Email...</h2>
          <button onClick={handleVerify} className="btn-primary w-full py-3">Confirm Verification</button>
        </div>
        <Toaster position="top-right" richColors />
      </div>
    );
  }

  if (resetToken) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4">
        <div className="bg-white p-8 rounded-2xl shadow-xl max-w-md w-full">
          <h2 className="text-2xl font-bold text-slate-900 mb-6 text-center">Reset Password</h2>
          <form onSubmit={(e) => {
            e.preventDefault();
            const pass = (e.target as any).password.value;
            handleResetPassword(pass);
          }} className="space-y-4">
            <input type="password" name="password" placeholder="New Master Password" required className="input-field" />
            <button type="submit" className="btn-primary w-full py-3">Update Password</button>
          </form>
        </div>
        <Toaster position="top-right" richColors />
      </div>
    );
  }

  if (shareToken) {
    return (
      <>
        <ShareView token={shareToken} />
        <Toaster position="top-right" richColors />
      </>
    );
  }

  if (!user) {
    return (
      <>
        <Login onLoginSuccess={setUser} />
        <Toaster position="top-right" richColors />
      </>
    );
  }

  return (
    <Layout 
      user={user} 
      onLogout={handleLogout} 
      view={view} 
      setView={setView}
      onOpenGenerator={() => setIsGenModalOpen(true)}
      onOpenAdd={openAddModal}
    >
      {view === 'dashboard' && (
        <div className="space-y-8">
          <div>
            <h1 className="text-2xl font-bold text-slate-900">Dashboard</h1>
            <p className="text-slate-500 text-sm mt-1">Welcome back to your secure enterprise vault</p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
              <p className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Items</p>
              <p className="text-3xl font-bold text-slate-900">{passwords.length}</p>
            </div>
            <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
              <p className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Favorites</p>
              <p className="text-3xl font-bold text-slate-900">{passwords.filter((p: any) => p.is_favorite).length}</p>
            </div>
            <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
              <p className="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Security Score</p>
              <p className="text-3xl font-bold text-green-600">92%</p>
            </div>
          </div>

          <PasswordGrid passwords={passwords} onRefresh={fetchPasswords} />
        </div>
      )}

      {view === 'vault' && (
        <div className="space-y-8">
          <div>
            <h1 className="text-2xl font-bold text-slate-900">My Vault</h1>
            <p className="text-slate-500 text-sm mt-1">Manage and access all your encrypted credentials</p>
          </div>
          <PasswordGrid passwords={passwords} onRefresh={fetchPasswords} />
        </div>
      )}

      {view === 'folders' && <FoldersView />}
      {view === 'addresses' && <AddressesView />}
      {view === 'notes' && <NotesView />}
      {view === 'security' && <SecurityDashboard passwords={passwords} />}
      {view === 'profile' && <ProfileView user={user} onUpdate={setUser} />}

      {view === 'admin' && <AdminPanel />}

      <PasswordGenerator isOpen={isGenModalOpen} onClose={() => setIsGenModalOpen(false)} />
      <AddItemModal 
        isOpen={isAddModalOpen} 
        onClose={() => setIsAddModalOpen(false)} 
        onRefresh={fetchPasswords}
        initialType={addType}
      />
      <Toaster position="top-right" richColors />
    </Layout>
  );
}
