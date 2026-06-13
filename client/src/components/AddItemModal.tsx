import React, { useState, useEffect } from 'react';
import { X, Key, MapPin, FileText, Lock, Plus } from 'lucide-react';
import { toast } from 'sonner';
import api from '../lib/api';

interface AddItemModalProps {
  isOpen: boolean;
  onClose: () => void;
  onRefresh: () => void;
  initialType?: 'password' | 'address' | 'note';
}

export const AddItemModal: React.FC<AddItemModalProps> = ({ isOpen, onClose, onRefresh, initialType = 'password' }) => {
  const [type, setType] = useState(initialType);
  const [folders, setFolders] = useState([]);
  const [loading, setLoading] = useState(false);

  // Form States
  const [formData, setFormData] = useState<any>({
    folder_id: '',
    // Password
    app_name: '',
    login_url: '',
    username: '',
    password: '',
    // Address
    label: '',
    first_name: '',
    last_name: '',
    address_line1: '',
    address_line2: '',
    city: '',
    state: '',
    zip_code: '',
    country: '',
    phone: '',
    // Note
    title: '',
    content: '',
    noteType: 'note'
  });

  useEffect(() => {
    if (isOpen) {
      fetchFolders();
      setType(initialType);
    }
  }, [isOpen, initialType]);

  const fetchFolders = async () => {
    try {
      const res = await api.get('/folders');
      setFolders(res.data);
    } catch (err) {
      console.error('Failed to fetch folders');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      let endpoint = '/vault';
      let payload = { ...formData };

      if (type === 'address') {
        endpoint = '/addresses';
      } else if (type === 'note') {
        endpoint = '/notes';
        payload.type = formData.noteType;
      }

      await api.post(endpoint, payload);
      toast.success('Item added successfully');
      onRefresh();
      onClose();
      // Reset form
      setFormData({
        folder_id: '',
        app_name: '',
        login_url: '',
        username: '',
        password: '',
        label: '',
        first_name: '',
        last_name: '',
        address_line1: '',
        address_line2: '',
        city: '',
        state: '',
        zip_code: '',
        country: '',
        phone: '',
        title: '',
        content: '',
        noteType: 'note'
      });
    } catch (err: any) {
      toast.error(err.response?.data?.error || 'Failed to add item');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in zoom-in-95 duration-200">
        <div className="flex items-center justify-between p-6 border-b border-slate-100">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-brand-red text-white rounded-lg">
              <Plus size={20} />
            </div>
            <h2 className="text-xl font-bold text-slate-900">Add New Item</h2>
          </div>
          <button onClick={onClose} className="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-50">
            <X size={20} />
          </button>
        </div>

        <div className="flex border-b border-slate-100">
          <button 
            onClick={() => setType('password')}
            className={`flex-1 py-4 text-sm font-bold flex items-center justify-center gap-2 transition-colors ${type === 'password' ? 'text-brand-red border-b-2 border-brand-red bg-red-50/30' : 'text-slate-500 hover:bg-slate-50'}`}
          >
            <Key size={16} />
            <span>Password</span>
          </button>
          <button 
            onClick={() => setType('address')}
            className={`flex-1 py-4 text-sm font-bold flex items-center justify-center gap-2 transition-colors ${type === 'address' ? 'text-brand-red border-b-2 border-brand-red bg-red-50/30' : 'text-slate-500 hover:bg-slate-50'}`}
          >
            <MapPin size={16} />
            <span>Address</span>
          </button>
          <button 
            onClick={() => setType('note')}
            className={`flex-1 py-4 text-sm font-bold flex items-center justify-center gap-2 transition-colors ${type === 'note' ? 'text-brand-red border-b-2 border-brand-red bg-red-50/30' : 'text-slate-500 hover:bg-slate-50'}`}
          >
            <FileText size={16} />
            <span>Note/Token</span>
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
          {/* Common Folder Selection */}
          <div>
            <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Folder / Client</label>
            <select 
              value={formData.folder_id}
              onChange={(e) => setFormData({ ...formData, folder_id: e.target.value })}
              className="input-field text-sm"
            >
              <option value="">No Folder</option>
              {folders.map((f: any) => (
                <option key={f.id} value={f.id}>{f.name} ({f.customer_name})</option>
              ))}
            </select>
          </div>

          {type === 'password' && (
            <>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-1">App Name</label>
                  <input 
                    type="text" required
                    value={formData.app_name}
                    onChange={(e) => setFormData({ ...formData, app_name: e.target.value })}
                    className="input-field text-sm" placeholder="e.g. Google"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Login URL</label>
                  <input 
                    type="url"
                    value={formData.login_url}
                    onChange={(e) => setFormData({ ...formData, login_url: e.target.value })}
                    className="input-field text-sm" placeholder="https://..."
                  />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Username</label>
                  <input 
                    type="text" required
                    value={formData.username}
                    onChange={(e) => setFormData({ ...formData, username: e.target.value })}
                    className="input-field text-sm" placeholder="user@example.com"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Password</label>
                  <input 
                    type="password" required
                    value={formData.password}
                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                    className="input-field text-sm" placeholder="••••••••"
                  />
                </div>
              </div>
            </>
          )}

          {type === 'address' && (
            <>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Label</label>
                  <input 
                    type="text" required
                    value={formData.label}
                    onChange={(e) => setFormData({ ...formData, label: e.target.value })}
                    className="input-field text-sm" placeholder="e.g. Home"
                  />
                </div>
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Phone</label>
                  <input 
                    type="text"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    className="input-field text-sm" placeholder="+1..."
                  />
                </div>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input 
                  type="text" placeholder="First Name"
                  value={formData.first_name}
                  onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                  className="input-field text-sm"
                />
                <input 
                  type="text" placeholder="Last Name"
                  value={formData.last_name}
                  onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                  className="input-field text-sm"
                />
              </div>
              <input 
                type="text" placeholder="Address Line 1"
                value={formData.address_line1}
                onChange={(e) => setFormData({ ...formData, address_line1: e.target.value })}
                className="input-field text-sm"
              />
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input 
                  type="text" placeholder="City"
                  value={formData.city}
                  onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                  className="input-field text-sm"
                />
                <input 
                  type="text" placeholder="State"
                  value={formData.state}
                  onChange={(e) => setFormData({ ...formData, state: e.target.value })}
                  className="input-field text-sm"
                />
                <input 
                  type="text" placeholder="Zip Code"
                  value={formData.zip_code}
                  onChange={(e) => setFormData({ ...formData, zip_code: e.target.value })}
                  className="input-field text-sm"
                />
              </div>
            </>
          )}

          {type === 'note' && (
            <>
              <div>
                <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Type</label>
                <div className="flex gap-4">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input 
                      type="radio" name="noteType" value="note" 
                      checked={formData.noteType === 'note'}
                      onChange={(e) => setFormData({ ...formData, noteType: e.target.value })}
                    />
                    <span className="text-sm">Secure Note</span>
                  </label>
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input 
                      type="radio" name="noteType" value="token" 
                      checked={formData.noteType === 'token'}
                      onChange={(e) => setFormData({ ...formData, noteType: e.target.value })}
                    />
                    <span className="text-sm">API Token</span>
                  </label>
                </div>
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Title</label>
                <input 
                  type="text" required
                  value={formData.title}
                  onChange={(e) => setFormData({ ...formData, title: e.target.value })}
                  className="input-field text-sm" placeholder="e.g. Server SSH Key"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Content</label>
                <textarea 
                  required rows={5}
                  value={formData.content}
                  onChange={(e) => setFormData({ ...formData, content: e.target.value })}
                  className="input-field text-sm font-mono" placeholder="Paste sensitive information here..."
                />
              </div>
            </>
          )}

          <div className="pt-4 flex justify-end gap-3">
            <button 
              type="button" 
              onClick={onClose}
              className="px-6 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-50 rounded-xl transition-colors"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              disabled={loading}
              className="btn-primary px-8 py-2.5 rounded-xl flex items-center gap-2"
            >
              {loading ? 'Adding...' : (
                <>
                  <Lock size={18} />
                  <span>Add to Vault</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};
