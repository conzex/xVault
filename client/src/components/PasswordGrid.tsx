import React, { useState } from 'react';
import { Star, ExternalLink, Copy, Eye, EyeOff, MoreVertical, Shield } from 'lucide-react';
import { toast } from 'sonner';
import api from '../lib/api';

interface PasswordGridProps {
  passwords: any[];
  onRefresh: () => void;
}

export const PasswordGrid: React.FC<PasswordGridProps> = ({ passwords, onRefresh }) => {
  const [showPasswords, setShowPasswords] = useState<Record<number, string>>({});

  const toggleFavorite = async (id: number) => {
    try {
      await api.patch(`/vault/${id}/favorite`);
      onRefresh();
    } catch (err) {
      toast.error('Failed to update favorite');
    }
  };

  const decryptPassword = async (id: number) => {
    if (showPasswords[id]) {
      const newShow = { ...showPasswords };
      delete newShow[id];
      setShowPasswords(newShow);
      return;
    }

    try {
      const res = await api.get(`/vault/${id}/decrypt`);
      setShowPasswords({ ...showPasswords, [id]: res.data.password });
    } catch (err) {
      toast.error('Failed to decrypt password');
    }
  };

  const copyToClipboard = (text: string, label: string) => {
    navigator.clipboard.writeText(text);
    toast.success(`${label} copied to clipboard`);
  };

  const launchUrl = (url: string, user: string, pass: string) => {
    window.open(url, '_blank');
    console.log(`🔐 Auto-fill simulation: Username=${user}, Password=${pass}`);
    toast.info('Opening website... (auto-fill would trigger here)');
  };

  return (
    <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div className="overflow-x-auto">
        <table className="w-full border-collapse">
          <thead>
            <tr className="bg-slate-50 border-b border-slate-200">
              <th className="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-10"></th>
              <th className="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Name</th>
              <th className="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Username</th>
              <th className="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Password</th>
              <th className="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Action</th>
              <th className="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-10"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {passwords.map((item) => (
              <tr key={item.id} className="group hover:bg-slate-50 transition-colors">
                <td className="px-4 py-4 text-center">
                  <button 
                    onClick={() => toggleFavorite(item.id)}
                    className={`transition-colors ${item.is_favorite ? 'text-amber-400' : 'text-slate-300 group-hover:text-slate-400'}`}
                  >
                    <Star size={18} fill={item.is_favorite ? "currentColor" : "none"} />
                  </button>
                </td>
                <td className="px-4 py-4">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded bg-slate-100 flex items-center justify-center border border-slate-200">
                      <Shield size={16} className="text-slate-400" />
                    </div>
                    <div>
                      <p className="font-semibold text-slate-900">{item.app_name}</p>
                      <p className="text-xs text-slate-400 truncate max-w-[150px]">{item.login_url}</p>
                    </div>
                  </div>
                </td>
                <td className="px-4 py-4">
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-medium text-slate-700">{item.username}</span>
                    <button 
                      onClick={() => copyToClipboard(item.username, 'Username')}
                      className="opacity-0 group-hover:opacity-100 p-1 text-slate-400 hover:text-slate-600 transition-all"
                    >
                      <Copy size={14} />
                    </button>
                  </div>
                </td>
                <td className="px-4 py-4">
                  <div className="flex items-center gap-2">
                    <span className="font-mono text-sm tracking-wider text-slate-600">
                      {showPasswords[item.id] ? showPasswords[item.id] : '••••••••'}
                    </span>
                    <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all">
                      <button 
                        onClick={() => decryptPassword(item.id)}
                        className="p-1 text-slate-400 hover:text-slate-600"
                      >
                        {showPasswords[item.id] ? <EyeOff size={14} /> : <Eye size={14} />}
                      </button>
                      <button 
                        onClick={async () => {
                          const res = await api.get(`/vault/${item.id}/decrypt`);
                          copyToClipboard(res.data.password, 'Password');
                        }}
                        className="p-1 text-slate-400 hover:text-slate-600"
                      >
                        <Copy size={14} />
                      </button>
                    </div>
                  </div>
                </td>
                <td className="px-4 py-4">
                  <button 
                    onClick={() => launchUrl(item.login_url, item.username, '********')}
                    className="flex items-center gap-2 text-brand-red font-semibold text-sm hover:underline"
                  >
                    <span>Launch</span>
                    <ExternalLink size={14} />
                  </button>
                </td>
                <td className="px-4 py-4 text-center">
                  <button className="text-slate-300 hover:text-slate-600">
                    <MoreVertical size={18} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {passwords.length === 0 && (
        <div className="py-20 flex flex-col items-center justify-center text-slate-400">
          <Shield size={48} className="mb-4 opacity-20" />
          <p>No items in your vault yet</p>
        </div>
      )}
    </div>
  );
};
