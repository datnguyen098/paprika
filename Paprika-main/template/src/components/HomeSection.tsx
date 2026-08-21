import { Flame, ArrowRight, ShieldCheck, Clock, MapPin, Sparkles, Copy, Check, Calendar } from 'lucide-react';
import { MenuItem } from '../types';
import { MENU_ITEMS, SPECIAL_OFFERS } from '../data/menu';
import { useState } from 'react';

interface HomeSectionProps {
  setCurrentPage: (page: string) => void;
  onAddToCart: (item: MenuItem) => void;
  onApplyPromoCode: (code: string) => void;
  appliedPromo: string;
}

export default function HomeSection({
  setCurrentPage,
  onAddToCart,
  onApplyPromoCode,
  appliedPromo
}: HomeSectionProps) {
  const [copiedCode, setCopiedCode] = useState<string | null>(null);

  const bestSellers = MENU_ITEMS.filter((item) => item.isBestSeller).slice(0, 3);

  const handleCopyCode = (code: string) => {
    navigator.clipboard.writeText(code);
    setCopiedCode(code);
    onApplyPromoCode(code);
    setTimeout(() => setCopiedCode(null), 3000);
  };

  return (
    <div className="bg-[#FDFBF7] text-stone-900" id="home-section-container">
      
      {/* 1. PROFESSIONAL POLISHED HERO SECTION */}
      <section className="relative min-h-[360px] bg-[#064E3B] flex items-center px-6 sm:px-12 py-12 md:py-16 overflow-hidden border-b border-[#043427]">
        <div className="z-10 max-w-2xl relative w-full">
          <span className="bg-[#B91C1C] text-white text-[10px] font-bold px-3 py-1 rounded uppercase mb-4 inline-block tracking-widest">
            Limited Time Offer
          </span>
          
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-black text-white leading-none mb-4 italic uppercase tracking-tighter font-heading">
            THE BLAZING <br />
            <span className="text-[#FFD700]">CHILI STACK</span>
          </h1>
          
          <p className="text-white/85 text-sm sm:text-base mb-6 max-w-md leading-relaxed font-sans">
            Triple-flame grilled beef, secret garden chili sauce, and melting cheddar cheese on a toasted artisan sourdough bun. Crafted fresh daily in Athens.
          </p>
          
          <div className="flex flex-wrap gap-4">
            <button 
              onClick={() => setCurrentPage('menu')}
              className="bg-[#B91C1C] hover:bg-[#991B1B] text-white font-extrabold px-8 py-3.5 rounded-full transition-all text-xs uppercase tracking-widest shadow-lg shadow-black/20 hover:scale-[1.02]"
            >
              Order Now
            </button>
            <button 
              onClick={() => setCurrentPage('menu')}
              className="border-2 border-white/30 hover:border-white/50 text-white font-extrabold px-6 py-3.5 rounded-full text-xs uppercase tracking-widest transition-all hover:bg-white/5"
            >
              View Menu
            </button>
            <button 
              onClick={() => setCurrentPage('booking')}
              className="bg-[#FFD700] hover:bg-[#e0be00] text-stone-950 font-extrabold px-6 py-3.5 rounded-full text-xs uppercase tracking-widest transition-all shadow hover:scale-[1.02]"
            >
              Book Table
            </button>
          </div>

          {/* Core Trust Counters */}
          <div className="flex items-center gap-6 mt-8 pt-6 border-t border-white/10 text-white/80">
            <div>
              <span className="block text-white font-black text-base leading-none">100%</span>
              <span className="text-[10px] uppercase font-bold tracking-wider text-white/60">Bio Beef</span>
            </div>
            <div className="w-px h-6 bg-white/10"></div>
            <div>
              <span className="block text-white font-black text-base leading-none"> Athens</span>
              <span className="text-[10px] uppercase font-bold tracking-wider text-white/60">Locally Sourced</span>
            </div>
            <div className="w-px h-6 bg-white/10"></div>
            <div>
              <span className="block text-white font-black text-base leading-none">&euro;0.00</span>
              <span className="text-[10px] uppercase font-bold tracking-wider text-white/60">Free Pickups</span>
            </div>
          </div>
        </div>

        {/* Wireframe design circles */}
        <div className="absolute right-[-45px] top-[-45px] opacity-20 pointer-events-none">
          <div className="w-[450px] h-[450px] border-[32px] border-white/10 rounded-full"></div>
        </div>

        {/* Right side floating gourmet image illustration */}
        <div className="absolute right-12 bottom-0 top-0 hidden lg:flex items-center">
          <div className="w-72 h-72 bg-gray-300 rounded-full border-[8px] border-white/10 shadow-2xl overflow-hidden flex items-center justify-center bg-[url('https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=450')] bg-cover hover:scale-105 transition-transform duration-500">
          </div>
        </div>
      </section>

      {/* 2. SPECIAL ACTIVE OFFERS ROW */}
      <section className="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto -mt-6 relative z-20">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {SPECIAL_OFFERS.map((offer) => {
            const isApplied = appliedPromo === offer.code;
            return (
              <div 
                key={offer.id}
                className={`p-5 rounded-2xl border transition duration-300 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 ${
                  isApplied 
                    ? 'bg-emerald-50 border-emerald-300 shadow-emerald-100' 
                    : 'bg-white border-stone-200 hover:border-[#064E3B]/40'
                }`}
              >
                <div className="space-y-1.5 max-w-md">
                  <div className="flex gap-2 items-center">
                    <span className="bg-[#B91C1C]/10 text-[#B91C1C] text-[9px] font-extrabold uppercase tracking-widest px-2.5 py-0.5 rounded">
                      {offer.badge}
                    </span>
                    {offer.discount > 0 && (
                      <span className="bg-[#064E3B] text-white text-[9px] font-bold px-2 py-0.5 rounded">
                        Save {offer.discount}%
                      </span>
                    )}
                  </div>
                  <h3 className="font-extrabold text-stone-900 text-base font-sans leading-tight">
                    {offer.title}
                  </h3>
                  <p className="text-stone-500 text-xs leading-relaxed font-sans">
                    {offer.subtitle}
                  </p>
                </div>

                <button
                  onClick={() => handleCopyCode(offer.code)}
                  className={`w-full sm:w-auto shrink-0 px-4 py-2 rounded-full font-bold text-[10px] uppercase tracking-widest transition duration-200 flex items-center justify-center gap-1.5 ${
                    isApplied 
                      ? 'bg-emerald-700 hover:bg-emerald-850 text-white' 
                      : 'bg-[#064E3B] hover:bg-[#033427] text-white'
                  }`}
                >
                  {isApplied ? (
                    <>
                      <Check className="w-3.5 h-3.5 text-emerald-300" />
                      Code Applied
                    </>
                  ) : copiedCode === offer.code ? (
                    <>
                      <Check className="w-3.5 h-3.5" />
                      Applying...
                    </>
                  ) : (
                    <>
                      <Copy className="w-3.5 h-3.5" />
                      Use {offer.code}
                    </>
                  )}
                </button>
              </div>
            );
          })}
        </div>
      </section>

      {/* 3. BEST SELLERS SECTION */}
      <section className="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div className="text-center space-y-2 mb-10">
          <span className="text-[#B91C1C] font-black uppercase text-xs tracking-widest font-heading">
            Best Sellers
          </span>
          <h2 className="text-3xl sm:text-4xl font-extrabold text-stone-950 tracking-tight font-sans italic uppercase">
            Signature Recipes
          </h2>
          <p className="text-stone-500 text-sm max-w-lg mx-auto">
            Gourmet fire-grilled creations assembled with fresh certified garden ingredients and dry-aged delicacies.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {bestSellers.map((item) => (
            <div 
              key={item.id}
              className="bg-white rounded-2xl p-4 shadow-sm border border-stone-200/60 flex flex-col transition-all hover:shadow-md hover:border-stone-300 group"
            >
              <div className="h-44 sm:h-48 bg-stone-100 rounded-xl mb-4 overflow-hidden relative shrink-0">
                <img
                  src={item.image}
                  alt={item.name}
                  className="w-full h-full object-cover transition duration-500 group-hover:scale-105"
                  referrerPolicy="no-referrer"
                />
                
                {item.prepTime && (
                  <span className="absolute top-2.5 left-2.5 bg-[#064E3B]/90 text-white text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider">
                    {item.prepTime}
                  </span>
                )}

                {item.spicyLevel > 0 && (
                  <div className="absolute bottom-2.5 right-2.5 flex gap-0.5 bg-white/70 backdrop-blur-sm px-1.5 py-0.5 rounded-full">
                    {Array.from({ length: item.spicyLevel }).map((_, sIdx) => (
                      <Flame key={sIdx} className="w-3.5 h-3.5 text-[#B91C1C] fill-[#B91C1C]" />
                    ))}
                  </div>
                )}
              </div>

              <h3 className="font-black uppercase text-sm mb-1 italic text-stone-900 group-hover:text-[#064E3B] transition-colors">
                {item.name}
              </h3>
              
              <p className="text-[11px] text-stone-500 mb-4 line-clamp-2 leading-relaxed">
                {item.description}
              </p>

              <div className="mt-auto pt-3 border-t border-stone-100 flex items-center justify-between">
                <div className="flex flex-col">
                  <span className="text-[9px] uppercase tracking-wider font-bold text-stone-400">Price</span>
                  <span className="font-black text-lg text-[#064E3B]">
                    &euro;{item.price.toFixed(2)}
                  </span>
                </div>
                
                <button
                  onClick={() => onAddToCart(item)}
                  className="bg-[#064E3B] hover:bg-[#B91C1C] cursor-pointer text-white w-9 h-9 rounded-full flex items-center justify-center font-extrabold text-sm transition-colors shadow-sm hover:scale-105"
                  title="Add to Basket"
                >
                  +
                </button>
              </div>
            </div>
          ))}
        </div>

        {/* View full menu redirection */}
        <div className="text-center mt-10">
          <button
            onClick={() => setCurrentPage('menu')}
            className="inline-flex items-center gap-2 px-8 py-3 bg-[#064E3B] hover:bg-[#B91C1C] text-white text-xs font-bold uppercase tracking-widest rounded-full transition-colors shadow-md shadow-emerald-900/10"
          >
            <span>Explore The Full Menu</span>
            <ArrowRight className="w-3.5 h-3.5 text-white" />
          </button>
        </div>

        {/* Interactive Storyteller Prompt Banner */}
        <div className="mt-16 bg-gradient-to-r from-[#042C21] to-[#064E3B] rounded-3xl p-6 sm:p-10 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl border border-white/5">
          <div className="space-y-2 max-w-lg text-left">
            <span className="text-[#FFD700] text-[10px] uppercase font-black tracking-widest block">Vietnamese Heritage & Greek Spirit</span>
            <h3 className="text-xl sm:text-2xl font-black uppercase italic tracking-tight font-heading">Hương Vị Địa Trung Hải Giao Thoa Ớt Cay Bùng Nổ</h3>
            <p className="text-white/80 text-xs leading-relaxed font-sans">
              Verde Chilli là kết tinh tinh túy từ thảo mộc thanh sạch Địa Trung Hải kết hợp cùng bí quyết tẩm ướp cay nồng tươi mới của người sáng lập. Hãy khám phá hành trình đầy cảm hứng của chúng tôi.
            </p>
          </div>
          <button
            onClick={() => setCurrentPage('about')}
            className="w-full md:w-auto shrink-0 bg-[#B91C1C] hover:bg-[#991B1B] text-white font-extrabold px-6 py-3.5 rounded-xl uppercase text-[10px] tracking-widest transition-all h-fit cursor-pointer hover:scale-[1.02]"
          >
            Đọc Câu Chuyện Của Chúng Tôi
          </button>
        </div>
      </section>

      {/* 4. WORKFLOW VALUES SECTION */}
      <section className="bg-[#F9F7F2] border-t border-stone-200/60 py-16 px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto space-y-12">
          <div className="text-center space-y-1">
            <span className="text-[#064E3B] font-bold text-xs uppercase tracking-widest block">Premium Service</span>
            <h2 className="text-2xl sm:text-3xl font-black text-stone-950 tracking-tight font-sans uppercase italic">
              Fulfilled Your Way
            </h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {/* Value card 1 */}
            <div className="bg-white p-6 rounded-2xl border border-stone-200/50 shadow-sm flex flex-col items-center text-center space-y-3">
              <div className="w-10 h-10 bg-[#B91C1C]/10 text-[#B91C1C] rounded-full flex items-center justify-center">
                <MapPin className="w-5 h-5" />
              </div>
              <h3 className="font-extrabold text-stone-900 text-sm uppercase">1. Athens Hot Delivery</h3>
              <p className="text-xs text-stone-500 leading-relaxed max-w-xs">
                Packaged carefully in optimized sealed insulation boxes to retain exact sizzling grill standards when handed over.
              </p>
            </div>

            {/* Value card 2 */}
            <div className="bg-white p-6 rounded-2xl border border-stone-200/50 shadow-sm flex flex-col items-center text-center space-y-3">
              <div className="w-10 h-10 bg-[#064E3B]/10 text-[#064E3B] rounded-full flex items-center justify-center">
                <ShieldCheck className="w-5 h-5" />
              </div>
              <h3 className="font-extrabold text-stone-900 text-sm uppercase">2. Bypass Queues</h3>
              <p className="text-xs text-stone-500 leading-relaxed max-w-xs">
                Draft order online, select physical pickup counter, and bypass standard waiting lines. Fully contact-free setup.
              </p>
            </div>

            {/* Value card 3 */}
            <div className="bg-white p-6 rounded-2xl border border-stone-200/50 shadow-sm flex flex-col items-center text-center space-y-3">
              <div className="w-10 h-10 bg-[#FFD700]/20 text-[#064E3B] rounded-full flex items-center justify-center">
                <Clock className="w-5 h-5" />
              </div>
              <h3 className="font-extrabold text-stone-900 text-sm uppercase">3. Table Reservations</h3>
              <p className="text-xs text-stone-500 leading-relaxed max-w-xs">
                Schedule dining spots directly. Savor delicious grilled meats hot right from our active fireplace grills in absolute safety.
              </p>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
