import React, { useState } from 'react';
import { X, Trash2, Plus, Minus, ShoppingBag, Flame, Tag, CheckCircle } from 'lucide-react';
import { CartItem } from '../types';

interface CartSidebarProps {
  isOpen: boolean;
  onClose: () => void;
  cartItems: CartItem[];
  onUpdateQuantity: (idx: number, change: number) => void;
  onRemoveItem: (idx: number) => void;
  cartSubtotal: number;
  deliveryFee: number;
  promoDiscount: number;
  promoCode: string;
  onApplyPromoCode: (code: string) => void;
  cartTotal: number;
  serviceType: 'delivery' | 'pickup' | 'dinein';
  onProceedToCheckout: () => void;
}

export default function CartSidebar({
  isOpen,
  onClose,
  cartItems,
  onUpdateQuantity,
  onRemoveItem,
  cartSubtotal,
  deliveryFee,
  promoDiscount,
  promoCode,
  onApplyPromoCode,
  cartTotal,
  serviceType,
  onProceedToCheckout
}: CartSidebarProps) {
  const [promoInput, setPromoInput] = useState('');
  const [errorMsg, setErrorMsg] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  if (!isOpen) return null;

  const handleApplyPromo = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg('');
    setSuccessMsg('');

    const code = promoInput.trim().toUpperCase();
    if (!code) return;

    if (code === 'CHILI20') {
      onApplyPromoCode(code);
      setSuccessMsg('🔥 Coupon CHILI20 applied: 20% Discount active!');
      setPromoInput('');
    } else if (code === 'WELCOME15') {
      onApplyPromoCode(code);
      setSuccessMsg('🔥 Welcome active: 15% Entire Discount applied!');
      setPromoInput('');
    } else if (code === 'FREEGREEN') {
      onApplyPromoCode(code);
      setSuccessMsg('🥑 FREEGREEN active: Free Guac Cream on arrival!');
      setPromoInput('');
    } else {
      setErrorMsg('Invalid code. Try CHILI20 or WELCOME15.');
    }
  };

  const hasItems = cartItems.length > 0;
  const totalItemCount = cartItems.reduce((acc, curr) => acc + curr.quantity, 0);

  return (
    <div className="fixed inset-0 z-50 overflow-hidden text-[#1A1A1A]" id="cart-sidebar-container">
      {/* Background overlay with blur */}
      <div 
        onClick={onClose} 
        className="absolute inset-0 bg-[#043427]/75 backdrop-blur-xs transition-opacity" 
      />

      <div className="absolute inset-y-0 right-0 max-w-full flex pl-10">
        <div className="w-screen max-w-sm bg-[#FDFBF7] shadow-2xl flex flex-col h-full border-l border-stone-200 animate-slideLeft">
          
          {/* Header section with brand colors */}
          <div className="p-6 border-b border-stone-200 flex items-center justify-between bg-[#FDFBF7]">
            <h2 className="text-lg font-black uppercase italic flex items-center gap-2">
              My Order
              <span className="bg-[#B91C1C] text-white text-[10px] h-5 w-5 flex items-center justify-center rounded-full font-sans not-italic">
                {totalItemCount}
              </span>
            </h2>
            <button
              onClick={onClose}
              className="p-1 rounded-full text-stone-400 hover:bg-stone-100 hover:text-stone-900 transition"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Cart item listing container */}
          <div className="flex-1 overflow-y-auto p-6 space-y-6 no-scrollbar bg-white">
            {!hasItems ? (
              <div className="h-full flex flex-col justify-center items-center text-center space-y-4 py-8">
                <div className="w-12 h-12 bg-stone-50 rounded-full flex items-center justify-center border border-stone-200">
                  <ShoppingBag className="w-6 h-6 text-stone-300" />
                </div>
                <h3 className="font-extrabold text-stone-800 text-sm">Your basket is empty</h3>
                <p className="text-stone-450 text-[11px] max-w-xs leading-relaxed">
                  Go back to our menu and load up on flame-grilled burgers or sizzling combos!
                </p>
                <button
                  onClick={onClose}
                  className="px-5 py-2 bg-[#064E3B] text-white rounded-full text-[10px] font-black uppercase tracking-widest transition hover:bg-[#B91C1C]"
                >
                  Explore Recipes Now
                </button>
              </div>
            ) : (
              <div className="space-y-4">
                {cartItems.map((item, index) => (
                  <div 
                    key={index} 
                    className="flex gap-4 pb-4 border-b border-stone-100 items-start shrink-0"
                  >
                    {/* Item Thumbnail */}
                    <div className="w-16 h-16 rounded-xl bg-stone-100 overflow-hidden shrink-0">
                      <img
                        src={item.menuItem.image}
                        alt={item.menuItem.name}
                        className="w-full h-full object-cover"
                        referrerPolicy="no-referrer"
                      />
                    </div>

                    {/* Item details */}
                    <div className="flex-1 space-y-1">
                      <h4 className="text-xs font-bold uppercase text-stone-950 leading-tight">
                        {item.menuItem.name}
                      </h4>
                      <p className="text-[10px] text-stone-400">
                        {item.customization || 'Standard Recipe'}
                      </p>

                      <div className="flex justify-between items-center mt-2 pt-1">
                        <div className="flex items-center gap-2 border rounded-full px-2 py-0.5 border-stone-200 bg-white">
                          <button
                            onClick={() => onUpdateQuantity(index, -1)}
                            className="text-xs text-stone-400 hover:text-stone-800 font-bold px-1"
                            title="Decrease Quantity"
                          >
                            -
                          </button>
                          <span className="text-xs font-bold font-mono px-0.5">{item.quantity}</span>
                          <button
                            onClick={() => onUpdateQuantity(index, 1)}
                            className="text-xs text-[#064E3B] hover:text-[#B91C1C] font-bold px-1"
                            title="Increase Quantity"
                          >
                            +
                          </button>
                        </div>

                        <div className="flex items-center gap-2">
                          <span className="text-xs font-extrabold text-stone-900 font-mono">
                            &euro;{(item.menuItem.price * item.quantity).toFixed(2)}
                          </span>
                          <button
                            onClick={() => onRemoveItem(index)}
                            className="text-stone-300 hover:text-[#B91C1C] transition p-1"
                            title="Delete Item"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Checkout Totals dynamic panel */}
          {hasItems && (
            <div className="p-6 bg-white border-t border-stone-100 space-y-4">
              
              {/* Promo input form */}
              <form onSubmit={handleApplyPromo} className="space-y-1.5 pt-1">
                <div className="flex gap-2">
                  <div className="relative flex-grow">
                    <input
                      type="text"
                      value={promoInput}
                      onChange={(e) => setPromoInput(e.target.value)}
                      placeholder="ENTER COUPON CODE..."
                      className="w-full bg-stone-50 border border-stone-200 rounded-lg pl-3 pr-2 py-2 text-[10px] font-black font-mono outline-none uppercase focus:border-[#064E3B] focus:bg-white"
                    />
                    <Tag className="w-3.5 h-3.5 text-stone-400 absolute right-2.5 top-2.5 pointer-events-none" />
                  </div>
                  <button
                    type="submit"
                    className="bg-[#064E3B] hover:bg-[#B91C1C] text-white text-[10px] uppercase tracking-wider font-extrabold px-4 rounded-lg transition"
                  >
                    Apply
                  </button>
                </div>
                {errorMsg && <p className="text-[#B91C1C] text-[9px]">⚠️ {errorMsg}</p>}
                {successMsg && <p className="text-emerald-700 font-extrabold text-[9px] break-words">🎉 {successMsg}</p>}
              </form>

              {/* Subtotal metrics */}
              <div className="space-y-2 pt-2 border-t border-stone-100">
                <div className="flex justify-between text-[11px] text-stone-500 uppercase tracking-widest">
                  <span>Subtotal:</span>
                  <span className="font-extrabold text-stone-800">&euro;{cartSubtotal.toFixed(2)}</span>
                </div>
                
                {serviceType === 'delivery' ? (
                  <div className="flex justify-between text-[11px] text-stone-500 uppercase tracking-widest">
                    <span>Delivery Fee:</span>
                    <span className="font-extrabold text-stone-800">&euro;{deliveryFee.toFixed(2)}</span>
                  </div>
                ) : (
                  <div className="flex justify-between text-[11px] text-stone-550 uppercase tracking-widest items-center font-bold text-emerald-800">
                    <span>Service:</span>
                    <span className="bg-emerald-50 px-2 py-0.5 rounded text-[9px]">FREE {serviceType.toUpperCase()}</span>
                  </div>
                )}

                {promoDiscount > 0 && (
                  <div className="flex justify-between text-[11px] text-[#B91C1C] font-extrabold uppercase tracking-widest bg-rose-50/50 p-2 rounded border border-rose-100">
                    <span>Discount ({promoCode}):</span>
                    <span>-&euro;{promoDiscount.toFixed(2)}</span>
                  </div>
                )}

                <div className="flex justify-between text-lg font-black uppercase italic pt-2 border-t border-stone-150">
                  <span>Total:</span>
                  <span className="text-[#064E3B] font-mono not-italic font-black">&euro;{cartTotal.toFixed(2)}</span>
                </div>
              </div>

              {/* Checkout Now button matches design */}
              <button
                onClick={onProceedToCheckout}
                className="w-full bg-[#B91C1C] hover:bg-[#991B1B] text-white font-black py-4 rounded-xl mt-4 uppercase tracking-widest shadow-xl shadow-red-100 hover:shadow-red-200 transition-all flex items-center justify-center gap-1.5"
              >
                <span>Checkout Now</span>
              </button>
            </div>
          )}

        </div>
      </div>
    </div>
  );
}
