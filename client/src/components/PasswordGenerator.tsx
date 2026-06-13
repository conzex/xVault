import React, { useState, useEffect } from 'react';
import { X, RefreshCw, Copy, Check } from 'lucide-react';
import { motion } from 'motion/react';
import { toast } from 'sonner';

interface PasswordGeneratorProps {
  isOpen: boolean;
  onClose: () => void;
}

export const PasswordGenerator: React.FC<PasswordGeneratorProps> = ({ isOpen, onClose }) => {
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
  }, [isOpen, length, options]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
      <motion.div 
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        className="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden"
      >
        <div className="p-6 border-b border-slate-100 flex justify-between items-center">
          <h3 className="text-lg font-bold text-slate-900">Generate Password</h3>
          <button onClick={onClose} className="text-slate-400 hover:text-slate-600 transition-colors">
            <X size={20} />
          </button>
        </div>
        <div className="p-6 space-y-6">
          <div className="bg-slate-50 p-4 rounded-lg border border-slate-200 flex items-center justify-between group">
            <span className="font-mono text-lg break-all text-slate-800">{password}</span>
            <div className="flex gap-2">
              <button 
                onClick={generate} 
                className="p-2 text-slate-400 hover:text-brand-red transition-colors"
                title="Regenerate"
              >
                <RefreshCw size={18} />
              </button>
              <button 
                onClick={() => {
                  navigator.clipboard.writeText(password);
                  toast.success('Password copied to clipboard');
                }}
                className="p-2 text-slate-400 hover:text-brand-red transition-colors"
                title="Copy"
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
                className="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-brand-red"
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
                    <div className="w-5 h-5 border-2 border-slate-300 rounded peer-checked:bg-brand-red peer-checked:border-brand-red transition-all"></div>
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
