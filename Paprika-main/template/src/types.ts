export interface MenuItem {
  id: string;
  name: string;
  description: string;
  price: number;
  originalPrice?: number;
  category: MenuItemCategory;
  spicyLevel: 0 | 1 | 2 | 3;
  isBestSeller?: boolean;
  isChefSpecial?: boolean;
  isVegetarian?: boolean;
  isVegan?: boolean;
  isGlutenFree?: boolean;
  calories: number;
  prepTime: string;
  image: string;
}

export type MenuItemCategory = 'burgers' | 'chicken' | 'sides' | 'desserts' | 'drinks' | 'combos';

export interface CartItem {
  menuItem: MenuItem;
  quantity: number;
  customization?: string;
}

export type BookingGuestCount = 1 | 2 | 3 | 4 | 5 | 6 | 8 | 10 | 12;

export interface BookingDetails {
  fullName: string;
  email: string;
  phone: string;
  date: string;
  time: string;
  guests: BookingGuestCount;
  seatingArea: 'indoor' | 'terrace' | 'garden';
  notes?: string;
}

export interface BranchStore {
  id: string;
  name: string;
  address: string;
  phone: string;
  hours: string;
  distance?: string;
  latitude: number;
  longitude: number;
}

export interface OrderDetails {
  serviceType: 'delivery' | 'pickup' | 'dinein';
  deliveryAddress?: {
    street: string;
    city: string;
    floorOrApt?: string;
    notes?: string;
    coords?: { lat: number; lng: number };
  };
  selectedStoreId?: string;
  dineInTable?: string;
  paymentMethod: 'card' | 'cash' | 'gpay';
  promoCode?: string;
  discountPercentage: number;
}
