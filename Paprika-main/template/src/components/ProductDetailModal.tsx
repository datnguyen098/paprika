import React, { useState, useMemo } from 'react';
import { X, Flame, ShieldAlert, Check, Plus, Minus, Info, ClipboardList, HelpCircle } from 'lucide-react';
import { MenuItem } from '../types';

interface ProductDetailModalProps {
  item: MenuItem | null;
  onClose: () => void;
  onAddToCart: (item: MenuItem, customization?: string, quantity?: number) => void;
}

// Typical ingredients based on item category for removal customize feature
const DEFAULT_INGREDIENTS: Record<string, string[]> = {
  combos: ['Double Grilled Patty', 'Brioche Sesame Bun', 'Melted Cheddar Cheese', 'Signature Chili Syrup', 'Lava Sriracha drizzle', 'Pickled Jalapeños', 'Salty Crispy Fries'],
  burgers: ['Toasted Brioche Bun', 'Charcoal Beef Patty', 'Cheddar Leaf', 'Organic Tomatoes', 'Crispy Salad Greens', 'Onion Rings', 'Chili Mayo Spread'],
  chicken: ['Handmade Chicken Breast', 'Proprietary Cayenne Crisp Rub', 'Green Avocado Lime Crema', 'Chimichurri glaze'],
  sides: ['Salty Skin-on Potatoes', 'Cayenne Chili dust', 'Avocado Ranch drizzle', 'Grated Parmesan'],
  desserts: ['Fudge Cocoa Base', 'Warming Cayenne pepper', 'Sweet Key-lime Yogurt cream', 'Whole-wheat cookie crumbs'],
  drinks: ['Cold Squeezed Citrus', 'Torn Green Mint', 'Himalayan Pink salt', 'Chili Pepper float']
};

export default function ProductDetailModal({
  item,
  onClose,
  onAddToCart
}: ProductDetailModalProps) {
  if (!item) return null;

  // Selected quantity
  const [quantity, setQuantity] = useState(1);

  // Excluded ingredients state (No mayonnaise, no onion, etc.)
  const defaultIngredientsList = DEFAULT_INGREDIENTS[item.category] || ['Fresh Ingredients', 'Spiciness Mix'];
  const [excludedIngredients, setExcludedIngredients] = useState<string[]>([]);

  // Selected Extras (Extra cheese, extra beef patty, etc.)
  const [selectedExtras, setSelectedExtras] = useState<{ id: string; name: string; price: number }[]>([]);

  // Active tab inside kfc-style details card
  const [activeTab, setActiveTab] = useState<'customise' | 'nutrition' | 'allergens'>('customise');

  // Available hot toppings / extras
  const EXTRA_TOPPINGS = useMemo(() => [
    { id: 'ext-cheese', name: 'Extra Cheddar Cheese Layer', price: 0.60, allowed: ['burgers', 'combos', 'sides'] },
    { id: 'ext-bacon', name: 'Smoked Crispy Turkey Bacon', price: 1.20, allowed: ['burgers', 'combos'] },
    { id: 'ext-patty', name: 'Extra Premium Fire Beef Patty', price: 2.50, allowed: ['burgers', 'combos'] },
    { id: 'ext-avocado', name: 'Creamy Hass Avocado Slices', price: 0.90, allowed: ['burgers', 'combos', 'chicken', 'sides'] },
    { id: 'ext-chili', name: 'Sizzling Sriracha Lava Dip', price: 0.50, allowed: ['burgers', 'combos', 'chicken', 'sides'] },
    { id: 'ext-jalapeno', name: 'Pickled Greek Jalapeños', price: 0.70, allowed: ['burgers', 'combos', 'chicken', 'sides'] },
  ], []);

  // Filter extras applicable to our active item's category
  const activeExtrasOptions = useMemo(() => {
    return EXTRA_TOPPINGS.filter(topping => topping.allowed.includes(item.category));
  }, [item.category, EXTRA_TOPPINGS]);

  // Mock nutritional information calculated accurately based on item calorie weight
  const nutritionalInfo = useMemo(() => {
    const factor = item.calories / 1000;
    return {
      energyKJ: Math.round(item.calories * 4.184),
      energyKcal: item.calories,
      fat: (32 * factor).toFixed(1),
      saturatedFat: (11 * factor).toFixed(1),
      carbs: (42 * factor).toFixed(1),
      sugars: (6 * factor).toFixed(1),
      protein: (26 * factor).toFixed(1),
      salt: (1.8 * factor).toFixed(2),
      sodium: (0.72 * factor).toFixed(2)
    };
  }, [item.calories]);

  // Handle Exclude ingredient toggle
  const toggleIngredient = (ing: string) => {
    setExcludedIngredients(prev =>
      prev.includes(ing) ? prev.filter(i => i !== ing) : [...prev, ing]
    );
  };

  // Handle Extra topping Add/Remove
  const toggleExtra = (topping: { id: string; name: string; price: number }) => {
    setSelectedExtras(prev => {
      const exists = prev.some(t => t.id === topping.id);
      if (exists) {
        return prev.filter(t => t.id !== topping.id);
      } else {
        return [...prev, topping];
      }
    });
  };

  // Calculate dynamic items overall price matching kfc.gr logic
  const singleItemExtrasCost = selectedExtras.reduce((sum, current) => sum + current.price, 0);
  const singleItemFinalPrice = item.price + singleItemExtrasCost;
  const grandCalculatedTotal = singleItemFinalPrice * quantity;

  // Compile customization string representing our modifications inside the shopping basket
  const finalCustomizationText = useMemo(() => {
    const modifications: string[] = [];
    if (excludedIngredients.length > 0) {
      modifications.push(`Χωρίς/No: ${excludedIngredients.join(', ')}`);
    }
    if (selectedExtras.length > 0) {
      modifications.push(`Με/With Extras: ${selectedExtras.map(e => e.name.replace('Extra ', '')).join('+')}`);
    }
    return modifications.join(' | ') || 'Standard Recipe';
  }, [excludedIngredients, selectedExtras]);

  // Submit customization directly to checkout state
  const handleAddToBasket = () => {
    onAddToCart(item, finalCustomizationText, quantity);
    onClose();
  };

  return (
    <div className="fixed inset-0 bg-stone-950/70 backdrop-blur-xs flex items-center justify-center p-4 z-55 animate-fadeIn" id="kfc-product-detail-modal">
      <div 
        className="bg-[#FDFBF7] rounded-3xl overflow-hidden shadow-2xl max-w-4xl w-full border border-stone-200 flex flex-col md:flex-row h-[90vh] md:h-[80vh] animate-slideIn text-stone-900"
        onClick={(e) => e.stopPropagation()}
      >
        
        {/* LEFT COMPARTMENT: LARGE ZOOMABLE PRODUCT GRAPHIC */}
        <div className="md:w-1/2 bg-stone-100 relative flex flex-col justify-between overflow-hidden border-b md:border-b-0 md:border-r border-stone-200">
          
          {/* Top category strip */}
          <div className="absolute top-4 left-4 z-10 flex gap-1.5 items-center">
            <span className="bg-[#B91C1C] text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded shadow-sm flex items-center gap-1">
              <Flame className="w-3 h-3 text-[#FFD700]" />
              {item.category.toUpperCase()}
            </span>
            {item.isBestSeller && (
              <span className="bg-[#064E3B] text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded shadow-sm">
                Bestseller
              </span>
            )}
          </div>

          {/* Majestic Image Container */}
          <div className="relative w-full flex-grow flex items-center justify-center p-4 bg-stone-50">
            <img
              src={item.image}
              alt={item.name}
              className="w-full h-full object-cover md:rounded-2xl max-h-[250px] md:max-h-full transition-transform duration-500 hover:scale-[1.03]"
              referrerPolicy="no-referrer"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-black/10 pointer-events-none" />
          </div>

          {/* Quick Specifications Overlay bottom panel */}
          <div className="bg-gradient-to-r from-[#064E3B] to-[#043427] text-white p-6 space-y-2 relative">
            <h2 className="text-xl sm:text-2xl font-black italic uppercase font-heading tracking-tight leading-tight">
              {item.name}
            </h2>
            <p className="text-white/80 text-xs leading-relaxed font-sans font-medium line-clamp-3">
              {item.description}
            </p>

            {/* Micro specs icons row */}
            <div className="flex gap-4 pt-3 border-t border-white/15 text-[10px] uppercase font-bold tracking-widest text-[#FFD700]">
              <div className="flex items-center gap-1.5">
                <span className="bg-white/10 p-1 rounded-md text-white">🔥</span>
                <span>Prep: {item.prepTime}</span>
              </div>
              <div className="flex items-center gap-1.5">
                <span className="bg-white/10 p-1 rounded-md text-white">🥗</span>
                <span>{item.calories} Calories</span>
              </div>
              <div className="flex items-center gap-1.5">
                <span className="bg-white/10 p-1 rounded-md text-white">🛡️</span>
                <span>Safe Kitchen</span>
              </div>
            </div>
          </div>
        </div>

        {/* RIGHT COMPARTMENT: INTERACTIVE CONFIGURATION FORM (INSPIRED BY KFC.GR) */}
        <div className="md:w-1/2 flex flex-col justify-between h-full bg-white">
          
          {/* Header & Tabs selector */}
          <div className="p-4 border-b border-stone-200 bg-white sticky top-0 z-10">
            <div className="flex justify-between items-center mb-4">
              <span className="text-[10px] uppercase font-black text-[#064E3B] tracking-widest">
                Gourmet Customizer Engine
              </span>
              <button
                onClick={onClose}
                className="w-8 h-8 rounded-full bg-stone-100 hover:bg-[#B91C1C] hover:text-white flex items-center justify-center text-stone-500 transition-colors shadow-sm"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Customizer Tabs Bar */}
            <div className="grid grid-cols-3 gap-1 bg-stone-100 p-0.5 rounded-lg">
              <button
                onClick={() => setActiveTab('customise')}
                className={`py-2 text-center rounded text-[10px] uppercase font-black tracking-wider transition ${
                  activeTab === 'customise'
                    ? 'bg-white text-[#064E3B] shadow-xs'
                    : 'text-stone-500 hover:text-stone-900'
                }`}
              >
                Customise Recipe
              </button>
              <button
                onClick={() => setActiveTab('nutrition')}
                className={`py-2 text-center rounded text-[10px] uppercase font-black tracking-wider transition ${
                  activeTab === 'nutrition'
                    ? 'bg-white text-[#064E3B] shadow-xs'
                    : 'text-stone-500 hover:text-stone-900'
                }`}
              >
                Nutritional Values
              </button>
              <button
                onClick={() => setActiveTab('allergens')}
                className={`py-2 text-center rounded text-[10px] uppercase font-black tracking-wider transition ${
                  activeTab === 'allergens'
                    ? 'bg-white text-[#064E3B] shadow-xs'
                    : 'text-stone-500 hover:text-stone-900'
                }`}
              >
                Allergens (GR)
              </button>
            </div>
          </div>

          {/* Scrollable Tabs Body */}
          <div className="flex-1 overflow-y-auto p-6 space-y-6 no-scrollbar bg-[#FDFBF7]">

            {/* 1. CUSTOMISE INGREDIENTS TAB */}
            {activeTab === 'customise' && (
              <div className="space-y-6 animate-fadeIn">
                
                {/* INGREDIENT REMOVAL WORKSPACE (No mayo, etc.) */}
                <div className="space-y-3">
                  <div className="flex justify-between items-baseline">
                    <h4 className="text-xs font-black uppercase text-stone-400 tracking-wider">
                      Exclude Ingredients (Aφαίρεση υλικών)
                    </h4>
                    <span className="text-[9px] text-stone-400 italic">Uncheck to remove</span>
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    {defaultIngredientsList.map((ing) => {
                      const isExcluded = excludedIngredients.includes(ing);
                      return (
                        <button
                          key={ing}
                          type="button"
                          onClick={() => toggleIngredient(ing)}
                          className={`flex items-center justify-between p-3 rounded-xl border text-left transition ${
                            isExcluded
                              ? 'bg-rose-50 border-rose-200 text-stone-400 line-through'
                              : 'bg-white border-stone-200 text-stone-800 font-bold'
                          }`}
                        >
                          <span className="text-xs truncate">{ing}</span>
                          <span className={`w-4 h-4 rounded-full border flex items-center justify-center shrink-0 ${
                            isExcluded 
                              ? 'bg-red-500 border-red-500 text-white' 
                              : 'bg-white border-stone-300 text-emerald-800'
                          }`}>
                            {!isExcluded && <Check className="w-2.5 h-2.5" />}
                          </span>
                        </button>
                      );
                    })}
                  </div>
                </div>

                {/* ADDITIONAL EXTRAS WORKSPACE (Cheese, Extra Patty) */}
                {activeExtrasOptions.length > 0 && (
                  <div className="space-y-3 pt-2">
                    <h4 className="text-xs font-black uppercase text-stone-400 tracking-wider">
                      Add Extra Toppings (Πρόσθετα υλικά)
                    </h4>

                    <div className="flex flex-col gap-2">
                      {activeExtrasOptions.map((extra) => {
                        const isSelected = selectedExtras.some(t => t.id === extra.id);
                        return (
                          <button
                            key={extra.id}
                            type="button"
                            onClick={() => toggleExtra(extra)}
                            className={`flex items-center justify-between p-3.5 rounded-xl border text-left bg-white transition ${
                              isSelected
                                ? 'border-[#064E3B] bg-emerald-50/50'
                                : 'border-stone-200 hover:bg-stone-50'
                            }`}
                          >
                            <div className="flex items-center gap-2.5">
                              <span className={`w-4 h-4 rounded border flex items-center justify-center shrink-0 ${
                                isSelected ? 'bg-[#064E3B] border-[#064E3B] text-white' : 'bg-white border-stone-300'
                              }`}>
                                {isSelected && <Check className="w-3 h-3 text-white" />}
                              </span>
                              <span className="text-xs font-bold text-stone-850 truncate">{extra.name}</span>
                            </div>
                            <span className="text-xs font-black text-[#064E3B] font-mono shrink-0">
                              +&euro;{extra.price.toFixed(2)}
                            </span>
                          </button>
                        );
                      })}
                    </div>
                  </div>
                )}

                {/* Visualizer customization result banner */}
                <div className="p-3 bg-[#064E3B]/5 border border-[#064E3B]/10 rounded-xl flex items-start gap-2 text-[10px] text-stone-500 font-medium">
                  <ClipboardList className="w-4 h-4 text-[#064E3B] shrink-0 mt-0.5" />
                  <div>
                    <span className="block font-bold text-[#064E3B] uppercase tracking-wide">Dynamic Note to Chef:</span>
                    <span className="block break-words">{finalCustomizationText}</span>
                  </div>
                </div>
              </div>
            )}

            {/* 2. NUTRITIONAL VALUES TAB (KFC.GR STANDARD VIEW) */}
            {activeTab === 'nutrition' && (
              <div className="space-y-6 animate-fadeIn text-xs">
                <div className="p-4 bg-amber-500/5 rounded-2xl border border-amber-500/10 flex gap-2">
                  <Info className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                  <p className="text-stone-500 text-[11px] leading-relaxed">
                    Values of nutritional components are calculated as average figures per serving for typical diets. Actual portions cooked dynamically may slightly vary.
                  </p>
                </div>

                <div className="border border-stone-200 rounded-2xl overflow-hidden shadow-sm bg-white divide-y divide-stone-100">
                  <div className="bg-stone-100 p-3 flex justify-between font-black uppercase text-[10px] tracking-wider text-stone-600">
                    <span>Nutritional Component (Συστατικά)</span>
                    <span>Value per Product Serving</span>
                  </div>
                  
                  <div className="p-3 flex justify-between items-center bg-stone-50/50">
                    <span className="font-bold text-stone-800">Energy (Ενέργεια)</span>
                    <span className="font-mono text-stone-900 font-extrabold">{nutritionalInfo.energyKJ} kJ / {nutritionalInfo.energyKcal} kcal</span>
                  </div>

                  <div className="p-3 flex justify-between items-center">
                    <div className="flex flex-col">
                      <span className="font-bold text-stone-800">Fat (Λιπαρά)</span>
                      <span className="text-[10px] text-stone-400">of which Saturated Fat (εκ των οποίων κορεσμένα)</span>
                    </div>
                    <div className="text-right">
                      <span className="block font-mono text-stone-900 font-semibold">{nutritionalInfo.fat}g</span>
                      <span className="block font-mono text-stone-400 text-[10px]">{nutritionalInfo.saturatedFat}g</span>
                    </div>
                  </div>

                  <div className="p-3 flex justify-between items-center">
                    <div className="flex flex-col">
                      <span className="font-bold text-stone-800">Carbohydrates (Υδατάνθρακες)</span>
                      <span className="text-[10px] text-stone-400">of which Sugars (εκ των οποίων σάκχαρα)</span>
                    </div>
                    <div className="text-right">
                      <span className="block font-mono text-stone-900 font-semibold">{nutritionalInfo.carbs}g</span>
                      <span className="block font-mono text-stone-400 text-[10px]">{nutritionalInfo.sugars}g</span>
                    </div>
                  </div>

                  <div className="p-3 flex justify-between items-center">
                    <span className="font-bold text-stone-800">Protein (Πρωτεΐνη)</span>
                    <span className="font-mono text-stone-900 font-semibold">{nutritionalInfo.protein}g</span>
                  </div>

                  <div className="p-3 flex justify-between items-center">
                    <span className="font-bold text-stone-800">Salt (Αλάτι)</span>
                    <span className="font-mono text-stone-900 font-semibold">{nutritionalInfo.salt}g</span>
                  </div>

                  <div className="p-3 flex justify-between items-center bg-stone-50">
                    <span className="font-bold text-stone-800">Sodium (Νάτριο)</span>
                    <span className="font-mono text-[#B91C1C] font-extrabold">{nutritionalInfo.sodium}g</span>
                  </div>
                </div>

                <div className="flex justify-around items-center border border-dashed border-stone-200 p-4 rounded-xl bg-white text-center">
                  <div>
                    <span className="block font-black text-lg text-[#064E3B] font-mono">{Math.round((item.calories/2000)*100)}%</span>
                    <span className="block text-[9px] text-stone-400 uppercase font-bold">RDA (Recommended Daily Allowance)</span>
                  </div>
                </div>
              </div>
            )}

            {/* 3. ALLERGENS TAB */}
            {activeTab === 'allergens' && (
              <div className="space-y-6 animate-fadeIn">
                <div className="p-4 bg-rose-50 border border-rose-100 rounded-2xl flex gap-3 text-xs text-rose-800">
                  <ShieldAlert className="w-5 h-5 text-rose-600 shrink-0" />
                  <div>
                    <strong className="block font-black uppercase text-[10px] mb-1">Warning (Προσοχή στις αλλεργίες)</strong>
                    <p className="leading-relaxed text-[11px] text-rose-700">
                      Our kitchen stores and handles wheat, dairy products, eggs, soy and mustard seeds. While cleaning surfaces thoroughly, cross contamination traces may stay.
                    </p>
                  </div>
                </div>

                <div className="space-y-3">
                  <h4 className="text-xs font-black uppercase text-stone-400 tracking-wider">
                    Allergen Trace Assessment (Παρουσία αλλεργιογόνων)
                  </h4>

                  <div className="grid grid-cols-2 gap-3">
                    {[
                      { name: 'Cereals with Gluten (Γλουτένη)', contains: !['drinks', 'sides'].includes(item.category) },
                      { name: 'Milk & Lactose (Γαλακτοκομικά)', contains: !['drinks', 'chicken'].includes(item.category) },
                      { name: 'Egg & White Albumen (Αυγά)', contains: ['burgers', 'combos', 'desserts'].includes(item.category) },
                      { name: 'Soya Soybeans (Σόγια)', contains: ['burgers', 'combos'].includes(item.category) },
                      { name: 'Mustard Seeds (Μουστάρδα)', contains: ['burgers', 'combos'].includes(item.category) },
                      { name: 'Sesame & Seed oil (Σουσάμι)', contains: ['burgers', 'combos'].includes(item.category) }
                    ].map((all) => (
                      <div 
                        key={all.name} 
                        className={`p-3.5 rounded-xl border flex items-center justify-between gap-2 transition bg-white ${
                          all.contains 
                            ? 'border-rose-200 text-rose-850 bg-rose-50/10' 
                            : 'border-stone-150 text-stone-400'
                        }`}
                      >
                        <span className="text-xs font-bold leading-tight">{all.name}</span>
                        {all.contains ? (
                          <span className="bg-[#B91C1C] text-white text-[9px] font-black px-1.5 py-0.5 rounded uppercase font-mono">
                            YES (ΝΑΙ)
                          </span>
                        ) : (
                          <span className="bg-stone-100 text-stone-400 text-[9px] px-1.5 py-0.5 rounded">
                            NO (ΟΧΙ)
                          </span>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}

          </div>

          {/* Checkout Controls Footer with Real-time Calculations / Matches kfc.gr */}
          <div className="p-6 bg-white border-t border-stone-150 space-y-4 shadow-xl z-20">
            <div className="flex items-center justify-between">
              
              {/* Counter increment selectors */}
              <div className="flex flex-col">
                <span className="text-[8px] uppercase tracking-wider font-extrabold text-stone-400 mb-1">Set Quantity</span>
                <div className="flex items-center gap-2.5 border rounded-full px-3 py-1.5 border-stone-250 bg-stone-50 select-none">
                  <button
                    onClick={() => setQuantity(q => Math.max(1, q - 1))}
                    className="text-stone-450 hover:text-stone-900 font-bold px-1 text-sm shrink-0"
                    title="Less servings"
                  >
                    <Minus className="w-3.5 h-3.5" />
                  </button>
                  <span className="text-sm font-extrabold font-mono px-1 w-6 text-center">{quantity}</span>
                  <button
                    onClick={() => setQuantity(q => q + 1)}
                    className="text-[#064E3B] hover:text-[#B91C1C] font-bold px-1 text-sm shrink-0"
                    title="More servings"
                  >
                    <Plus className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>

              {/* Individual Item Pricing recap */}
              <div className="text-right">
                <span className="text-[8px] uppercase tracking-wider font-extrabold text-stone-400 block mb-0.5">Price Structure</span>
                <span className="text-xs text-stone-500 font-bold block">
                  &euro;{singleItemFinalPrice.toFixed(2)} each 
                  {singleItemExtrasCost > 0 && ` (€${item.price.toFixed(2)} + €${singleItemExtrasCost.toFixed(2)} extras)`}
                </span>
              </div>

            </div>

            {/* Direct Add Button containing the calculated checkout pricing */}
            <button
              onClick={handleAddToBasket}
              className="w-full bg-[#B91C1C] hover:bg-[#991B1B] text-white font-black py-4 px-6 rounded-xl text-center uppercase tracking-widest transition duration-150 shadow-lg shadow-red-100 flex items-center justify-between group active:scale-[0.99]"
            >
              <span className="group-hover:translate-x-1 transition-transform">Add customized item to basket</span>
              <span className="font-mono text-lg text-[#FFD700] bg-black/15 px-3 py-0.5 rounded font-black">
                &euro;{grandCalculatedTotal.toFixed(2)}
              </span>
            </button>
          </div>

        </div>

      </div>
    </div>
  );
}
