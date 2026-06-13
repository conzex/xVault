import React, { useState } from 'react';
import { Plus, Link as LinkIcon, Clock, RefreshCw, X, Mail, ShieldCheck } from 'lucide-react';
import { toast } from 'sonner';
import api from '../lib/api';

export const AdminPanel: React.FC = () => {
  const [email, setEmail] = useState('');
  const [expiry, setExpiry] = useState('24h');
  const [customHours, setCustomHours] = useState('48');
  const [oneTime, setOneTime] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleGenerate = async () => {
    if (!email) return toast.error('Please enter a customer email');
    setLoading(true);
    try {
      const res = await api.post('/share/generate', { 
        email, 
        expiry, 
        oneTime,
        customHours: expiry === 'custom' ? customHours : null 
      });
      if (res.data.sent) {
        toast.success('Secure link generated and email sent via SMTP!');
      } else {
        toast.warning('Link generated but email failed to send. Check SMTP config.');
      }
      setEmail('');
    } catch (err) {
      toast.error('Failed to generate share link');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Enterprise Share Center</h1>
        <p className="text-slate-500 text-sm mt-1">Generate secure, expiring links for your customers</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="lg:col-span-1">
          <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <h3 className="font-bold text-slate-900 mb-4 flex items-center gap-2">
              <Plus size={18} className="text-brand-red" />
              Generate New Link
            </h3>
            <div className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Customer Email</label>
                <div className="relative">
                  <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                  <input 
                    type="email" 
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="customer@example.com" 
                    className="input-field pl-10 text-sm" 
                  />
                </div>
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Expiration</label>
                <select 
                  value={expiry}
                  onChange={(e) => setExpiry(e.target.value)}
                  className="input-field text-sm"
                >
                  <option value="24h">24 Hours</option>
                  <option value="7d">7 Days</option>
                  <option value="30d">30 Days</option>
                  <option value="custom">Custom Hours</option>
                  <option value="lifetime">Lifetime (No Expiry)</option>
                </select>
              </div>
              {expiry === 'custom' && (
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Hours</label>
                  <input 
                    type="number" 
                    value={customHours}
                    onChange={(e) => setCustomHours(e.target.value)}
                    className="input-field text-sm" 
                  />
                </div>
              )}
              <label className="flex items-center gap-3 cursor-pointer group">
                <input 
                  type="checkbox" 
                  checked={oneTime}
                  onChange={(e) => setOneTime(e.target.checked)}
                  className="w-4 h-4 accent-brand-red"
                />
                <span className="text-sm text-slate-600">One-time use only</span>
              </label>
              <button 
                onClick={handleGenerate}
                disabled={loading}
                className="w-full btn-primary py-2.5 rounded-lg flex items-center justify-center gap-2"
              >
                {loading ? <RefreshCw size={18} className="animate-spin" /> : <LinkIcon size={18} />}
                <span>Generate & Send Link</span>
              </button>
            </div>
          </div>
        </div>

        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="p-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
              <h3 className="font-bold text-slate-900 text-sm">Enterprise Security Status</h3>
              <span className="flex items-center gap-1 text-[10px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded-full uppercase">
                <ShieldCheck size={12} />
                SMTP Active
              </span>
            </div>
            <div className="p-8 text-center">
              <div className="max-w-md mx-auto space-y-4">
                <div className="w-16 h-16 bg-red-50 text-brand-red rounded-full flex items-center justify-center mx-auto">
                  <ShieldCheck size={32} />
                </div>
                <h4 className="font-bold text-slate-900">Secure Sharing Enabled</h4>
                <p className="text-sm text-slate-500 leading-relaxed">
                  Your enterprise vault is configured to send encrypted access tokens via 
                  <strong> mail.sumitkumawat.com</strong>. All links are tracked and automatically 
                  expire based on your security policy.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
