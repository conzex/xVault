import React, { useState, useEffect } from 'react';
import { FileText, Plus, Trash2, Key, Copy } from 'lucide-react';
import { toast } from 'sonner';
import api from '../lib/api';

export const NotesView: React.FC = () => {
  const [notes, setNotes] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchNotes();
  }, []);

  const fetchNotes = async () => {
    try {
      const res = await api.get('/notes');
      setNotes(res.data);
    } catch (err) {
      toast.error('Failed to fetch secure notes');
    } finally {
      setLoading(false);
    }
  };

  const deleteNote = async (id: number) => {
    try {
      await api.delete(`/notes/${id}`);
      toast.success('Item deleted');
      fetchNotes();
    } catch (err) {
      toast.error('Failed to delete item');
    }
  };

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
    toast.success('Copied to clipboard');
  };

  return (
    <div className="space-y-8">
      <div className="flex justify-between items-end">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Secure Notes & Tokens</h1>
          <p className="text-slate-500 text-sm mt-1">Store sensitive information, API keys, and private tokens</p>
        </div>
        <button className="btn-primary flex items-center gap-2">
          <Plus size={18} />
          <span>Add New</span>
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {notes.map((note: any) => (
          <div key={note.id} className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow group relative">
            <button 
              onClick={() => deleteNote(note.id)}
              className="absolute top-4 right-4 p-2 text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all"
            >
              <Trash2 size={16} />
            </button>
            <div className="flex items-start gap-4 mb-4">
              <div className={`p-3 rounded-lg ${note.type === 'token' ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600'}`}>
                {note.type === 'token' ? <Key size={24} /> : <FileText size={24} />}
              </div>
              <div className="flex-1 min-w-0">
                <h3 className="font-bold text-slate-900 truncate">{note.title}</h3>
                <p className="text-xs text-slate-400 capitalize">{note.type}</p>
              </div>
            </div>
            <div className="relative">
              <div className="bg-slate-50 p-3 rounded-lg border border-slate-100 text-sm text-slate-600 font-mono break-all max-h-32 overflow-y-auto">
                {note.content}
              </div>
              <button 
                onClick={() => copyToClipboard(note.content)}
                className="absolute top-2 right-2 p-1.5 bg-white border border-slate-200 rounded text-slate-400 hover:text-brand-red shadow-sm opacity-0 group-hover:opacity-100 transition-all"
              >
                <Copy size={14} />
              </button>
            </div>
          </div>
        ))}
        {notes.length === 0 && !loading && (
          <div className="col-span-full py-20 bg-white rounded-xl border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400">
            <FileText size={48} className="mb-4 opacity-20" />
            <p>No secure notes or tokens saved yet</p>
          </div>
        )}
      </div>
    </div>
  );
};
