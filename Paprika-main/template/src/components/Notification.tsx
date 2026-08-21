import { useEffect } from 'react';
import { Flame, X, CheckSquare } from 'lucide-react';

interface NotificationProps {
  message: string;
  type: 'success' | 'info' | 'error';
  onClose: () => void;
}

export default function Notification({ message, type, onClose }: NotificationProps) {
  useEffect(() => {
    const timer = setTimeout(() => {
      onClose();
    }, 4000);
    return () => clearTimeout(timer);
  }, [onClose]);

  return (
    <div className="fixed bottom-24 sm:bottom-6 right-4 z-55 max-w-sm w-full bg-white rounded-2xl border border-stone-200 p-4 shadow-2xl flex items-start gap-3 animate-slideIn">
      <div className={`p-2 rounded-xl shrink-0 ${
        type === 'success' 
          ? 'bg-emerald-50 text-emerald-800 border border-emerald-250' 
          : type === 'error'
          ? 'bg-rose-50 text-rose-800'
          : 'bg-[#155A38]/10 text-[#155A38]'
      }`}>
        {type === 'success' ? (
          <CheckSquare className="w-5 h-5 text-emerald-700" />
        ) : (
          <Flame className="w-5 h-5 text-[#D2143A] fill-[#D2143A]" />
        )}
      </div>

      <div className="flex-grow space-y-0.5 select-none">
        <span className="block text-xs font-black text-stone-900 font-sans uppercase">
          {type === 'success' ? 'Verde Recipe Alert' : 'System Notice'}
        </span>
        <p className="text-stone-500 text-xs sm:text-sm font-sans leading-snug">{message}</p>
      </div>

      <button onClick={onClose} className="p-1 hover:bg-stone-50 rounded text-stone-400 hover:text-stone-700 transition">
        <X className="w-4 h-4" />
      </button>
    </div>
  );
}
