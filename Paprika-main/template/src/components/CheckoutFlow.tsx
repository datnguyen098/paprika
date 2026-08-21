import React, { useState, useTransition } from 'react';
import { MapPin, ShoppingBag, CreditCard, ShieldCheck, HelpCircle, CheckCircle, Clock } from 'lucide-react';
import { OrderDetails, CartItem, BranchStore } from '../types';
import { BRANCH_STORES } from '../data/menu';

interface CheckoutFlowProps {
  cartItems: CartItem[];
  cartSubtotal: number;
  deliveryFee: number;
  promoDiscount: number;
  promoCode: string;
  cartTotal: number;
  serviceType: 'delivery' | 'pickup' | 'dinein';
  setServiceType: (type: 'delivery' | 'pickup' | 'dinein') => void;
  onClearCart: () => void;
  setCurrentPage: (page: string) => void;
}

export default function CheckoutFlow({
  cartItems,
  cartSubtotal,
  deliveryFee,
  promoDiscount,
  promoCode,
  cartTotal,
  serviceType,
  setServiceType,
  onClearCart,
  setCurrentPage
}: CheckoutFlowProps) {
  const [selectedStore, setSelectedStore] = useState<string>(BRANCH_STORES[0].id);
  const [paymentMethod, setPaymentMethod] = useState<'card' | 'cash' | 'gpay'>('card');
  const [isPending, startTransition] = useTransition();

  // Address fields
  const [street, setStreet] = useState('');
  const [city, setCity] = useState('Athens');
  const [floor, setFloor] = useState('');
  const [phone, setPhone] = useState('');
  const [notes, setNotes] = useState('');

  // Table dine-in
  const [tableNo, setTableNo] = useState('Table 4');

  // Submit states
  const [validationError, setValidationError] = useState('');
  const [successOrderTicket, setSuccessOrderTicket] = useState<{
    id: string;
    eta: string;
    addressOrStore: string;
  } | null>(null);

  const activeStoreObj = BRANCH_STORES.find(s => s.id === selectedStore) || BRANCH_STORES[0];

  const handlePlaceOrder = (e: React.FormEvent) => {
    e.preventDefault();
    setValidationError('');

    // Quick fields validation validation
    if (!phone.trim()) {
      setValidationError('Mobile phone contact number is required to receive SMS tracking links.');
      return;
    }
    if (serviceType === 'delivery' && !street.trim()) {
      setValidationError('Please input a valid delivery street address.');
      return;
    }

    // Success reservation code generator
    const randomTicketNo = `VC-${Math.floor(100000 + Math.random() * 900000)}`;
    const calculatedEta = serviceType === 'delivery' ? '25 - 35 mins' : serviceType === 'pickup' ? '12 mins' : '8 mins';
    const addressDetails = serviceType === 'delivery' 
      ? `${street}, ${floor ? 'Floor ' + floor + ', ' : ''}${city}`
      : activeStoreObj.name;

    setSuccessOrderTicket({
      id: randomTicketNo,
      eta: calculatedEta,
      addressOrStore: addressDetails
    });
  };

  const handleFinishCheckout = () => {
    onClearCart();
    setSuccessOrderTicket(null);
    startTransition(() => {
      setCurrentPage('home');
    });
  };

  // Success Confirmation State rendering
  if (successOrderTicket) {
    return (
      <div className="bg-[#FDFBF7] py-16 px-4 sm:px-6 lg:px-8 max-w-xl mx-auto" id="checkout-success-view">
        <div className="bg-white rounded-3xl border border-stone-200 p-8 text-center space-y-6 shadow-xl animate-fadeIn">
          {/* Animated Green Badge */}
          <div className="w-16 h-16 bg-emerald-50 text-emerald-800 mx-auto rounded-full flex items-center justify-center border-2 border-emerald-300">
            <CheckCircle className="w-8 h-8 text-emerald-700 animate-pulse font-bold" />
          </div>

          <div className="space-y-2">
            <span className="text-[10px] uppercase font-mono tracking-widest bg-emerald-100 text-[#064E3B] px-3 py-1 rounded-full font-bold">
              Cooking Commenced
            </span>
            <h2 className="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight font-sans italic uppercase">
              Your gourmet feast is cooking!
            </h2>
            <p className="text-stone-500 text-sm max-w-md mx-auto">
              Order code <strong>{successOrderTicket.id}</strong>. We sent an SMS and live courier tracker link straight to your mobile phone number.
            </p>
          </div>

          {/* Ticket Info Card */}
          <div className="bg-stone-50 rounded-2xl border border-stone-150 p-5 text-left space-y-3 divide-y divide-stone-200">
            <div className="pb-3 grid grid-cols-2 gap-2 text-xs">
              <div>
                <span className="block text-stone-400 font-bold uppercase text-[9px]">Fulfillment Type</span>
                <span className="font-extrabold text-stone-900 uppercase">{serviceType}</span>
              </div>
              <div>
                <span className="block text-stone-400 font-bold uppercase text-[9px]">Prep Sizzling ETA</span>
                <span className="font-extrabold text-[#B91C1C] flex items-center gap-1">
                  <Clock className="w-3.5 h-3.5" />
                  {successOrderTicket.eta}
                </span>
              </div>
            </div>

            <div className="pt-3 pb-3 text-xs">
              <span className="block text-stone-400 font-bold uppercase text-[10px] mb-1">Destination Location</span>
              <span className="font-bold text-stone-800">{successOrderTicket.addressOrStore}</span>
            </div>

            <div className="pt-3 text-xs w-full">
              <span className="block text-stone-400 font-bold uppercase text-[10px] mb-2">My Basket Summary ({cartItems.length} items)</span>
              <div className="space-y-1">
                {cartItems.map((item, idx) => (
                  <div key={idx} className="flex justify-between text-[#064E3B] font-bold">
                    <span>{item.quantity}x {item.menuItem.name}</span>
                    <span>&euro;{(item.menuItem.price * item.quantity).toFixed(2)}</span>
                  </div>
                ))}
              </div>
              {promoCode && (
                <div className="text-emerald-700 font-semibold flex items-center justify-between mt-2.5 bg-emerald-50 p-1.5 rounded border border-emerald-200">
                  <span>Coupon {promoCode} Active:</span>
                  <span>-&euro;{promoDiscount.toFixed(2)}</span>
                </div>
              )}
              <div className="flex justify-between font-bold text-stone-950 text-sm pt-2 mt-2 border-t border-dashed border-stone-300">
                <span>Grand Paid Total:</span>
                <span className="font-mono text-base text-[#064E3B]">&euro;{cartTotal.toFixed(2)}</span>
              </div>
            </div>
          </div>

          <button
            onClick={handleFinishCheckout}
            className="w-full py-4 bg-[#B91C1C] hover:bg-[#991B1B] text-white text-sm uppercase tracking-widest font-black rounded-xl transition duration-150 shadow-md"
          >
            Return to Homepage
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-[#FDFBF7] text-stone-900 py-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto flex-grow" id="checkout-form-view">
      <div className="text-center space-y-2 mb-8 animate-fadeIn">
        <span className="text-[#B91C1C] text-xs font-black uppercase tracking-widest block">Checkout Form</span>
        <h1 className="text-2xl sm:text-3xl font-black text-stone-950 tracking-tight font-sans italic uppercase">
          Review & Settle order
        </h1>
        <p className="text-stone-500 text-sm max-w-sm mx-auto">
          Confirm your order mode, delivery credentials and let us ignite the flames immediately.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {/* LEFT COLUMN: THE DETAILED OPTIONS FORM */}
        <form onSubmit={handlePlaceOrder} className="lg:col-span-7 space-y-6">
          
          {/* SERVICE TYPE SELECTOR BOX */}
          <div className="bg-white rounded-2xl border border-stone-200 p-6 shadow-sm space-y-4">
            <h3 className="text-xs uppercase font-extrabold text-stone-400 tracking-wider">
              1. Select Ordering Mode
            </h3>
            <div className="grid grid-cols-3 gap-3">
              {[
                { id: 'delivery', label: 'Home Delivery', desc: 'Athens area' },
                { id: 'pickup', label: 'Click & Collect', desc: 'Zero booking fee' },
                { id: 'dinein', label: 'Dine-In Table', desc: 'Syntagma depot' }
              ].map((mode) => (
                <button
                  key={mode.id}
                  type="button"
                  onClick={() => setServiceType(mode.id as any)}
                  className={`p-3.5 rounded-xl border text-left transition ${
                    serviceType === mode.id
                      ? 'bg-[#064E3B]/5 border-[#064E3B] text-[#064E3B]'
                      : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100'
                  }`}
                >
                  <span className="block font-bold text-xs sm:text-sm uppercase tracking-wide">{mode.label}</span>
                  <span className="block text-[10px] text-stone-400 mt-0.5">{mode.desc}</span>
                </button>
              ))}
            </div>
          </div>

          {/* FULFILLMENT INPUT FIELDS DYNAMIC VIEW */}
          <div className="bg-white rounded-2xl border border-stone-200 p-6 shadow-sm space-y-5">
            <h2 className="text-sm font-extrabold text-stone-900 uppercase tracking-wide border-b border-stone-100 pb-2">
              2. Fulfillment Credentials
            </h2>

            {validationError && (
              <div className="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-705 font-medium">
                ⚠️ {validationError}
              </div>
            )}

            {/* Home Delivery Dynamic Inputs */}
            {serviceType === 'delivery' && (
              <div className="space-y-4 animate-slideIn">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="space-y-1">
                    <label className="block text-xs font-bold text-stone-600">Street Address *</label>
                    <input
                      type="text"
                      value={street}
                      onChange={(e) => setStreet(e.target.value)}
                      placeholder="Leoforos Syngrou 412"
                      className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4.5 py-3 text-sm outline-none transition"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="block text-xs font-bold text-stone-600">City / Municipality *</label>
                    <select
                      value={city}
                      onChange={(e) => setCity(e.target.value)}
                      className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-3 py-3 text-sm outline-none transition"
                    >
                      <option value="Athens">Central Athens Municipality</option>
                      <option value="Glyfada">Glyfada Waterfront</option>
                      <option value="Marousi">Marousi North Hub</option>
                      <option value="Kallithea">Kallithea Suburb</option>
                    </select>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-1">
                    <label className="block text-xs font-bold text-stone-600">Floor / Apt No</label>
                    <input
                      type="text"
                      value={floor}
                      onChange={(e) => setFloor(e.target.value)}
                      placeholder="e.g. 2nd Floor, Apt 4"
                      className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-3 text-sm outline-none transition"
                    />
                  </div>
                  <div className="space-y-1">
                    <label className="block text-xs font-bold text-stone-600">Contact Phone Number *</label>
                    <input
                      type="tel"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      placeholder="+30 697 123 4567"
                      className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-3 text-sm outline-none transition"
                    />
                  </div>
                </div>

                <div className="space-y-1">
                  <label className="block text-xs font-bold text-stone-600">Delivery Notes for Courier</label>
                  <textarea
                    value={notes}
                    onChange={(e) => setNotes(e.target.value)}
                    placeholder="Ring bell 'Papadopoulos'. Leave box at the primary entry gate if not response."
                    rows={2}
                    className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-3 text-sm outline-none transition"
                  />
                </div>
              </div>
            )}

            {/* Click and Collect Store Branch Selection */}
            {serviceType === 'pickup' && (
              <div className="space-y-4 animate-slideIn">
                <span className="block text-xs font-bold text-stone-400 uppercase tracking-widest leading-none">Select Pick Up Depot Branch</span>
                
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  {BRANCH_STORES.map((store) => (
                    <button
                      key={store.id}
                      type="button"
                      onClick={() => setSelectedStore(store.id)}
                      className={`p-4 rounded-xl border text-left transition flex flex-col justify-between h-full group ${
                        selectedStore === store.id
                          ? 'bg-[#064E3B]/5 border-[#064E3B] shadow-sm'
                          : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100'
                      }`}
                    >
                      <span className={`w-3.5 h-3.5 rounded-full border mb-3 shrink-0 flex items-center justify-center ${
                        selectedStore === store.id ? 'bg-[#064E3B] border-[#064E3B]' : 'bg-white border-stone-300'
                      }`} />
                      <div>
                        <span className="block font-black text-xs sm:text-sm text-stone-900 leading-tight group-hover:text-[#064E3B]">{store.name}</span>
                        <span className="block text-[10px] text-stone-400 mt-1">{store.address}</span>
                        <span className="block text-[9px] text-[#B91C1C] font-bold mt-2 font-mono h-4 italic">({store.distance})</span>
                      </div>
                    </button>
                  ))}
                </div>

                <div className="p-4 bg-stone-100 rounded-xl space-y-2 text-xs">
                  <div className="flex justify-between">
                    <span className="text-stone-500">Selected Store Code:</span>
                    <strong className="text-[#064E3B]">{activeStoreObj.id}</strong>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-stone-500">Depot Telephone:</span>
                    <strong className="text-stone-800">{activeStoreObj.phone}</strong>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-stone-500">Operation Times:</span>
                    <strong className="text-stone-800">{activeStoreObj.hours}</strong>
                  </div>
                </div>

                <div className="grid grid-cols-1 gap-4">
                  <div className="space-y-1">
                    <label className="block text-xs font-bold text-stone-600">Contact Phone Number *</label>
                    <input
                      type="tel"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      placeholder="+30 697 123 4567"
                      className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-3 text-sm outline-none transition"
                    />
                  </div>
                </div>
              </div>
            )}

            {/* Dine-In Custom Selection Table Area */}
            {serviceType === 'dinein' && (
              <div className="space-y-4 animate-slideIn">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="space-y-1">
                    <label className="block text-xs font-bold text-stone-600">Table Placement Select</label>
                    <select
                      value={tableNo}
                      onChange={(e) => setTableNo(e.target.value)}
                      className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-3 py-3 text-sm outline-none transition"
                    >
                      {Array.from({ length: 12 }).map((_, idx) => (
                        <option key={idx} value={`Table ${idx + 1}`}>
                          Table {idx + 1} ({idx < 4 ? 'Window View' : idx < 8 ? 'Indoor Saloon' : 'Garden Pergola'})
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="space-y-1">
                    <label className="block text-xs font-bold text-stone-600">Contact Phone Number *</label>
                    <input
                      type="tel"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      placeholder="+30 697 123 4567"
                      className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-3 text-sm outline-none transition"
                    />
                  </div>
                </div>

                <p className="text-xs text-stone-400">
                  We will notify our kitchen and cook things instantly. Sitting directly at Table {tableNo}. Our staff will deliver immediately to your seat.
                </p>
              </div>
            )}

          </div>

          {/* PAYMENT CHOICE SELECTORS */}
          <div className="bg-white rounded-2xl border border-stone-200 p-6 shadow-sm space-y-4">
            <h3 className="text-xs uppercase font-extrabold text-stone-400 tracking-wider">
              3. Instant Payment Options
            </h3>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              {[
                { id: 'card', label: 'Credit/Debit Card', desc: 'Secure Stripe processing' },
                { id: 'cash', label: 'Pay on Receipt', desc: 'Cash or mobile POS' },
                { id: 'gpay', label: 'Google / Apple Pay', desc: 'Instant single-tap checkout' }
              ].map((pay) => (
                <button
                  key={pay.id}
                  type="button"
                  onClick={() => setPaymentMethod(pay.id as any)}
                  className={`p-3.5 rounded-xl border text-left transition flex items-start gap-2.5 ${
                    paymentMethod === pay.id
                      ? 'bg-[#064E3B]/5 border-[#064E3B] text-[#064E3B]'
                      : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100'
                  }`}
                >
                  <CreditCard className="w-5 h-5 text-[#064E3B] shrink-0 mt-0.5" />
                  <div>
                    <span className="block font-bold text-xs uppercase tracking-wide">{pay.label}</span>
                    <span className="block text-[10px] text-stone-400 mt-0.5">{pay.desc}</span>
                  </div>
                </button>
              ))}
            </div>
          </div>

          <button
            type="submit"
            className="w-full py-4 bg-[#B91C1C] hover:bg-[#991B1B] text-white uppercase font-black text-xs tracking-widest rounded-xl transition shadow-lg shadow-red-100 hover:scale-[1.01]"
          >
            Order & Pay &euro;{cartTotal.toFixed(2)} Now
          </button>
        </form>

        {/* RIGHT COLUMN: BASKET CART OVERVIEW TICKETS */}
        <div className="lg:col-span-5 space-y-6">
          <div className="bg-white rounded-2xl border border-stone-200 p-6 shadow-sm space-y-5">
            <h3 className="text-xs uppercase font-extrabold text-[#064E3B] tracking-wider mb-2 font-heading">
              My Order Sum
            </h3>

            <div className="space-y-4 max-h-[300px] overflow-y-auto pr-2 no-scrollbar">
              {cartItems.map((item, idx) => (
                <div key={idx} className="flex gap-4 pb-4 border-b border-stone-100 items-start">
                  <div className="w-12 h-12 bg-stone-50 rounded-lg overflow-hidden shrink-0">
                    <img src={item.menuItem.image} alt={item.menuItem.name} className="w-full h-full object-cover" />
                  </div>
                  <div className="flex-1 space-y-0.5">
                    <h4 className="text-xs font-bold uppercase text-stone-900 leading-tight">{item.menuItem.name}</h4>
                    <p className="text-[10px] text-stone-400">{item.customization || 'Standard Recipe'}</p>
                    <div className="flex justify-between items-center mt-1">
                      <span className="text-[10px] text-stone-400 font-mono">Qty: {item.quantity}</span>
                      <strong className="text-xs font-bold font-mono text-stone-800">&euro;{(item.menuItem.price * item.quantity).toFixed(2)}</strong>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* Total calculations */}
            <div className="space-y-2.5 pt-2 text-xs">
              <div className="flex justify-between text-stone-500 uppercase tracking-widest text-[10px]">
                <span>Subtotal Amount:</span>
                <span className="font-extrabold text-stone-850">&euro;{cartSubtotal.toFixed(2)}</span>
              </div>
              
              {serviceType === 'delivery' ? (
                <div className="flex justify-between text-stone-500 uppercase tracking-widest text-[10px]">
                  <span>Athens Delivery Fee:</span>
                  <span className="font-extrabold text-stone-850">&euro;{deliveryFee.toFixed(2)}</span>
                </div>
              ) : (
                <div className="flex justify-between text-emerald-800 font-bold uppercase tracking-widest text-[10px]">
                  <span>Depot Collect Charge:</span>
                  <span className="bg-emerald-50 px-2 py-0.5 rounded text-[9px]">FREE</span>
                </div>
              )}

              {promoDiscount > 0 && (
                <div className="flex justify-between text-[#B91C1C] font-black uppercase tracking-widest text-[10px] bg-rose-50 p-2 rounded border border-rose-100">
                  <span>Discount ({promoCode}):</span>
                  <span>-&euro;{promoDiscount.toFixed(2)}</span>
                </div>
              )}

              <div className="border-t border-stone-200 pt-3 flex justify-between text-stone-950 font-black italic uppercase text-sm font-heading">
                <span>Total Payment:</span>
                <span className="font-mono text-base text-[#064E3B] not-italic font-black">&euro;{cartTotal.toFixed(2)}</span>
              </div>
            </div>

            {/* Secure statement badges */}
            <div className="p-3 bg-stone-50 rounded-xl border border-stone-100 space-y-2 text-[10px] text-stone-450 font-bold uppercase tracking-wide">
              <div className="flex gap-2 items-center">
                <ShieldCheck className="w-4 h-4 text-[#064E3B]" />
                <span>256-bit SSL Cryptography Encrypted</span>
              </div>
              <div className="flex gap-2 items-center">
                <HelpCircle className="w-4 h-4 text-[#064E3B]" />
                <span>Contact Hotline support active</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
}
