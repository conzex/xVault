import React, { useState, useEffect, useMemo } from 'react';
import { 
  Shield, 
  Search, 
  Plus, 
  Star, 
  MoreVertical, 
  ExternalLink, 
  Copy, 
  Eye, 
  EyeOff, 
  LayoutDashboard, 
  CreditCard, 
  ShieldCheck, 
  LogOut, 
  X,
  RefreshCw,
  Check,
  Link as LinkIcon,
  Clock,
  User as UserIcon,
  Settings,
  Lock
} from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';
import { Toaster, toast } from 'sonner';
import { api, decrypt } from './lib/storage';
import { User, PasswordEntry, SharedLink, AuthState } from './types';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

// --- Components ---

const Logo = ({ className }: { className?: string }) => (
  <div className={cn("flex items-center gap-2", className)}>
    <div className="bg-[#D32F2F] p-1.5 rounded-lg">
      <Lock size={20} className="text-white" />
    </div>
    <div className="flex items-baseline gap-0.5">
      <span className="font-bold text-2xl tracking-tight text-black">x</span>
      <span className="font-bold text-2xl tracking-tight text-[#D32F2F]">Vault</span>
    </div>
  </div>
);

const Footer = () => (
  <footer className="bg-white border-t border-slate-200 py-8 px-6 mt-auto">
    <div className="max-w-7xl mx-auto flex flex-col items-center gap-4">
      <p className="text-sm text-slate-500 font-medium text-center">
        © 2024 Defendx. All rights reserved. A Conzex Global Product
      </p>
    </div>
  </footer>
);

const PasswordGeneratorModal = ({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) => {
  const [length, setLength] = useState(16);
  const [options, setOptions] = useState({
    uppercase: true,
    lowercase: true,
    numbers: true,
    symbols: true,
  });
  const [password, setPassword] = useState('');

  const generate = () => {
    const charset = {
      uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
      lowercase: 'abcdefghijklmnopqrstuvwxyz',
      numbers: '0123456789',
      symbols: '!@#$%^&*()_+~`|}{[]:;?><,./-=',
    };
    
    let characters = '';
    if (options.uppercase) characters += charset.uppercase;
    if (options.lowercase) characters += charset.lowercase;
    if (options.numbers) characters += charset.numbers;
    if (options.symbols) characters += charset.symbols;

    if (!characters) return;

    const array = new Uint32Array(length);
    window.crypto.getRandomValues(array);
    
    let result = '';
    for (let i = 0; i < length; i++) {
      result += characters[array[i] % characters.length];
    }
    setPassword(result);
  };

  useEffect(() => {
    if (isOpen) generate();
  }, [isOpen]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <motion.div 
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        className="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden"
      >
        <div className="p-6 border-b border-slate-100 flex justify-between items-center">
          <h3 className="text-lg font-bold text-slate-900">Generate Password</h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600">
            <X size={20} />
          </button>
        </div>
        <div className="p-6 space-y-6">
          <div className="bg-slate-50 p-4 rounded-lg border border-slate-200 flex items-center justify-between group">
            <span className="font-mono text-lg break-all">{password}</span>
            <div className="flex gap-2">
              <button onClick={generate} className="p-2 text-slate-400 hover:text-[#D32F2F] transition-colors">
                <RefreshCw size={18} />
              </button>
              <button 
                onClick={() => {
                  navigator.clipboard.writeText(password);
                  toast.success('Password copied to clipboard');
                }}
                className="p-2 text-slate-400 hover:text-[#D32F2F] transition-colors"
              >
                <Copy size={18} />
              </button>
            </div>
          </div>

          <div className="space-y-4">
            <div>
              <div className="flex justify-between mb-2">
                <label className="text-sm font-medium text-slate-700">Length: {length}</label>
              </div>
              <input 
                type="range" 
                min="8" 
                max="64" 
                value={length} 
                onChange={(e) => setLength(parseInt(e.target.value))}
                className="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#D32F2F]"
              />
            </div>

            <div className="grid grid-cols-2 gap-4">
              {Object.entries(options).map(([key, value]) => (
                <label key={key} className="flex items-center gap-3 cursor-pointer group">
                  <div className="relative flex items-center">
                    <input 
                      type="checkbox" 
                      checked={value} 
                      onChange={() => setOptions(prev => ({ ...prev, [key]: !value }))}
                      className="peer sr-only"
                    />
                    <div className="w-5 h-5 border-2 border-slate-300 rounded peer-checked:bg-[#D32F2F] peer-checked:border-[#D32F2F] transition-all"></div>
                    <Check size={14} className="absolute left-0.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity" />
                  </div>
                  <span className="text-sm text-slate-600 capitalize">{key}</span>
                </label>
              ))}
            </div>
          </div>
        </div>
        <div className="p-6 bg-slate-50 flex gap-3">
          <button onClick={onClose} className="flex-1 btn-secondary">Cancel</button>
          <button 
            onClick={() => {
              navigator.clipboard.writeText(password);
              toast.success('Password copied to clipboard');
              onClose();
            }}
            className="flex-1 btn-primary"
          >
            Copy & Close
          </button>
        </div>
      </motion.div>
    </div>
  );
};

// --- Main App ---

export default function App() {
  const [authState, setAuthState] = useState<AuthState>({
    user: null,
    isAuthenticated: false,
    isLoading: true,
  });
  const [isRegistering, setIsRegistering] = useState(false);
  const [view, setView] = useState<'dashboard' | 'admin' | 'cards' | 'security'>('dashboard');
  const [passwords, setPasswords] = useState<PasswordEntry[]>([]);
  const [sharedLinks, setSharedLinks] = useState<SharedLink[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [isGenModalOpen, setIsGenModalOpen] = useState(false);
  const [showPasswords, setShowPasswords] = useState<Record<string, boolean>>({});
  const [loginEmail, setLoginEmail] = useState('');
  const [loginPassword, setLoginPassword] = useState('');
  const [registerName, setRegisterName] = useState('');
  const [registerEmail, setRegisterEmail] = useState('');
  const [registerPassword, setRegisterPassword] = useState('');

  useEffect(() => {
    const savedUser = localStorage.getItem('vault_session');
    if (savedUser) {
      const user = JSON.parse(savedUser);
      setAuthState({ user, isAuthenticated: true, isLoading: false });
      loadData(user.id);
    } else {
      setAuthState(prev => ({ ...prev, isLoading: false }));
    }
  }, []);

  const loadData = async (userId: string) => {
    try {
      const [p, l] = await Promise.all([
        api.getPasswords(userId),
        api.getSharedLinks()
      ]);
      setPasswords(p);
      setSharedLinks(l);
    } catch (error) {
      console.error('Failed to load data:', error);
    }
  };

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await api.login(loginEmail, loginPassword);
      if (res.success && res.user) {
        localStorage.setItem('vault_session', JSON.stringify(res.user));
        setAuthState({ user: res.user, isAuthenticated: true, isLoading: false });
        loadData(res.user.id);
        toast.success(`Welcome back, ${res.user.name}`);
      } else {
        toast.error(res.message || 'Invalid credentials');
      }
    } catch (error) {
      toast.error('Login failed. Please try again.');
    }
  };

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const res = await api.register({
        email: registerEmail,
        name: registerName,
        password: registerPassword
      });
      if (res.success) {
        toast.success('Account created successfully! Please log in.');
        setIsRegistering(false);
        setLoginEmail(registerEmail);
      } else {
        toast.error(res.message);
      }
    } catch (error) {
      toast.error('Registration failed');
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('vault_session');
    setAuthState({ user: null, isAuthenticated: false, isLoading: false });
    toast.info('Logged out successfully');
  };

  const toggleFavorite = async (id: string) => {
    const item = passwords.find(p => p.id === id);
    if (!item) return;

    const updatedItem = { ...item, isFavorite: !item.isFavorite };
    try {
      await api.savePassword(updatedItem);
      setPasswords(passwords.map(p => p.id === id ? updatedItem : p));
    } catch (error) {
      toast.error('Failed to update favorite status');
    }
  };

  const filteredPasswords = useMemo(() => {
    return passwords
      .filter(p => 
        p.appName.toLowerCase().includes(searchQuery.toLowerCase()) ||
        p.username.toLowerCase().includes(searchQuery.toLowerCase())
      )
      .sort((a, b) => (b.isFavorite ? 1 : 0) - (a.isFavorite ? 1 : 0));
  }, [passwords, searchQuery]);

  if (authState.isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-50">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-[#D32F2F]"></div>
      </div>
    );
  }

  if (!authState.isAuthenticated) {
    return (
      <div className="min-h-screen flex flex-col bg-slate-50">
        <div className="flex-1 flex items-center justify-center p-4">
          <motion.div 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-100"
          >
            <div className="flex flex-col items-center mb-8">
              <Logo className="mb-2" />
              <p className="text-slate-500 text-sm">
                {isRegistering ? 'Create your secure vault account' : 'Log in to your secure vault'}
              </p>
            </div>
            
            {isRegistering ? (
              <form onSubmit={handleRegister} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                  <div className="relative">
                    <UserIcon className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                    <input 
                      type="text" 
                      required
                      value={registerName}
                      onChange={(e) => setRegisterName(e.target.value)}
                      placeholder="John Doe"
                      className="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#D32F2F]/20 focus:border-[#D32F2F] outline-none transition-all"
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                  <div className="relative">
                    <LinkIcon className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                    <input 
                      type="email" 
                      required
                      value={registerEmail}
                      onChange={(e) => setRegisterEmail(e.target.value)}
                      placeholder="john@example.com"
                      className="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#D32F2F]/20 focus:border-[#D32F2F] outline-none transition-all"
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Master Password</label>
                  <div className="relative">
                    <Shield className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                    <input 
                      type="password" 
                      required
                      value={registerPassword}
                      onChange={(e) => setRegisterPassword(e.target.value)}
                      placeholder="••••••••"
                      className="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#D32F2F]/20 focus:border-[#D32F2F] outline-none transition-all"
                    />
                  </div>
                </div>
                <button type="submit" className="w-full btn-primary py-3 rounded-lg text-lg shadow-lg shadow-red-500/20">
                  Create Account
                </button>
                <button 
                  type="button"
                  onClick={() => setIsRegistering(false)}
                  className="w-full text-sm text-slate-500 hover:text-slate-700 mt-2"
                >
                  Already have an account? Log in
                </button>
              </form>
            ) : (
              <form onSubmit={handleLogin} className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Email or Username</label>
                  <div className="relative">
                    <UserIcon className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                    <input 
                      type="text" 
                      required
                      value={loginEmail}
                      onChange={(e) => setLoginEmail(e.target.value)}
                      placeholder="admin or email@example.com"
                      className="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#D32F2F]/20 focus:border-[#D32F2F] outline-none transition-all"
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1">Master Password</label>
                  <div className="relative">
                    <Shield className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                    <input 
                      type="password" 
                      required
                      value={loginPassword}
                      onChange={(e) => setLoginPassword(e.target.value)}
                      placeholder="••••••••"
                      className="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#D32F2F]/20 focus:border-[#D32F2F] outline-none transition-all"
                    />
                  </div>
                </div>
                <button type="submit" className="w-full btn-primary py-3 rounded-lg text-lg shadow-lg shadow-red-500/20">
                  Log In
                </button>
                <div className="mt-8 pt-6 border-t border-slate-100 text-center">
                  <p className="text-sm text-slate-500">
                    New to Conzex? <button type="button" onClick={() => setIsRegistering(true)} className="text-[#D32F2F] font-semibold hover:underline">Create an account</button>
                  </p>
                </div>
              </form>
            )}
          </motion.div>
        </div>
        <Footer />
        <Toaster position="top-right" />
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col bg-[#F8FAFC]">
      {/* Top Bar */}
      <header className="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-30">
        <div className="flex items-center gap-8">
          <Logo />
          <div className="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
            <button onClick={() => setView('dashboard')} className={cn("hover:text-slate-900", view === 'dashboard' && "text-[#D32F2F]")}>Home</button>
            <button onClick={() => setView('dashboard')} className="hover:text-slate-900">My Vault</button>
            <button onClick={() => setIsGenModalOpen(true)} className="hover:text-slate-900">Generate Password</button>
          </div>
        </div>
        
        <div className="flex items-center gap-4">
          <div className="relative hidden sm:block">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
            <input 
              type="text" 
              placeholder="Search vault..." 
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10 pr-4 py-2 bg-slate-100 border-transparent focus:bg-white focus:border-slate-200 rounded-lg text-sm outline-none transition-all w-64"
            />
          </div>
          <div className="h-8 w-px bg-slate-200 mx-2 hidden sm:block"></div>
          <div className="flex items-center gap-3">
            <div className="text-right hidden md:block">
              <p className="text-sm font-semibold text-slate-900 leading-none">{authState.user?.name}</p>
              <p className="text-xs text-slate-500 mt-1 capitalize">{authState.user?.role}</p>
            </div>
            <button onClick={handleLogout} className="p-2 text-slate-400 hover:text-[#D32F2F] transition-colors rounded-lg hover:bg-slate-50">
              <LogOut size={20} />
            </button>
          </div>
        </div>
      </header>

      <div className="flex flex-1 overflow-hidden">
        {/* Sidebar */}
        <aside className="w-64 bg-white border-r border-slate-200 hidden lg:flex flex-col py-6">
          <div className="px-4 mb-6">
            <button className="w-full btn-primary flex items-center justify-center gap-2 py-2.5 rounded-lg shadow-md shadow-red-500/10">
              <Plus size={18} />
              <span>Add Item</span>
            </button>
          </div>

          <nav className="flex-1 space-y-1">
            <div className={cn("lastpass-sidebar-item", view === 'dashboard' && "active")} onClick={() => setView('dashboard')}>
              <LayoutDashboard size={18} />
              <span>All Items</span>
              <span className="ml-auto text-xs bg-slate-100 px-2 py-0.5 rounded-full text-slate-500">({passwords.length})</span>
            </div>
            <div className={cn("lastpass-sidebar-item", view === 'cards' && "active")} onClick={() => setView('cards')}>
              <CreditCard size={18} />
              <span>Payment cards</span>
            </div>
            <div className={cn("lastpass-sidebar-item", view === 'security' && "active")} onClick={() => setView('security')}>
              <ShieldCheck size={18} />
              <span>Security dashboard</span>
            </div>
            
            {authState.user?.role === 'admin' && (
              <>
                <div className="pt-4 pb-2 px-6 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Admin</div>
                <div className={cn("lastpass-sidebar-item", view === 'admin' && "active")} onClick={() => setView('admin')}>
                  <LinkIcon size={18} />
                  <span>Share Links</span>
                </div>
              </>
            )}
          </nav>

          <div className="mt-auto px-4">
            <div className="bg-red-50 p-4 rounded-xl border border-red-100">
              <p className="text-xs font-bold text-[#D32F2F] mb-1">Enjoyed Premium?</p>
              <p className="text-[10px] text-red-700 leading-relaxed mb-3">Your trial has expired. Upgrade now to keep advanced features.</p>
              <button className="w-full bg-white text-[#D32F2F] text-[10px] font-bold py-2 rounded border border-red-200 hover:bg-red-50 transition-colors">
                SEE PRICING
              </button>
            </div>
          </div>
        </aside>

        {/* Main Content */}
        <main className="flex-1 overflow-y-auto p-6 md:p-8">
          <AnimatePresence mode="wait">
            {view === 'dashboard' && (
              <motion.div 
                key="dashboard"
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -10 }}
              >
                <div className="flex justify-between items-end mb-8">
                  <div>
                    <h1 className="text-2xl font-bold text-slate-900">All Items</h1>
                    <p className="text-slate-500 text-sm mt-1">Manage your secure passwords and logins</p>
                  </div>
                  <div className="flex gap-3">
                    <button className="btn-secondary flex items-center gap-2 text-sm">
                      <Settings size={16} />
                      <span>Settings</span>
                    </button>
                  </div>
                </div>

                <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                  <div className="overflow-x-auto">
                    <table className="w-full border-collapse">
                      <thead>
                        <tr className="bg-slate-50">
                          <th className="lastpass-table-header w-10"></th>
                          <th className="lastpass-table-header">Name</th>
                          <th className="lastpass-table-header">Username</th>
                          <th className="lastpass-table-header">Password</th>
                          <th className="lastpass-table-header">Action</th>
                          <th className="lastpass-table-header w-10"></th>
                        </tr>
                      </thead>
                      <tbody>
                        {filteredPasswords.map((item) => (
                          <tr key={item.id} className="group hover:bg-slate-50 transition-colors">
                            <td className="lastpass-table-cell text-center">
                              <button 
                                onClick={() => toggleFavorite(item.id)}
                                className={cn("transition-colors", item.isFavorite ? "text-amber-400" : "text-slate-300 group-hover:text-slate-400")}
                              >
                                <Star size={18} fill={item.isFavorite ? "currentColor" : "none"} />
                              </button>
                            </td>
                            <td className="lastpass-table-cell">
                              <div className="flex items-center gap-3">
                                <div className="w-8 h-8 rounded bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200">
                                  {item.logoUrl ? (
                                    <img src={item.logoUrl} alt={item.appName} className="w-5 h-5" />
                                  ) : (
                                    <Shield size={16} className="text-slate-400" />
                                  )}
                                </div>
                                <div>
                                  <p className="font-semibold text-slate-900">{item.appName}</p>
                                  <p className="text-xs text-slate-400 truncate max-w-[150px]">{item.loginUrl}</p>
                                </div>
                              </div>
                            </td>
                            <td className="lastpass-table-cell">
                              <div className="flex items-center gap-2">
                                <span className="font-medium">{item.username}</span>
                                <button 
                                  onClick={() => {
                                    navigator.clipboard.writeText(item.username);
                                    toast.success('Username copied');
                                  }}
                                  className="opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-slate-600 transition-all"
                                >
                                  <Copy size={14} />
                                </button>
                              </div>
                            </td>
                            <td className="lastpass-table-cell">
                              <div className="flex items-center gap-2">
                                <span className="font-mono tracking-wider">
                                  {showPasswords[item.id] ? decrypt(item.encryptedPassword) : '••••••••'}
                                </span>
                                <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all">
                                  <button 
                                    onClick={() => setShowPasswords(prev => ({ ...prev, [item.id]: !prev[item.id] }))}
                                    className="p-1 text-slate-400 hover:text-slate-600"
                                  >
                                    {showPasswords[item.id] ? <EyeOff size={14} /> : <Eye size={14} />}
                                  </button>
                                  <button 
                                    onClick={() => {
                                      navigator.clipboard.writeText(decrypt(item.encryptedPassword));
                                      toast.success('Password copied');
                                    }}
                                    className="p-1 text-slate-400 hover:text-slate-600"
                                  >
                                    <Copy size={14} />
                                  </button>
                                </div>
                              </div>
                            </td>
                            <td className="lastpass-table-cell">
                              <button 
                                onClick={() => {
                                  window.open(item.loginUrl, '_blank');
                                  console.log(`Auto-fill triggered for ${item.appName} (Simulated)`);
                                  toast.info('Opening site and triggering auto-fill...');
                                }}
                                className="flex items-center gap-2 text-[#D32F2F] font-semibold hover:underline"
                              >
                                <span>Launch</span>
                                <ExternalLink size={14} />
                              </button>
                            </td>
                            <td className="lastpass-table-cell text-center">
                              <button className="text-slate-300 hover:text-slate-600">
                                <MoreVertical size={18} />
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                  {filteredPasswords.length === 0 && (
                    <div className="py-20 flex flex-col items-center justify-center text-slate-400">
                      <Search size={48} className="mb-4 opacity-20" />
                      <p>No items found matching your search</p>
                    </div>
                  )}
                </div>
              </motion.div>
            )}

            {view === 'admin' && (
              <motion.div 
                key="admin"
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -10 }}
              >
                <div className="mb-8">
                  <h1 className="text-2xl font-bold text-slate-900">Enterprise Share Center</h1>
                  <p className="text-slate-500 text-sm mt-1">Generate secure, expiring links for your customers</p>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                  <div className="lg:col-span-1">
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                      <h3 className="font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <Plus size={18} className="text-[#D32F2F]" />
                        Generate New Link
                      </h3>
                      <div className="space-y-4">
                        <div>
                          <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Customer Email</label>
                          <input 
                            id="customer-email"
                            type="email" 
                            placeholder="customer@example.com" 
                            className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-[#D32F2F]" 
                          />
                        </div>
                        <div>
                          <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Expiration</label>
                          <select className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:border-[#D32F2F]">
                            <option>24 Hours</option>
                            <option>7 Days</option>
                            <option>One-time use</option>
                          </select>
                        </div>
                        <button 
                          onClick={async () => {
                            const emailInput = document.getElementById('customer-email') as HTMLInputElement;
                            const email = emailInput?.value || 'customer@example.com';
                            const link: SharedLink = {
                              id: Math.random().toString(36).substr(2, 9),
                              token: Math.random().toString(36).substr(2, 12),
                              customerEmail: email,
                              expiresAt: new Date(Date.now() + 86400000).toISOString(),
                              isOneTime: false,
                              isUsed: false,
                              sharedItemIds: ['1', '3'],
                              createdBy: authState.user?.id || '',
                            };
                            try {
                              await api.createSharedLink(link);
                              toast.success('Secure link generated and email sent!');
                              loadData(authState.user?.id || '');
                            } catch (error) {
                              toast.error('Failed to generate link');
                            }
                          }}
                          className="w-full btn-primary py-2.5 rounded-lg"
                        >
                          Generate & Send Link
                        </button>
                      </div>
                    </div>
                  </div>

                  <div className="lg:col-span-2">
                    <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                      <div className="p-4 border-b border-slate-100 bg-slate-50">
                        <h3 className="font-bold text-slate-900 text-sm">Active Share Links</h3>
                      </div>
                      <div className="divide-y divide-slate-100">
                        {sharedLinks.length === 0 ? (
                          <div className="p-12 text-center text-slate-400 text-sm">
                            No active links generated yet.
                          </div>
                        ) : (
                          sharedLinks.map(link => (
                            <div key={link.id} className="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                              <div className="flex items-center gap-4">
                                <div className="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                                  <LinkIcon size={18} />
                                </div>
                                <div>
                                  <p className="text-sm font-semibold text-slate-900">{link.customerEmail}</p>
                                  <div className="flex items-center gap-3 mt-1">
                                    <span className="flex items-center gap-1 text-[10px] text-slate-500">
                                      <Clock size={10} />
                                      Expires: {new Date(link.expiresAt).toLocaleDateString()}
                                    </span>
                                    <span className="text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-bold uppercase">Active</span>
                                  </div>
                                </div>
                              </div>
                              <div className="flex gap-2">
                                <button 
                                  onClick={() => {
                                    const url = `${window.location.origin}/share/${link.token}`;
                                    navigator.clipboard.writeText(url);
                                    toast.success('Link copied to clipboard');
                                  }}
                                  className="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                  title="Copy Link"
                                >
                                  <Copy size={18} />
                                </button>
                                <button className="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Revoke">
                                  <X size={18} />
                                </button>
                              </div>
                            </div>
                          ))
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </motion.div>
            )}
          </AnimatePresence>
        </main>
      </div>

      <Footer />
      <PasswordGeneratorModal isOpen={isGenModalOpen} onClose={() => setIsGenModalOpen(false)} />
      <Toaster position="top-right" richColors />
    </div>
  );
}
