import 'dart:convert';

import 'dish_model.dart';

class CartData {
  const CartData({
    this.items = const [],
  });

  final List<CartItem> items;

  int get count => items.fold(0, (sum, item) => sum + item.quantity);
  int get subtotal => items.fold(0, (sum, item) => sum + item.lineTotal);
  bool get isEmpty => items.isEmpty;

  CartData copyWith({List<CartItem>? items}) {
    return CartData(items: items ?? this.items);
  }

  factory CartData.fromJson(Map<String, dynamic> json) {
    return CartData(
      items: (json['items'] as List<dynamic>? ?? [])
          .map((e) => CartItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  Map<String, dynamic> toJson() => {
        'items': items.map((item) => item.toJson()).toList(),
      };

  static CartData decode(String? raw) {
    if (raw == null || raw.isEmpty) return const CartData();
    try {
      return CartData.fromJson(jsonDecode(raw) as Map<String, dynamic>);
    } catch (_) {
      return const CartData();
    }
  }

  String encode() => jsonEncode(toJson());
}

class CartItem {
  const CartItem({
    required this.lineKey,
    required this.dishId,
    required this.name,
    required this.slug,
    required this.quantity,
    required this.baseUnitPrice,
    required this.unitPrice,
    this.image,
    this.note,
    this.selectedOptions = const [],
  });

  final String lineKey;
  final int dishId;
  final String name;
  final String slug;
  final String? image;
  final int quantity;
  final int baseUnitPrice;
  final int unitPrice;
  final String? note;
  final List<CartOption> selectedOptions;

  int get lineTotal => unitPrice * quantity;
  String get summary {
    final parts = <String>[];
    if (selectedOptions.isNotEmpty) {
      final grouped = <String, List<String>>{};
      for (final option in selectedOptions) {
        grouped.putIfAbsent(option.groupName, () => <String>[]).add(option.name);
      }
      for (final entry in grouped.entries) {
        parts.add('${entry.key}: ${entry.value.join(', ')}');
      }
    }
    final cleanNote = note?.trim();
    if (cleanNote != null && cleanNote.isNotEmpty) {
      parts.add('Ghi chú: $cleanNote');
    }
    return parts.isEmpty ? 'Công thức tiêu chuẩn' : parts.join(' | ');
  }

  CartItem copyWith({
    int? quantity,
  }) {
    return CartItem(
      lineKey: lineKey,
      dishId: dishId,
      name: name,
      slug: slug,
      image: image,
      quantity: quantity ?? this.quantity,
      baseUnitPrice: baseUnitPrice,
      unitPrice: unitPrice,
      note: note,
      selectedOptions: selectedOptions,
    );
  }

  factory CartItem.fromJson(Map<String, dynamic> json) {
    return CartItem(
      lineKey: json['line_key'] as String? ?? '',
      dishId: (json['dish_id'] as num?)?.toInt() ?? 0,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      image: json['image'] as String?,
      quantity: (json['quantity'] as num?)?.toInt() ?? 1,
      baseUnitPrice: (json['base_unit_price'] as num?)?.toInt() ?? 0,
      unitPrice: (json['unit_price'] as num?)?.toInt() ?? 0,
      note: json['note'] as String?,
      selectedOptions: (json['selected_options'] as List<dynamic>? ?? [])
          .map((e) => CartOption.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }

  Map<String, dynamic> toJson() => {
        'line_key': lineKey,
        'dish_id': dishId,
        'name': name,
        'slug': slug,
        'image': image,
        'quantity': quantity,
        'base_unit_price': baseUnitPrice,
        'unit_price': unitPrice,
        'note': note,
        'selected_options': selectedOptions.map((option) => option.toJson()).toList(),
      };

  Map<String, dynamic> toOrderJson() => {
        'dish_id': dishId,
        'quantity': quantity,
        'option_ids': selectedOptions.map((option) => option.id).toList(),
        'note': note,
      };
}

class CartOption {
  const CartOption({
    required this.id,
    required this.name,
    required this.groupId,
    required this.groupName,
    required this.priceDelta,
  });

  final int id;
  final String name;
  final int groupId;
  final String groupName;
  final int priceDelta;

  factory CartOption.fromJson(Map<String, dynamic> json) {
    return CartOption(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name'] as String? ?? '',
      groupId: (json['group_id'] as num?)?.toInt() ?? 0,
      groupName: json['group_name'] as String? ?? '',
      priceDelta: (json['price_delta'] as num?)?.toInt() ?? 0,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'group_id': groupId,
        'group_name': groupName,
        'price_delta': priceDelta,
      };
}

CartItem cartItemFromDishDetail({
  required DishDetail detail,
  required int quantity,
  required Map<int, Set<int>> selectedOptions,
  String? note,
}) {
  final options = <CartOption>[];
  var optionsTotal = 0;
  for (final group in detail.options) {
    final selected = selectedOptions[group.id] ?? const <int>{};
    for (final option in group.options) {
      if (!selected.contains(option.id)) continue;
      options.add(
        CartOption(
          id: option.id,
          name: option.name,
          groupId: group.id,
          groupName: group.name,
          priceDelta: option.priceDelta,
        ),
      );
      optionsTotal += option.priceDelta;
    }
  }

  final cleanNote = note?.trim();
  final lineKey = _lineKey(
    detail.id,
    options.map((option) => option.id).toList()..sort(),
    cleanNote == null || cleanNote.isEmpty ? null : cleanNote,
  );
  final base = detail.currentPrice;
  final unit = (base + optionsTotal).clamp(0, 1 << 31).toInt();

  return CartItem(
    lineKey: lineKey,
    dishId: detail.id,
    name: detail.name,
    slug: detail.slug,
    image: detail.image,
    quantity: quantity.clamp(1, 99).toInt(),
    baseUnitPrice: base,
    unitPrice: unit,
    note: cleanNote == null || cleanNote.isEmpty ? null : cleanNote,
    selectedOptions: options,
  );
}

String _lineKey(int dishId, List<int> optionIds, String? note) {
  final raw = jsonEncode({
    'dish_id': dishId,
    'options': optionIds,
    'note': note,
  });
  return base64Url.encode(utf8.encode(raw));
}
