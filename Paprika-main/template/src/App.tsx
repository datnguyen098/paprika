import { useState, useEffect, Activity, startTransition } from 'react';
import Header from './components/Header';
import Footer from './components/Footer';
import HomeSection from './components/HomeSection';
import MenuSection from './components/MenuSection';
import CheckoutFlow from './components/CheckoutFlow';
import BookingSection from './components/BookingSection';
import AboutSection from './components/AboutSection';
import CartSidebar from './components/CartSidebar';
import Notification from './components/Notification';
import { MenuItem, MenuItemCategory, CartItem } from './types';
import { Home, Compass, ShoppingBag, Calendar, Phone, Flame, ChevronRight } from 'lucide-react';

export default function App() {
  const [currentPage, setCurrentPage] = useState<string>('home');
  const [activeCategory, setActiveCategory] = useState<MenuItemCategory>('combos');
  const [cartItems, setCartItems] = useState<CartItem[]>([]);
  const [serviceType, setServiceType] = useState<'delivery' | 'pickup' | 'dinein'>('delivery');
  const [isCartOpen, setIsCartOpen] = useState<boolean>(false);
  const [toast, setToast] = useState<{ message: string; type: 'success' | 'info' | 'error' } | null>(null);

  // Promo code configurations state
  const [promoCode, setPromoCode] = useState<string>('');
  const [promoDiscountPercentage, setPromoDiscountPercentage] = useState<number>(0);

  // Floating Greek help lines trigger
  const [hotlineCallTriggered, setHotlineCallTriggered] = useState<boolean>(false);

  // Auto scroll to top on page switches to avoid layout shifts and optimize mobile UX
  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'instant' });
  }, [currentPage]);

  // Toast trigger helper
  const showToast = (message: string, type: 'success' | 'info' | 'error' = 'success') => {
    setToast({ message, type });
  };

  // Add Item to Basket
  const handleAddToCart = (item: MenuItem, customization?: string, quantity: number = 1) => {
    setCartItems((prevItems) => {
      // Find if item with same customization already exists
      const existingIdx = prevItems.findIndex(
        (i) => i.menuItem.id === item.id && i.customization === customization
      );

      if (existingIdx > -1) {
        const copied = [...prevItems];
        copied[existingIdx].quantity += quantity;
        showToast(`Added ${quantity > 1 ? quantity + 'x' : 'another'} ${item.name} to your basket!`, 'success');
        return copied;
      } else {
        showToast(`${quantity}x ${item.name} added to your basket!`, 'success');
        return [...prevItems, { menuItem: item, quantity, customization }];
      }
    });
  };

  // Update line counts inside shopping cart
  const handleUpdateQuantity = (idx: number, change: number) => {
    setCartItems((prevItems) => {
      const copied = [...prevItems];
      const newQuantity = copied[idx].quantity + change;
      
      if (newQuantity <= 0) {
        showToast(`${copied[idx].menuItem.name} removed from basket.`, 'info');
        copied.splice(idx, 1);
      } else {
        copied[idx].quantity = newQuantity;
      }
      return copied;
    });
  };

  // Remove whole line from card
  const handleRemoveItem = (idx: number) => {
    setCartItems((prevItems) => {
      const copied = [...prevItems];
      showToast(`${copied[idx].menuItem.name} removed.`, 'info');
      copied.splice(idx, 1);
      return copied;
    });
  };

  // Promo code trigger handler
  const handleApplyPromoCode = (code: string) => {
    setPromoCode(code);
    if (code === 'CHILI20') {
      setPromoDiscountPercentage(20);
    } else if (code === 'WELCOME15') {
      setPromoDiscountPercentage(15);
    } else if (code === 'FREEGREEN') {
      setPromoDiscountPercentage(0); // FREE item reward instead
    }
  };

  // Clearing whole basket upon successful order submissions
  const handleClearCart = () => {
    setCartItems([]);
    setPromoCode('');
    setPromoDiscountPercentage(0);
  };

  // Financial calculations
  const cartSubtotal = cartItems.reduce(
    (acc, curr) => acc + curr.menuItem.price * curr.quantity,
    0
  );

  // Athens Greece delivery fee setup
  const deliveryFee = serviceType === 'delivery' && cartSubtotal > 0 ? 2.50 : 0.00;

  // Greek discount math rules:
  // CHILI20: Save 20% on elements under the 'combos' category!
  // WELCOME15: Save 15% on the entire subtotal sum
  let promoDiscount = 0;
  if (promoCode === 'CHILI20') {
    const totalCombosSum = cartItems
      .filter((i) => i.menuItem.category === 'combos')
      .reduce((acc, curr) => acc + curr.menuItem.price * curr.quantity, 0);
    promoDiscount = totalCombosSum * 0.20;
  } else if (promoCode === 'WELCOME15') {
    promoDiscount = cartSubtotal * 0.15;
  }

  const cartTotal = Math.max(0, cartSubtotal + deliveryFee - promoDiscount);

  const cartItemsCount = cartItems.reduce((acc, curr) => acc + curr.quantity, 0);

  const handleProceedToCheckout = () => {
    setIsCartOpen(false);
    startTransition(() => {
      setCurrentPage('checkout');
    });
  };

  const handleFloatingCall = () => {
    setHotlineCallTriggered(true);
    showToast('Dialing hotline: +30 210 555 7777', 'info');
    setTimeout(() => {
      setHotlineCallTriggered(false);
    }, 6000);
  };

  return (
    <div className="min-h-screen bg-[#FDFBF7] text-stone-900 font-sans flex flex-col justify-between selection:bg-[#B91C1C]/15">
      
      {/* Primary Brand Sticky Navigation Header */}
      <Header
        currentPage={currentPage}
        setCurrentPage={setCurrentPage}
        cartItemsCount={cartItemsCount}
        onOpenCart={() => setIsCartOpen(true)}
        serviceType={serviceType}
        setServiceType={setServiceType}
      />

      {/* Main Dynamic View Section Switcher */}
      <main className="flex-grow pb-20 sm:pb-0">
        {currentPage === 'home' && (
          <HomeSection
            setCurrentPage={setCurrentPage}
            onAddToCart={handleAddToCart}
            onApplyPromoCode={handleApplyPromoCode}
            appliedPromo={promoCode}
          />
        )}

        {currentPage === 'menu' && (
          <MenuSection
            onAddToCart={handleAddToCart}
            activeCategory={activeCategory}
            setActiveCategory={setActiveCategory}
          />
        )}

        {currentPage === 'checkout' && (
          <CheckoutFlow
            cartItems={cartItems}
            cartSubtotal={cartSubtotal}
            deliveryFee={deliveryFee}
            promoDiscount={promoDiscount}
            promoCode={promoCode}
            cartTotal={cartTotal}
            serviceType={serviceType}
            setServiceType={setServiceType}
            onClearCart={handleClearCart}
            setCurrentPage={setCurrentPage}
          />
        )}

        {currentPage === 'booking' && (
          <BookingSection />
        )}

        {currentPage === 'about' && (
          <AboutSection setCurrentPage={setCurrentPage} />
        )}
      </main>

      {/* Primary Brand Dark Green Premium Footer */}
      <Footer setCurrentPage={setCurrentPage} />

      {/* Slide Out Shopping Basket */}
      <CartSidebar
        isOpen={isCartOpen}
        onClose={() => setIsCartOpen(false)}
        cartItems={cartItems}
        onUpdateQuantity={handleUpdateQuantity}
        onRemoveItem={handleRemoveItem}
        cartSubtotal={cartSubtotal}
        deliveryFee={deliveryFee}
        promoDiscount={promoDiscount}
        promoCode={promoCode}
        onApplyPromoCode={handleApplyPromoCode}
        cartTotal={cartTotal}
        serviceType={serviceType}
        onProceedToCheckout={handleProceedToCheckout}
      />

      {/* Live Custom Dynamic Toasts Banner */}
      {toast && (
        <Notification
          message={toast.message}
          type={toast.type}
          onClose={() => setToast(null)}
        />
      )}

      {/* 6. MOBILE STICKY BOTTOM TAB NAVIGATION (Thumb-friendly UX for 320px - 414px) */}
      <div 
        className="fixed bottom-0 left-0 right-0 z-40 bg-[#064E3B] border-t border-[#043427] sm:hidden shadow-lg"
        id="mobile-sticky-bottom-navigation"
      >
        <div className="grid grid-cols-4 gap-1 py-1.5 px-2">
          {/* Home action Button */}
          <button
            onClick={() => setCurrentPage('home')}
            className={`flex flex-col items-center justify-center p-2 rounded-xl transition ${
              currentPage === 'home' ? 'text-[#FFD700] bg-[#043427]' : 'text-stone-300'
            }`}
          >
            <Home className="w-5 h-5 leading-none" />
            <span className="text-[10px] uppercase font-bold tracking-wider mt-1 block">Home</span>
          </button>

          {/* Menus action Button */}
          <button
            onClick={() => setCurrentPage('menu')}
            className={`flex flex-col items-center justify-center p-2 rounded-xl transition ${
              currentPage === 'menu' ? 'text-[#FFD700] bg-[#043427]' : 'text-stone-300'
            }`}
          >
            <Compass className="w-5 h-5 leading-none" />
            <span className="text-[10px] uppercase font-bold tracking-wider mt-1 block">Menu</span>
          </button>

          {/* Reservings/Booking button */}
          <button
            onClick={() => setCurrentPage('booking')}
            className={`flex flex-col items-center justify-center p-2 rounded-xl transition ${
              currentPage === 'booking' ? 'text-[#FFD700] bg-[#043427]' : 'text-stone-300'
            }`}
          >
            <Calendar className="w-5 h-5 leading-none" />
            <span className="text-[10px] uppercase font-bold tracking-wider mt-1 block">Booking</span>
          </button>

          {/* Cart triggers with item indicators */}
          <button
            onClick={() => setIsCartOpen(true)}
            className={`flex flex-col items-center justify-center p-2 rounded-xl relative transition ${
              isCartOpen ? 'text-[#FFD700] bg-[#043427]' : 'text-stone-300'
            }`}
          >
            <div className="relative">
              <ShoppingBag className="w-5 h-5 leading-none" />
              {cartItemsCount > 0 && (
                <span className="absolute -top-1.5 -right-2 bg-[#B91C1C] text-white text-[9px] font-bold h-4.5 min-w-4.5 px-1 flex items-center justify-center rounded-full border border-[#064E3B]">
                  {cartItemsCount}
                </span>
              )}
            </div>
            <span className="text-[10px] uppercase font-bold tracking-wider mt-1 block">Basket</span>
          </button>
        </div>
      </div>

      {/* 7. FLOATING QUICK HOTLINE DIALER PHONE PANEL */}
      <div className="fixed bottom-20 sm:bottom-6 left-6 z-40" id="floating-contact-panel">
        <button
          onClick={handleFloatingCall}
          className={`p-4 rounded-full shadow-2xl transition duration-300 flex items-center justify-center border hover:scale-110 ${
            hotlineCallTriggered 
              ? 'bg-[#B91C1C] text-white border-[#B91C1C]' 
              : 'bg-[#064E3B] text-white border-[#043427] hover:bg-[#B91C1C]'
          }`}
          title="Direct Delivery Telephone Hotline"
        >
          <Phone className={`w-5 h-5 ${hotlineCallTriggered ? 'animate-bounce' : 'animate-pulse'}`} />
        </button>

        {/* Ring statement bubble */}
        {hotlineCallTriggered && (
          <div className="absolute left-14 bottom-2.5 bg-stone-90 w-48 text-stone-900 text-xs p-3.5 rounded-2xl bg-white shadow-2xl border border-stone-200 animate-slideIn">
            <span className="block font-bold text-[#064E3B]">Calling Athens Kitchen</span>
            <span className="block text-[10px] text-stone-400 font-mono mt-0.5">Dialing: +30 210 555 7777</span>
            <span className="block text-[9px] text-[#B91C1C] font-bold mt-1 animate-pulse">Sizzling & ringing...</span>
          </div>
        )}
      </div>

    </div>
  );
}
