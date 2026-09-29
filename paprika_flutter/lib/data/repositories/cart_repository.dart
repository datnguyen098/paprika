import 'package:shared_preferences/shared_preferences.dart';

import '../models/cart_model.dart';

class CartRepository {
  CartRepository(this._prefs);

  static const _storageKey = 'paprika_cart_v1';

  final SharedPreferences _prefs;

  CartData getCart() => CartData.decode(_prefs.getString(_storageKey));

  Future<CartData> addItem(CartItem item) async {
    final cart = getCart();
    final items = [...cart.items];
    final index = items.indexWhere((existing) => existing.lineKey == item.lineKey);
    if (index >= 0) {
      final current = items[index];
      items[index] = current.copyWith(
        quantity: (current.quantity + item.quantity).clamp(1, 99).toInt(),
      );
    } else {
      items.add(item);
    }
    return _save(CartData(items: items));
  }

  Future<CartData> updateQuantity(String lineKey, int quantity) async {
    final cart = getCart();
    final items = <CartItem>[];
    for (final item in cart.items) {
      if (item.lineKey != lineKey) {
        items.add(item);
      } else if (quantity > 0) {
        items.add(item.copyWith(quantity: quantity.clamp(1, 99).toInt()));
      }
    }
    return _save(CartData(items: items));
  }

  Future<CartData> removeItem(String lineKey) async {
    final cart = getCart();
    return _save(
      CartData(
        items: cart.items.where((item) => item.lineKey != lineKey).toList(),
      ),
    );
  }

  Future<CartData> clear() => _save(const CartData());

  Future<CartData> _save(CartData cart) async {
    await _prefs.setString(_storageKey, cart.encode());
    return cart;
  }
}
