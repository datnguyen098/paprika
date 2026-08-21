import { useState, startTransition } from 'react';
import { ShoppingBag, Menu, X, Calendar, Flame, MapPin } from 'lucide-react';

interface HeaderProps {
  currentPage: string;
  setCurrentPage: (page: string) => void;
  cartItemsCount: number;
  onOpenCart: () => void;
  serviceType: 'delivery' | 'pickup' | 'dinein';
  setServiceType: (type: 'delivery' | 'pickup' | 'dinein') => void;
}

export default function Header({
  currentPage,
  setCurrentPage,
  cartItemsCount,
  onOpenCart,
  serviceType,
  setServiceType
}: HeaderProps) {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const navItems = [
    { id: 'home', label: 'Home' },
    { id: 'menu', label: 'Our Menu' },
    { id: 'about', label: 'About Us' },
    { id: 'booking', label: 'Book a Table' }
  ];

  const handleNavClick = (pageId: string) => {
    startTransition(() => {
      setCurrentPage(pageId);
    });
    setMobileMenuOpen(false);
  };

  return (
    <header className="sticky top-0 z-40 bg-[#064E3B] text-white shadow-lg border-b border-[#043427]">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-20">
          {/* Logo Brand */}
          <div 
            onClick={() => handleNavClick('home')} 
            className="flex items-center gap-2 cursor-pointer group"
            id="header-logo-container"
          >
            <div className="text-2xl font-black italic tracking-tighter flex items-center gap-1.5 font-heading">
              <span className="bg-[#B91C1C] text-white px-2.5 py-1 rounded inline-block transition group-hover:scale-105 shadow-md">VERDE</span>
              <span className="text-white">CHILLI</span>
            </div>
          </div>

          {/* Desktop Navigation */}
          <nav className="hidden md:flex items-center space-x-6 text-sm font-semibold uppercase tracking-wider opacity-90" id="desktop-nav">
            {navItems.map((item) => {
              const isActive = currentPage === item.id;
              return (
                <button
                  key={item.id}
                  onClick={() => handleNavClick(item.id)}
                  id={`nav-item-${item.id}`}
                  className={`py-1 text-xs uppercase tracking-widest font-bold border-b-2 transition-all ${
                    isActive
                      ? 'border-[#B91C1C] text-white'
                      : 'border-transparent text-white/80 hover:text-white hover:border-white/50'
                  }`}
                >
                  {item.label}
                </button>
              );
            })}
          </nav>

          {/* Fulfillment Toggle Selector */}
          <div className="hidden lg:flex items-center bg-black/15 p-1 rounded-full border border-white/10" id="fulfillment-toggle-desktop">
            <button
              onClick={() => setServiceType('delivery')}
              className={`px-4 py-1.5 rounded-full text-[10px] uppercase tracking-widest font-bold transition ${
                serviceType === 'delivery' 
                  ? 'bg-white text-[#064E3B] shadow-md' 
                  : 'text-white/85 hover:text-white'
              }`}
            >
              Delivery
            </button>
            <button
              onClick={() => setServiceType('pickup')}
              className={`px-4 py-1.5 rounded-full text-[10px] uppercase tracking-widest font-bold transition ${
                serviceType === 'pickup' 
                  ? 'bg-white text-[#064E3B] shadow-md' 
                  : 'text-white/85 hover:text-white'
              }`}
            >
              Pick Up
            </button>
          </div>

          {/* Right Action Widgets */}
          <div className="flex items-center gap-4">
            {/* Delivery address info matching layout template */}
            <div className="hidden sm:flex flex-col items-end text-[10px] uppercase tracking-widest opacity-85">
              <span className="text-white/70">Deliver to:</span>
              <span className="font-extrabold text-white text-right">Athens, GR</span>
            </div>

            {/* Quick reservation CTA desktop */}
            <button
              onClick={() => handleNavClick('booking')}
              className="hidden lg:flex items-center gap-1.5 px-5 py-2.5 bg-white/15 hover:bg-white/25 uppercase text-[10px] font-bold tracking-widest rounded-full transition border border-white/20"
            >
              <Calendar className="w-3.5 h-3.5 text-white/80" />
              Book Table
            </button>

            {/* Cart Button with Professional Red Accent */}
            <button
              onClick={onOpenCart}
              id="cart-btn"
              className="relative flex items-center gap-2 px-4 py-2.5 bg-[#B91C1C] hover:bg-[#991B1B] rounded-full shadow-md transition group border border-transparent"
            >
              <ShoppingBag className="w-4 h-4 text-white group-hover:scale-105 transition" />
              <span className="hidden sm:inline text-xs uppercase tracking-widest font-extrabold text-white">Basket</span>
              {cartItemsCount > 0 && (
                <span 
                  id="header-cart-badge"
                  className="absolute -top-1.5 -right-1.5 bg-white text-[#B91C1C] text-[10px] font-black h-5 min-w-5 px-1 flex items-center justify-center rounded-full border border-[#B91C1C] shadow-lg"
                >
                  {cartItemsCount}
                </span>
              )}
            </button>

            {/* Mobile menu trigger */}
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="md:hidden p-2 rounded-full text-white hover:bg-white/10"
              aria-label="Toggle menu"
            >
              {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Drawer Dropdown */}
      {mobileMenuOpen && (
        <div className="md:hidden bg-[#043427] border-t border-white/10 py-4 px-4 space-y-3 animate-fadeIn">
          <div className="flex flex-col gap-1 px-1">
            {navItems.map((item) => (
              <button
                key={item.id}
                onClick={() => handleNavClick(item.id)}
                className={`w-full text-left px-4 py-3 rounded-xl font-bold uppercase tracking-widest text-xs transition ${
                  currentPage === item.id
                    ? 'bg-[#B91C1C] text-white border-l-4 border-white pl-3'
                    : 'text-white/80 hover:bg-white/10 hover:text-white'
                }`}
              >
                {item.label}
              </button>
            ))}
          </div>

          <hr className="border-white/10" />

          <div className="pt-2 px-1">
            <span className="block text-[10px] uppercase tracking-wider text-white/60 font-bold mb-2">Order Options</span>
            <div className="grid grid-cols-2 gap-2">
              <button
                onClick={() => setServiceType('delivery')}
                className={`p-3 rounded-xl text-center text-[10px] uppercase tracking-widest font-bold flex flex-col items-center gap-1.5 transition ${
                  serviceType === 'delivery' 
                    ? 'bg-[#B91C1C] text-white' 
                    : 'bg-[#064E3B] text-white/85'
                }`}
              >
                <MapPin className="w-4 h-4" />
                Delivery Area
              </button>
              <button
                onClick={() => setServiceType('pickup')}
                className={`p-3 rounded-xl text-center text-[10px] uppercase tracking-widest font-bold flex flex-col items-center gap-1.5 transition ${
                  serviceType === 'pickup' 
                    ? 'bg-[#B91C1C] text-white' 
                    : 'bg-[#064E3B] text-white/85'
                }`}
              >
                <ShoppingBag className="w-4 h-4" />
                Collect Store
              </button>
            </div>
          </div>
        </div>
      )}
    </header>
  );
}
