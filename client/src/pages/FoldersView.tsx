import React, { useState, useEffect } from 'react';
import { Folder, Plus, Trash2, User, Mail, ChevronRight } from 'lucide-react';
import { toast } from 'sonner';
import api from '../lib/api';

export const FoldersView: React.FC = () => {
  const [folders, setFolders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showAdd, setShowAdd] = useState(false);
  const [newFolder, setNewFolder] = useState({ name: '', customer_name: '', customer_email: '' });

  useEffect(() => {
    fetchFolders();
  }, []);

  const fetchFolders = async () => {
    try {
      const res = await api.get('/folders');
      setFolders(res.data);
    } catch (err) {
      toast.error('Failed to fetch folders');
    } finally {
      setLoading(false);
    }
  };

  const handleAdd = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await api.post('/folders', newFolder);
      toast.success('Folder created');
      setNewFolder({ name: '', customer_name: '', customer_email: '' });
      setShowAdd(false);
      fetchFolders();
    } catch (err) {
      toast.error('Failed to create folder');
    }
  };

  const deleteFolder = async (id: number) => {
    try {
      await api.delete(`/folders/${id}`);
      toast.success('Folder deleted');
      fetchFolders();
    } catch (err) {
      toast.error('Failed to delete folder');
    }
  };

  return (
    <div className="space-y-8">
      <div className="flex justify-between items-end">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Folders & Clients</h1>
          <p className="text-slate-500 text-sm mt-1">Organize your vault by project or customer</p>
        </div>
        <button 
          onClick={() => setShowAdd(true)}
          className="btn-primary flex items-center gap-2"
        >
          <Plus size={18} />
          <span>New Folder</span>
        </button>
      </div>

      {showAdd && (
        <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-lg animate-in fade-in slide-in-from-top-4">
          <h3 className="font-bold text-slate-900 mb-4">Create New Folder</h3>
          <form onSubmit={handleAdd} className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input 
              type="text" 
              placeholder="Folder Name (e.g. Project X)" 
              required
              value={newFolder.name}
              onChange={(e) => setNewFolder({ ...newFolder, name: e.target.value })}
              className="input-field text-sm"
            />
            <input 
              type="text" 
              placeholder="Customer Name" 
              value={newFolder.customer_name}
              onChange={(e) => setNewFolder({ ...newFolder, customer_name: e.target.value })}
              className="input-field text-sm"
            />
            <input 
              type="email" 
              placeholder="Customer Email" 
              value={newFolder.customer_email}
              onChange={(e) => setNewFolder({ ...newFolder, customer_email: e.target.value })}
              className="input-field text-sm"
            />
            <div className="md:col-span-3 flex justify-end gap-2">
              <button type="button" onClick={() => setShowAdd(false)} className="px-4 py-2 text-sm text-slate-500 hover:bg-slate-50 rounded-lg">Cancel</button>
              <button type="submit" className="btn-primary px-6">Create Folder</button>
            </div>
          </form>
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {folders.map((folder: any) => (
          <div key={folder.id} className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow group relative">
            <button 
              onClick={() => deleteFolder(folder.id)}
              className="absolute top-4 right-4 p-2 text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all"
            >
              <Trash2 size={16} />
            </button>
            <div className="flex items-center gap-4 mb-4">
              <div className="p-3 bg-slate-50 text-slate-400 rounded-lg">
                <Folder size={24} />
              </div>
              <div className="flex-1 min-w-0">
                <h3 className="font-bold text-slate-900 truncate">{folder.name}</h3>
                <p className="text-xs text-slate-400">Created {new Date(folder.created_at).toLocaleDateString()}</p>
              </div>
            </div>
            
            <div className="space-y-2 border-t border-slate-50 pt-4">
              {folder.customer_name && (
                <div className="flex items-center gap-2 text-xs text-slate-500">
                  <User size={14} />
                  <span>{folder.customer_name}</span>
                </div>
              )}
              {folder.customer_email && (
                <div className="flex items-center gap-2 text-xs text-slate-500">
                  <Mail size={14} />
                  <span>{folder.customer_email}</span>
                </div>
              )}
            </div>
            
            <button className="w-full mt-4 py-2 bg-slate-50 hover:bg-slate-100 rounded-lg text-xs font-bold text-slate-600 flex items-center justify-center gap-1 transition-colors">
              <span>View Items</span>
              <ChevronRight size={14} />
            </button>
          </div>
        ))}
        {folders.length === 0 && !loading && (
          <div className="col-span-full py-20 bg-white rounded-xl border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400">
            <Folder size={48} className="mb-4 opacity-20" />
            <p>No folders created yet</p>
          </div>
        )}
      </div>
    </div>
  );
};
