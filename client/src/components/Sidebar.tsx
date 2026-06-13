import React from 'react';
import { Home, Key, Shield, Settings, MapPin, FileText, Folder } from 'lucide-react';

interface SidebarProps {
  view: string;
  setView: (view: string) => void;
  user: any;
}

export const Sidebar: React.FC<SidebarProps> = ({ view, setView, user }) => {
  return (
    <aside className="w-64 bg-white border-r border-slate-200 hidden lg:flex flex-col py-6">
      <nav className="flex-1 px-4 space-y-1">
        <div 
          className={`sidebar-item ${view === 'dashboard' ? 'active' : ''}`}
          onClick={() => setView('dashboard')}
        >
          <Home size={18} />
          <span>Dashboard</span>
        </div>
        <div 
          className={`sidebar-item ${view === 'vault' ? 'active' : ''}`}
          onClick={() => setView('vault')}
        >
          <Key size={18} />
          <span>All Items</span>
        </div>
        <div 
          className={`sidebar-item ${view === 'folders' ? 'active' : ''}`}
          onClick={() => setView('folders')}
        >
          <Folder size={18} />
          <span>Folders</span>
        </div>
        <div 
          className={`sidebar-item ${view === 'addresses' ? 'active' : ''}`}
          onClick={() => setView('addresses')}
        >
          <MapPin size={18} />
          <span>Addresses</span>
        </div>
        <div 
          className={`sidebar-item ${view === 'notes' ? 'active' : ''}`}
          onClick={() => setView('notes')}
        >
          <FileText size={18} />
          <span>Secure Notes</span>
        </div>
        <div 
          className={`sidebar-item ${view === 'security' ? 'active' : ''}`}
          onClick={() => setView('security')}
        >
          <Shield size={18} />
          <span>Security Dashboard</span>
        </div>
        
        {user?.role === 'admin' && (
          <>
            <div className="pt-4 pb-2 px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Admin</div>
            <div 
              className={`sidebar-item ${view === 'admin' ? 'active' : ''}`}
              onClick={() => setView('admin')}
            >
              <Settings size={18} />
              <span>Enterprise Share</span>
            </div>
          </>
        )}
      </nav>

      <div className="px-4 mt-auto">
        <div className="bg-red-50 p-4 rounded-xl border border-red-100">
          <p className="text-xs font-bold text-brand-red mb-1">Enjoyed Premium?</p>
          <p className="text-[10px] text-red-700 leading-relaxed mb-3">Your trial has expired. Upgrade now to keep advanced features.</p>
          <button className="w-full bg-white text-brand-red text-[10px] font-bold py-2 rounded border border-red-200 hover:bg-red-50 transition-colors">
            SEE PRICING
          </button>
        </div>
      </div>
    </aside>
  );
};
