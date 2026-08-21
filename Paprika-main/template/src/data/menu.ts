import { MenuItem, BranchStore, MenuItemCategory } from '../types';

export const MENU_CATEGORIES: { id: MenuItemCategory; label: string; icon: string }[] = [
  { id: 'combos', label: 'Spicy Combos', icon: 'Flame' },
  { id: 'burgers', label: 'Premium Burgers', icon: 'FlameKindling' },
  { id: 'chicken', label: 'Crispy Chili Chicken', icon: 'Sparkles' },
  { id: 'sides', label: 'Fries & Sides', icon: 'Donut' },
  { id: 'desserts', label: 'Sweet Treats', icon: 'CakeSlice' },
  { id: 'drinks', label: 'Cold Dips & Drinks', icon: 'Beer' }
];

export const MENU_ITEMS: MenuItem[] = [
  // Combos
  {
    id: 'combo-1',
    name: 'El Diablo Jalapeño Feast',
    description: 'Double custom beef patty with premium melted cheddar, pickled organic jalapeños, crispy green oak lettuce, and our spicy signature chili infusion. Paired with loaded spiced fries and double-chilled fresh lime lemonade.',
    price: 15.90,
    originalPrice: 19.50,
    category: 'combos',
    spicyLevel: 3,
    isBestSeller: true,
    calories: 1120,
    prepTime: '12-15 mins',
    image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=800'
  },
  {
    id: 'combo-2',
    name: 'The Verde Chimichurri Chicken Combo',
    description: 'Crispy tender chicken breast glazed with aromatic coriander-green chimichurri oil, fresh avocado slices, and mild chili mayo. Paired with crispy potato skin wedges and green mint tea.',
    price: 14.20,
    category: 'combos',
    spicyLevel: 1,
    isChefSpecial: true,
    calories: 940,
    prepTime: '10-12 mins',
    image: 'https://images.unsplash.com/photo-1606755962773-d324e0a13086?auto=format&fit=crop&q=80&w=800'
  },
  // Burgers
  {
    id: 'burger-1',
    name: 'Chili Sriracha Double Beef',
    description: 'Two premium dry-aged beef patties, double pepper jack cheese, caramelized red onions, sweet hot sriracha reduction, and crunchy chili oil. Bold, rich, and intensely savory.',
    price: 9.80,
    category: 'burgers',
    spicyLevel: 2,
    isBestSeller: true,
    calories: 820,
    prepTime: '8-10 mins',
    image: 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&q=80&w=800'
  },
  {
    id: 'burger-2',
    name: 'The Green Avocado & Chili Glazed Chicken',
    description: 'Buttermilk crispy chicken breast tossed in green mild-heat jalapeño sauce, sliced fresh Hass avocado, slow-roasted vine tomatoes, and whipped chili-lime spread in a toasted sesame brioche.',
    price: 9.20,
    category: 'burgers',
    spicyLevel: 1,
    isChefSpecial: true,
    calories: 750,
    prepTime: '8-10 mins',
    image: 'https://images.unsplash.com/photo-1525059696034-4967a8e1dca2?auto=format&fit=crop&q=80&w=800'
  },
  {
    id: 'burger-3',
    name: 'Verde Spicy Garden Burger',
    description: 'Handcrafted plant-based green pea & spinach patty, melting vegan high-melt cheese, vine tomatoes, microgreens, and a robust hot chipotle spread.',
    price: 8.90,
    category: 'burgers',
    spicyLevel: 2,
    isVegetarian: true,
    isVegan: true,
    calories: 610,
    prepTime: '10 mins',
    image: 'https://images.unsplash.com/photo-1585238342024-78d387f4a707?auto=format&fit=crop&q=80&w=800'
  },
  // Chicken
  {
    id: 'chicken-1',
    name: 'Chili Dust Tender Sticks',
    description: 'Six ultra-crispy handcrafted whole chicken breast tenders seasoned with our proprietary smoked chipotle and cayenne outer rub. Served with deep green avocado lime crema.',
    price: 7.90,
    category: 'chicken',
    spicyLevel: 2,
    isBestSeller: true,
    calories: 540,
    prepTime: '6-8 mins',
    image: 'https://images.unsplash.com/photo-1562967914-608f82629710?auto=format&fit=crop&q=80&w=800'
  },
  {
    id: 'chicken-2',
    name: 'Volcanic Red Hot Fire Wings',
    description: 'Eight jumbo hot wings glazed with local habanero garlic honey sauce, fresh green onions, and chili seeds. Highly addictive for true hot pepper fans.',
    price: 10.50,
    category: 'chicken',
    spicyLevel: 3,
    calories: 690,
    prepTime: '8-10 mins',
    image: 'https://images.unsplash.com/photo-1567620832903-9fc6debc209f?auto=format&fit=crop&q=80&w=800'
  },
  // Sides
  {
    id: 'sides-1',
    name: 'Skin-on Loaded Chili Wedges',
    description: 'Double cooked potato skins tossed in sea salt, loaded with grated parmesan cheese, fresh minced jalapeños, chili flakes, and dynamic avocado ranch drizzle.',
    price: 4.80,
    category: 'sides',
    spicyLevel: 1,
    isVegetarian: true,
    calories: 430,
    prepTime: '5 mins',
    image: 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&q=80&w=800'
  },
  {
    id: 'sides-2',
    name: 'Green Garlic Chimichurri Fries',
    description: 'Crisp golden rustic fries generously tossed in olive oil, crushed green garlic, fresh flat parsley, oregano and mild bird-eye chili spices.',
    price: 4.20,
    category: 'sides',
    spicyLevel: 1,
    isVegetarian: true,
    isVegan: true,
    calories: 390,
    prepTime: '5 mins',
    image: 'https://images.unsplash.com/photo-1576107232684-1279f390859f?auto=format&fit=crop&q=80&w=800'
  },
  {
    id: 'sides-3',
    name: 'Avocado Crunch Slaw',
    description: 'Shredded fresh green cabbage, coriander, shaved jalapeños, sweet lime vinaigrette and creamy hand-scooped avocado bits.',
    price: 3.90,
    category: 'sides',
    spicyLevel: 0,
    isVegetarian: true,
    isVegan: true,
    isGlutenFree: true,
    calories: 180,
    prepTime: '4 mins',
    image: 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&q=80&w=800'
  },
  // Desserts
  {
    id: 'dessert-1',
    name: 'Mexican Chili Chocolate Fudge Cake',
    description: 'Decadent dark chocolate fudge cake infused with a hint of cinnamon and a subtle warming puff of cayenne pepper. Sweet, rich, with an exciting tingly finish.',
    price: 5.50,
    category: 'desserts',
    spicyLevel: 1,
    isVegetarian: true,
    calories: 480,
    prepTime: '4 mins',
    image: 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&q=80&w=800'
  },
  {
    id: 'dessert-2',
    name: 'Fresh Lime & Mint Lime Tart',
    description: 'Cold whipped key-lime yogurt cream on a whole-wheat cookie crust, loaded with grated mint leaves. Incredibly refreshing contrast after spicy food.',
    price: 4.90,
    category: 'desserts',
    spicyLevel: 0,
    isVegetarian: true,
    calories: 320,
    prepTime: '3 mins',
    image: 'https://images.unsplash.com/photo-1587314168485-3236d6710814?auto=format&fit=crop&q=80&w=800'
  },
  // Drinks
  {
    id: 'drink-1',
    name: 'Signature Spicy Orange Mint Drink',
    description: 'Fresh squeezed orange juice muddled with green cooling mint, sparkling tonic, and a delicate floating chili pepper slice.',
    price: 3.80,
    category: 'drinks',
    spicyLevel: 1,
    isVegetarian: true,
    isVegan: true,
    isGlutenFree: true,
    calories: 85,
    prepTime: '3 mins',
    image: 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&q=80&w=800'
  },
  {
    id: 'drink-2',
    name: 'Green Cucumber Cooler',
    description: 'Cold pressed organic cucumber, green apple juice, dynamic sour lime premium syrup, and a dash of Himalayan salt. Rehydrating and clean.',
    price: 3.50,
    category: 'drinks',
    spicyLevel: 0,
    isVegetarian: true,
    isVegan: true,
    isGlutenFree: true,
    calories: 60,
    prepTime: '3 mins',
    image: 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&q=80&w=800'
  }
];

export const BRANCH_STORES: BranchStore[] = [
  {
    id: 'store-centre',
    name: 'Verde Chilli - Central Athens Plaza',
    address: 'Syntagma Square 12, Athens, Greece',
    phone: '+30 210 334 4555',
    hours: '11:00 AM - Midnight',
    distance: '0.8 km away',
    latitude: 37.9756,
    longitude: 23.7348
  },
  {
    id: 'store-north',
    name: 'Verde Chilli - Kifisia Gourmet Hub',
    address: 'Leoforos Kifisias 212, Marousi, Athens',
    phone: '+30 210 612 8899',
    hours: '12:00 PM - 11:30 PM',
    distance: '6.4 km away',
    latitude: 38.0564,
    longitude: 23.8112
  },
  {
    id: 'store-coast',
    name: 'Verde Chilli - Glyfada Sea Breeze',
    address: 'Leoforos Poseidonos 85, Glyfada, Greece',
    phone: '+30 210 894 1177',
    hours: '11:00 AM - 01:00 AM',
    distance: '12.1 km away',
    latitude: 37.8624,
    longitude: 23.7552
  }
];

export const SPECIAL_OFFERS = [
  {
    id: 'offer-1',
    code: 'CHILI20',
    title: 'Get 20% on Combos',
    subtitle: 'Apply coupon code CHILI20 in checkout for an instant 20% off high-heat combo meals!',
    badge: 'Limited Time Offer',
    discount: 20
  },
  {
    id: 'offer-2',
    code: 'FREEGREEN',
    title: 'Free Avocado Crema Dip',
    subtitle: 'Spend €20+ and receive an automatic gourmet Verde Chimichurri seasoning or cold Avocado Crema dip on us.',
    badge: 'Popular Reward',
    discount: 0
  }
];
