import React, { useState } from 'react';
import { Mail, Flame, Phone, MapPin, Clock, ArrowRight } from 'lucide-react';

interface FooterProps {
  setCurrentPage: (page: string) => void;
}

export default function Footer({ setCurrentPage }: FooterProps) {
  const [email, setEmail] = useState('');
  const [subscribed, setSubscribed] = useState(false);

  const handleSubscribe = (e: React.FormEvent) => {
    e.preventDefault();
    if (email.trim() && email.includes('@')) {
      setSubscribed(true);
      setEmail('');
      setTimeout(() => setSubscribed(false), 5000);
    }
  };

  return (
    <footer className="bg-[#032219] text-[#EFECE6] border-t-8 border-[#B91C1C]" id="app-footer">
      {/* Top Banner: Contact & Slogan */}
      <div className="bg-[#042C21] py-6 border-b border-white/5">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="p-2 bg-[#B91C1C]/10 rounded-lg text-[#B91C1C]">
              <Phone className="w-5 h-5 text-[#B91C1C]" />
            </div>
            <div>
              <span className="block text-xs uppercase tracking-wider text-[#A2C7B4] font-medium">Quick Delivery Hotline</span>
              <span className="text-lg font-bold text-white font-mono">+30 210 555 7777</span>
            </div>
          </div>
          <p className="text-sm text-[#A2C7B4] text-center md:text-right max-w-md font-sans">
            Crafted for spice lovers, combining organic farm-fresh ingredients with premium culinary heat.
          </p>
        </div>
      </div>

      {/* Main Grid Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
          
          {/* Col 1: Brand Info */}
          <div className="space-y-4">
            <div className="flex items-center gap-2">
              <div className="bg-[#B91C1C] p-1.5 rounded-lg">
                <Flame className="w-5 h-5 text-white" />
              </div>
              <span className="font-extrabold text-lg tracking-tight font-sans italic uppercase">
                VERDE <span className="text-[#FFD700]">CHILI</span>
              </span>
            </div>
            <p className="text-sm text-[#B5CFB7] leading-relaxed">
              We specialize in fire-grilled gourmet chicken, custom flame-kissed brioche buns, and house-made green chimichurri dips. Inspired by premium Greek local herbs and rich chili accents.
            </p>
            <div className="flex gap-2.5 pt-2">
              {['facebook', 'instagram', 'tripadvisor', 'tiktok'].map((social) => (
                <span 
                  key={social} 
                  className="w-8 h-8 rounded-full bg-[#064E3B] flex items-center justify-center text-xs text-[#EFECE6] font-mono hover:bg-[#B91C1C] hover:text-white transition cursor-pointer"
                  title={social}
                >
                  {social[0].toUpperCase()}
                </span>
              ))}
            </div>
          </div>

          {/* Col 2: Quick Links */}
          <div>
            <h3 className="text-white text-xs uppercase tracking-widest font-black mb-5 border-b border-white/10 pb-2">
              Explore Our Store
            </h3>
            <ul className="space-y-3 text-xs text-[#B5CFB7] uppercase tracking-wider font-extrabold">
              {['home', 'menu', 'about', 'booking'].map((page) => (
                <li key={page}>
                  <button
                    onClick={() => setCurrentPage(page)}
                    className="hover:text-white transition flex items-center gap-1.5 animate-fadeIn"
                  >
                    <ArrowRight className="w-3.5 h-3.5 text-[#B91C1C]" />
                    {page === 'home' && 'Welcome Home'}
                    {page === 'menu' && 'Our Hot Menu'}
                    {page === 'about' && 'About Us (Câu chuyện Verde)'}
                    {page === 'booking' && 'Reserve A Table'}
                  </button>
                </li>
              ))}
              <li>
                <span className="text-[#B5CFB7]/40 block mt-2 font-sans normal-case font-normal">Greek Registered LLC. Delivery within Athens Metropolitan zones.</span>
              </li>
            </ul>
          </div>

          {/* Col 3: Typical Hours / Info */}
          <div className="space-y-4">
            <h3 className="text-white text-xs uppercase tracking-widest font-black mb-5 border-b border-white/10 pb-2 font-heading">
              Service Operations
            </h3>
            <div className="flex gap-3 text-xs text-[#B5CFB7]">
              <Clock className="w-5 h-5 text-[#FFD700] shrink-0 mt-0.5" />
              <div>
                <span className="block font-semibold text-white uppercase tracking-wider mb-0.5">Opening Times</span>
                <span className="block">Monday - Thursday: 11:30 - 23:30</span>
                <span className="block">Friday - Sunday: 11:00 - 01:00</span>
              </div>
            </div>
            <div className="flex gap-3 text-xs text-[#B5CFB7]">
              <MapPin className="w-5 h-5 text-[#FFD700] shrink-0 mt-0.5" />
              <div>
                <span className="block font-semibold text-white uppercase tracking-wider mb-0.5">Main Depot</span>
                <span className="block">Syntagma Central Hub, Athens</span>
              </div>
            </div>
          </div>

          {/* Col 4: Newsletter */}
          <div className="space-y-4">
            <h3 className="text-white text-xs uppercase tracking-widest font-black mb-5 border-b border-white/10 pb-2 font-heading">
              Join the Hotlist
            </h3>
            <p className="text-xs text-[#B5CFB7] leading-relaxed">
              Subscribe to unlock double-discount promo codes and secret weekend popups.
            </p>
            <form onSubmit={handleSubscribe} className="space-y-2">
              <div className="relative">
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="Your premium email Address..."
                  required
                  className="w-full bg-[#042C21] border border-white/10 rounded-xl px-4 py-3 text-xs text-white placeholder-emerald-900 focus:outline-none focus:ring-1 focus:ring-[#B91C1C] focus:border-transparent transition"
                />
                <button
                  type="submit"
                  className="absolute right-2 top-2 bg-[#B91C1C] hover:bg-[#991B1B] p-1.5 rounded-lg transition"
                >
                  <Mail className="w-4 h-4 text-white" />
                </button>
              </div>
              {subscribed && (
                <div className="p-2 bg-[#B91C1C]/15 border border-[#B91C1C]/30 rounded-lg text-xs text-[#ff5f7d] animate-slideIn font-bold">
                  🔥 Club joined! Code: <strong>WELCOME15</strong>
                </div>
              )}
            </form>
          </div>

        </div>

        {/* Bottom copyright stamp */}
        <div className="border-t border-white/5 mt-16 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-center">
          <p className="text-xs text-[#B5CFB7]/50">
            &copy; {new Date().getFullYear()} Verde Chili Greece. All Rights Reserved. Real gourmet heat.
          </p>
          <div className="flex gap-4 text-xs text-[#B5CFB7]/40">
            <span className="hover:text-white transition cursor-pointer">Privacy Policy</span>
            <span>&bull;</span>
            <span className="hover:text-white transition cursor-pointer">Terms of Service</span>
            <span>&bull;</span>
            <span className="hover:text-white transition cursor-pointer">Allergens Guide</span>
          </div>
        </div>
      </div>
    </footer>
  );
}
