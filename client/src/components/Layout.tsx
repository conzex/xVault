import React, { useState } from 'react';
import { LogOut, Plus, User as UserIcon, Settings, Key, Shield, MapPin, FileText, ChevronDown } from 'lucide-react';
import { Sidebar } from './Sidebar';

interface LayoutProps {
  children: React.ReactNode;
  user: any;
  onLogout: () => void;
  view: string;
  setView: (view: string) => void;
  onOpenGenerator: () => void;
  onOpenAdd: (type: 'password' | 'address' | 'note') => void;
}

export const Layout: React.FC<LayoutProps> = ({ children, user, onLogout, view, setView, onOpenGenerator, onOpenAdd }) => {
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const [showFabMenu, setShowFabMenu] = useState(false);

  return (
    <div className="min-h-screen flex flex-col">
      {/* Header */}
      <header className="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-30">
        <div className="flex items-center gap-8">
          <div className="flex items-center gap-2 cursor-pointer" onClick={() => setView('dashboard')}>
            <img 
              src="https://cdn.conzex.com/files/logo/icon.svg" 
              alt="xVault Logo" 
              className="w-8 h-8"
              referrerPolicy="no-referrer"
            />
            <div className="flex items-baseline gap-0.5">
              <span className="font-bold text-2xl tracking-tight text-brand-black">x</span>
              <span className="font-bold text-2xl tracking-tight text-brand-red">Vault</span>
            </div>
          </div>
          
          <nav className="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
            <button 
              onClick={() => setView('dashboard')}
              className={`hover:text-slate-900 transition-colors ${view === 'dashboard' ? 'text-brand-red' : ''}`}
            >
              Home
            </button>
            <button 
              onClick={() => setView('vault')}
              className={`hover:text-slate-900 transition-colors ${view === 'vault' ? 'text-brand-red' : ''}`}
            >
              My Vault
            </button>
            <button 
              onClick={() => setView('addresses')}
              className={`hover:text-slate-900 transition-colors ${view === 'addresses' ? 'text-brand-red' : ''}`}
            >
              Addresses
            </button>
            <button 
              onClick={() => setView('notes')}
              className={`hover:text-slate-900 transition-colors ${view === 'notes' ? 'text-brand-red' : ''}`}
            >
              Secure Notes
            </button>
            <button 
              onClick={onOpenGenerator}
              className="hover:text-slate-900 transition-colors"
            >
              Generate Password
            </button>
          </nav>
        </div>

        <div className="flex items-center gap-4">
          <div className="relative">
            <button 
              onClick={() => setShowProfileMenu(!showProfileMenu)}
              className="flex items-center gap-2 p-1 hover:bg-slate-50 rounded-lg transition-colors"
            >
              <div className="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-slate-500">
                <UserIcon size={18} />
              </div>
              <div className="text-left hidden sm:block">
                <p className="text-xs font-bold text-slate-900 leading-none">{user?.name}</p>
                <p className="text-[10px] text-slate-400 mt-0.5 uppercase tracking-wider">{user?.role}</p>
              </div>
              <ChevronDown size={14} className="text-slate-400" />
            </button>

            {showProfileMenu && (
              <div className="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-xl py-2 z-50">
                <button 
                  onClick={() => { setView('profile'); setShowProfileMenu(false); }}
                  className="w-full px-4 py-2 text-left text-sm text-slate-600 hover:bg-slate-50 flex items-center gap-2"
                >
                  <Settings size={16} />
                  <span>Manage Profile</span>
                </button>
                <button 
                  onClick={() => { setView('profile'); setShowProfileMenu(false); }}
                  className="w-full px-4 py-2 text-left text-sm text-slate-600 hover:bg-slate-50 flex items-center gap-2"
                >
                  <Shield size={16} />
                  <span>Reset Password</span>
                </button>
                <div className="h-px bg-slate-100 my-1"></div>
                <button 
                  onClick={onLogout}
                  className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                >
                  <LogOut size={16} />
                  <span>Log Out</span>
                </button>
              </div>
            )}
          </div>
        </div>
      </header>

      <div className="flex flex-1 overflow-hidden relative">
        <Sidebar view={view} setView={setView} user={user} />

        {/* Main Content */}
        <main className="flex-1 overflow-y-auto bg-slate-50">
          <div className="max-w-7xl mx-auto p-6 md:p-8">
            {children}
          </div>
        </main>

        {/* FAB */}
        <div className="fixed bottom-8 right-8 z-40">
          <div className="relative">
            {showFabMenu && (
              <div className="absolute bottom-16 right-0 space-y-2 w-48">
                <button 
                  onClick={() => { onOpenAdd('password'); setShowFabMenu(false); }}
                  className="w-full bg-white border border-slate-200 p-3 rounded-xl shadow-lg flex items-center gap-3 hover:bg-slate-50 transition-all text-sm font-semibold text-slate-700"
                >
                  <div className="p-1.5 bg-blue-50 text-blue-600 rounded-lg"><Key size={16} /></div>
                  <span>Add Password</span>
                </button>
                <button 
                  onClick={() => { onOpenAdd('address'); setShowFabMenu(false); }}
                  className="w-full bg-white border border-slate-200 p-3 rounded-xl shadow-lg flex items-center gap-3 hover:bg-slate-50 transition-all text-sm font-semibold text-slate-700"
                >
                  <div className="p-1.5 bg-green-50 text-green-600 rounded-lg"><MapPin size={16} /></div>
                  <span>Add Address</span>
                </button>
                <button 
                  onClick={() => { onOpenAdd('note'); setShowFabMenu(false); }}
                  className="w-full bg-white border border-slate-200 p-3 rounded-xl shadow-lg flex items-center gap-3 hover:bg-slate-50 transition-all text-sm font-semibold text-slate-700"
                >
                  <div className="p-1.5 bg-amber-50 text-amber-600 rounded-lg"><FileText size={16} /></div>
                  <span>Add Note</span>
                </button>
              </div>
            )}
            <button 
              onClick={() => setShowFabMenu(!showFabMenu)}
              className="w-14 h-14 bg-brand-red text-white rounded-full shadow-xl shadow-red-500/40 flex items-center justify-center hover:scale-110 transition-transform active:scale-95"
            >
              <Plus size={28} className={`transition-transform duration-300 ${showFabMenu ? 'rotate-45' : ''}`} />
            </button>
          </div>
        </div>
      </div>

      {/* Footer */}
      <footer className="bg-white border-t border-slate-200 py-12 px-6">
        <div className="max-w-7xl mx-auto">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <div className="col-span-1 md:col-span-2">
              <div className="flex items-center gap-2 mb-4">
                <img 
                  src="https://cdn.conzex.com/files/logo/icon.svg" 
                  alt="xVault Logo" 
                  className="w-8 h-8"
                  referrerPolicy="no-referrer"
                />
                <div className="flex items-baseline gap-0.5">
                  <span className="font-bold text-xl tracking-tight text-brand-black">x</span>
                  <span className="font-bold text-xl tracking-tight text-brand-red">Vault</span>
                </div>
              </div>
              <p className="text-sm text-slate-500 leading-relaxed max-w-sm">
                The world's most secure enterprise password manager. Protecting your digital life with military-grade encryption and seamless sharing.
              </p>
            </div>
            <div>
              <h4 className="font-bold text-slate-900 text-sm mb-4">Product</h4>
              <ul className="space-y-2 text-sm text-slate-500">
                <li><button onClick={() => setView('vault')} className="hover:text-brand-red">My Vault</button></li>
                <li><button onClick={onOpenGenerator} className="hover:text-brand-red">Generator</button></li>
                <li><button onClick={() => setView('security')} className="hover:text-brand-red">Security Score</button></li>
              </ul>
            </div>
            <div>
              <h4 className="font-bold text-slate-900 text-sm mb-4">Enterprise</h4>
              <ul className="space-y-2 text-sm text-slate-500">
                <li><button onClick={() => setView('admin')} className="hover:text-brand-red">Share Center</button></li>
                <li><button className="hover:text-brand-red">Admin Console</button></li>
                <li><button className="hover:text-brand-red">Security Audit</button></li>
              </ul>
            </div>
          </div>
          <div className="pt-8 border-t border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <p className="text-sm text-slate-500 font-medium">
              © 2024 Xvault
            </p>
            <p className="text-sm font-bold bg-gradient-to-r from-red-600 to-red-400 bg-clip-text text-transparent">
              A Cogent Global Product
            </p>
          </div>
        </div>
      </footer>
    </div>
  );
};
