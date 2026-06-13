import React, { useState, useEffect } from 'react';
import { MapPin, Plus, Trash2, Phone, Globe } from 'lucide-react';
import { toast } from 'sonner';
import api from '../lib/api';

export const AddressesView: React.FC = () => {
  const [addresses, setAddresses] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchAddresses();
  }, []);

  const fetchAddresses = async () => {
    try {
      const res = await api.get('/addresses');
      setAddresses(res.data);
    } catch (err) {
      toast.error('Failed to fetch addresses');
    } finally {
      setLoading(false);
    }
  };

  const deleteAddress = async (id: number) => {
    try {
      await api.delete(`/addresses/${id}`);
      toast.success('Address deleted');
      fetchAddresses();
    } catch (err) {
      toast.error('Failed to delete address');
    }
  };

  return (
    <div className="space-y-8">
      <div className="flex justify-between items-end">
        <div>
          <h1 className="text-2xl font-bold text-slate-900">Addresses</h1>
          <p className="text-slate-500 text-sm mt-1">Store and manage your physical addresses for easy auto-fill</p>
        </div>
        <button className="btn-primary flex items-center gap-2">
          <Plus size={18} />
          <span>Add Address</span>
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {addresses.map((addr: any) => (
          <div key={addr.id} className="bg-white p-6 rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow group relative">
            <button 
              onClick={() => deleteAddress(addr.id)}
              className="absolute top-4 right-4 p-2 text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all"
            >
              <Trash2 size={16} />
            </button>
            <div className="flex items-start gap-4 mb-4">
              <div className="p-3 bg-slate-50 text-slate-400 rounded-lg">
                <MapPin size={24} />
              </div>
              <div>
                <h3 className="font-bold text-slate-900">{addr.label}</h3>
                <p className="text-sm text-slate-600">{addr.first_name} {addr.last_name}</p>
              </div>
            </div>
            <div className="space-y-2 text-sm text-slate-500">
              <p>{addr.address_line1}</p>
              {addr.address_line2 && <p>{addr.address_line2}</p>}
              <p>{addr.city}, {addr.state} {addr.zip_code}</p>
              <div className="flex items-center gap-2 pt-2">
                <Globe size={14} />
                <span>{addr.country}</span>
              </div>
              {addr.phone && (
                <div className="flex items-center gap-2">
                  <Phone size={14} />
                  <span>{addr.phone}</span>
                </div>
              )}
            </div>
          </div>
        ))}
        {addresses.length === 0 && !loading && (
          <div className="col-span-full py-20 bg-white rounded-xl border border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-400">
            <MapPin size={48} className="mb-4 opacity-20" />
            <p>No addresses saved yet</p>
          </div>
        )}
      </div>
    </div>
  );
};
