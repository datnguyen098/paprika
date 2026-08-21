import React, { useState, useTransition } from 'react';
import { Search, Flame, Sparkles, Check, RefreshCw, Layers, ShieldCheck } from 'lucide-react';
import { MenuItem, MenuItemCategory } from '../types';
import { MENU_ITEMS, MENU_CATEGORIES } from '../data/menu';
import ProductDetailModal from './ProductDetailModal';

interface MenuSectionProps {
  onAddToCart: (item: MenuItem, customization?: string, quantity?: number) => void;
  activeCategory: MenuItemCategory;
  setActiveCategory: (category: MenuItemCategory) => void;
}

export default function MenuSection({
  onAddToCart,
  activeCategory,
  setActiveCategory
}: MenuSectionProps) {
  const [searchQuery, setSearchQuery] = useState('');
  const [spicyFilter, setSpicyFilter] = useState<'all' | 0 | 1 | 2 | 3>('all');
  const [dietFilter, setDietFilter] = useState<'all' | 'vegan' | 'vegetarian' | 'glutenfree'>('all');
  const [isPending, startTransition] = useTransition();

  // Customization modal/drawer trigger
  const [selectedCustomizingItem, setSelectedCustomizingItem] = useState<MenuItem | null>(null);

  const filteredItems = MENU_ITEMS.filter((item) => {
    // 1. Category
    if (item.category !== activeCategory) return false;
    
    // 2. Search query
    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      const matchName = item.name.toLowerCase().includes(query);
      const matchDesc = item.description.toLowerCase().includes(query);
      if (!matchName && !matchDesc) return false;
    }

    // 3. Spicy level
    if (spicyFilter !== 'all' && item.spicyLevel !== spicyFilter) return false;

    // 4. Diet filters
    if (dietFilter === 'vegan' && !item.isVegan) return false;
    if (dietFilter === 'vegetarian' && !item.isVegetarian) return false;
    if (dietFilter === 'glutenfree' && !item.isGlutenFree) return false;

    return true;
  });

  const handleResetFilters = () => {
    startTransition(() => {
      setSearchQuery('');
      setSpicyFilter('all');
      setDietFilter('all');
    });
  };

  const openCustomizer = (item: MenuItem) => {
    setSelectedCustomizingItem(item);
  };

  return (
    <div className="bg-[#FDFBF7] text-[#1A1A1A] min-h-screen" id="menu-section-container">
      
      {/* 1. SECTOR DETAILS HEADER */}
      <section className="bg-[#064E3B] text-white py-12 px-6 sm:px-12 border-b border-[#043427]">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <div className="space-y-1">
            <span className="bg-[#B91C1C] text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded">
              Hand-Pressed & Fire-Grilled
            </span>
            <h1 className="text-3xl sm:text-4xl font-black tracking-tight leading-none italic uppercase font-heading">
              Gourmet Menu
            </h1>
            <p className="text-white/80 text-xs sm:text-sm max-w-lg leading-relaxed">
              Every recipe is assembled from custom bio-certified ingredients, seasoned with premium olive oils and cooked fresh to order.
            </p>
          </div>

          {/* Clean Quick Metrics tag */}
          <div className="flex gap-4 sm:gap-6 bg-black/15 p-4 rounded-xl border border-white/10 shrink-0">
            <div className="text-center px-1">
              <span className="block text-xl font-black text-[#FFD700]">14+</span>
              <span className="block text-[9px] text-white/70 uppercase font-bold tracking-wider">Dishes</span>
            </div>
            <div className="border-l border-white/10" />
            <div className="text-center px-1">
              <span className="block text-xl font-black text-[#FFD700]">3 Min</span>
              <span className="block text-[9px] text-white/70 uppercase font-bold tracking-wider">Avg Fry Heat</span>
            </div>
            <div className="border-l border-white/10" />
            <div className="text-center px-1">
              <span className="block text-xl font-black text-emerald-300">Fast</span>
              <span className="block text-[9px] text-white/70 uppercase font-bold tracking-wider">Packaging</span>
            </div>
          </div>
        </div>
      </section>

      {/* Categories Horizontal Pill Selection Menu */}
      <nav className="border-b border-stone-200 bg-white shadow-xs py-3 px-6 sm:px-12 flex gap-3 overflow-x-auto no-scrollbar z-10 sticky top-20">
        <div className="max-w-7xl mx-auto w-full flex items-center gap-2">
          <span className="text-[10px] uppercase font-black text-stone-400 tracking-wider hidden lg:inline mr-2 shrink-0">Categories:</span>
          {MENU_CATEGORIES.map((cat) => {
            const isSelected = activeCategory === cat.id;
            return (
              <button
                key={cat.id}
                onClick={() => setActiveCategory(cat.id)}
                className={`px-5 py-2 rounded-full text-xs font-bold uppercase tracking-widest flex-shrink-0 transition-all ${
                  isSelected
                    ? 'bg-[#064E3B] text-white shadow-sm'
                    : 'bg-stone-100 hover:bg-stone-200 text-stone-600'
                }`}
              >
                {cat.label}
              </button>
            );
          })}
        </div>
      </nav>

      {/* 2. DYNAMIC MAIN BODY */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" id="interactive-menu-grid">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          {/* FILTERING OPTIONS DRAWER BOX */}
          <div className="lg:col-span-3 space-y-4">
            
            {/* Search */}
            <div className="bg-white p-4 rounded-xl border border-stone-200/80 shadow-xs space-y-2">
              <label htmlFor="menu-search-input" className="block text-[10px] uppercase font-bold text-stone-400 tracking-widest">
                Search Menu
              </label>
              <div className="relative">
                <input
                  id="menu-search-input"
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="e.g. Chili burger, combos..."
                  className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-lg pl-9 pr-3 py-2 text-xs outline-none transition"
                />
                <Search className="w-3.5 h-3.5 text-stone-400 absolute left-3 top-3" />
              </div>
            </div>

            {/* Custom Advanced Filters */}
            <div className="bg-white rounded-xl border border-stone-200/80 p-4 shadow-xs space-y-4">
              
              {/* Spicy level button-based toggles */}
              <div className="space-y-2">
                <h4 className="text-[10px] uppercase font-bold text-stone-400 tracking-widest">
                  Spicy Heat Filter
                </h4>
                <div className="grid grid-cols-2 gap-1.5">
                  <button
                    onClick={() => setSpicyFilter('all')}
                    className={`py-1.5 px-2 text-center rounded-lg text-xs font-bold border transition ${
                      spicyFilter === 'all'
                        ? 'bg-[#064E3B] text-white border-[#064E3B]'
                        : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100'
                    }`}
                  >
                    All Types
                  </button>
                  {[0, 1, 2, 3].map((level) => (
                    <button
                      key={level}
                      onClick={() => setSpicyFilter(level as any)}
                      className={`py-1.5 px-1.5 text-center rounded-lg text-xs font-bold border transition flex items-center justify-center gap-0.5 ${
                        spicyFilter === level
                          ? 'bg-[#B91C1C] text-white border-[#B91C1C]'
                          : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100'
                      }`}
                    >
                      {level === 0 ? 'Mild' : Array.from({ length: level }).map((_, i) => '🔥')}
                    </button>
                  ))}
                </div>
              </div>

              {/* Diet filter keys */}
              <div className="space-y-2">
                <h4 className="text-[10px] uppercase font-bold text-stone-400 tracking-widest">
                  Dietary / Lifestyle
                </h4>
                <div className="flex flex-col gap-1">
                  {[
                    { id: 'all', label: 'Show All Items' },
                    { id: 'vegetarian', label: 'Vegetarian Only' },
                    { id: 'vegan', label: 'Vegan Only' },
                    { id: 'glutenfree', label: 'Gluten-Free Only' }
                  ].map((diet) => (
                    <button
                      key={diet.id}
                      onClick={() => setDietFilter(diet.id as any)}
                      className={`text-left px-3 py-1.5 rounded-lg text-xs font-bold border transition flex items-center gap-2 ${
                        dietFilter === diet.id
                          ? 'bg-[#064E3B]/10 text-[#064E3B] border-[#064E3B]'
                          : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100'
                      }`}
                    >
                      <span className={`w-2 h-2 rounded-full ${
                        dietFilter === diet.id ? 'bg-[#064E3B]' : 'bg-stone-300'
                      }`} />
                      {diet.label}
                    </button>
                  ))}
                </div>
              </div>

              {/* Reset shortcut */}
              {(searchQuery || spicyFilter !== 'all' || dietFilter !== 'all') && (
                <button
                  onClick={handleResetFilters}
                  className="w-full py-2 bg-stone-100 hover:bg-[#B91C1C] hover:text-white text-stone-600 text-[10px] font-black uppercase tracking-widest rounded-lg transition flex items-center justify-center gap-1"
                >
                  <RefreshCw className="w-3 h-3 animate-spin duration-1000" />
                  Clear Filters
                </button>
              )}
            </div>
          </div>

          {/* MAIN MENU DISHES GRID */}
          <div className="lg:col-span-9 space-y-4">
            <div className="flex items-center justify-between border-b border-stone-200 pb-3">
              <span className="text-[10px] font-black text-[#064E3B] uppercase tracking-wider">
                Category: {MENU_CATEGORIES.find(c => c.id === activeCategory)?.label || activeCategory}
              </span>
              <span className="text-xs text-stone-500 font-medium">
                Matches: <strong>{filteredItems.length}</strong> Gourmet Recipes
              </span>
            </div>

            {/* Empty result view */}
            {filteredItems.length === 0 ? (
              <div className="bg-white border border-stone-200 p-12 rounded-3xl text-center space-y-4 shadow-sm">
                <div className="w-12 h-12 bg-stone-100 text-stone-400 mx-auto rounded-full flex items-center justify-center">
                  <Layers className="w-6 h-6" />
                </div>
                <h3 className="font-extrabold text-stone-850 text-base">No recipes fit the search criteria</h3>
                <p className="text-stone-450 text-xs max-w-sm mx-auto leading-relaxed">
                  Try typing a different name keyword, selecting alternative categories, or resetting active spice level controls.
                </p>
                <button
                  onClick={handleResetFilters}
                  className="px-5 py-2 bg-[#064E3B] hover:bg-[#B91C1C] text-white text-[10px] font-black uppercase tracking-widest rounded-full transition shadow"
                >
                  Clear Active Filters
                </button>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="menu-items-cards-grid">
                {filteredItems.map((item) => (
                  <div 
                    key={item.id}
                    className="bg-white rounded-2xl p-4 shadow-sm border border-stone-100 flex flex-col group transition-all hover:shadow-md hover:border-stone-200"
                  >
                    {/* Gourmet Image Box */}
                    <div className="h-40 bg-stone-100 rounded-xl mb-4 overflow-hidden relative shrink-0">
                      <img
                        src={item.image}
                        alt={item.name}
                        className="w-full h-full object-cover transition duration-500 group-hover:scale-105"
                        referrerPolicy="no-referrer"
                      />
                      
                      {/* Bestseller/Chef tag */}
                      {item.isChefSpecial ? (
                        <span className="absolute top-2 left-2 bg-[#B91C1C] text-white text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider">
                          Chef Pick
                        </span>
                      ) : item.isBestSeller ? (
                        <span className="absolute top-2 left-2 bg-[#064E3B] text-white text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider">
                          Popular
                        </span>
                      ) : null}

                      {/* Calorie info */}
                      <span className="absolute bottom-2 left-2 bg-stone-900/75 text-white text-[8px] font-mono px-1.5 py-0.5 rounded">
                        {item.calories} kCal
                      </span>
                    </div>

                    <h3 className="font-black uppercase text-sm mb-1 italic text-stone-900 group-hover:text-[#064E3B] transition-colors leading-tight">
                      {item.name}
                    </h3>
                    
                    <p className="text-[11px] text-stone-500 mb-4 line-clamp-2 leading-relaxed">
                      {item.description}
                    </p>

                    <div className="mt-auto pt-3 border-t border-stone-105 flex items-center justify-between">
                      <div className="flex flex-col">
                        <span className="text-[8px] uppercase tracking-wider font-extrabold text-stone-400">Price</span>
                        <span className="font-black text-lg text-[#064E3B]">
                          &euro;{item.price.toFixed(2)}
                        </span>
                      </div>
                      
                      <button
                        onClick={() => openCustomizer(item)}
                        className="bg-[#064E3B] text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-[#B91C1C] transition-colors font-extrabold text-sm shadow-sm"
                        title="Add to Basket"
                      >
                        +
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </section>

      {/* DYNAMIC INTEGRATED PRODUCT DETAIL DETAILS MODAL (KFC.GR COMPATIBLE) */}
      {selectedCustomizingItem && (
        <ProductDetailModal
          item={selectedCustomizingItem}
          onClose={() => setSelectedCustomizingItem(null)}
          onAddToCart={onAddToCart}
        />
      )}

    </div>
  );
}
