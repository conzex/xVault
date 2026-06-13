import { toast } from 'sonner';

export const useClipboard = () => {
  const copy = (text: string, label: string = 'Text') => {
    navigator.clipboard.writeText(text);
    toast.success(`${label} copied to clipboard`);
  };

  return { copy };
};
