import 'package:equatable/equatable.dart';

/// Sub-model - Category lồng trong Dish.
class DishCategory extends Equatable {
  const DishCategory({
    required this.id,
    required this.name,
    required this.slug,
  });

  final int id;
  final String name;
  final String slug;

  factory DishCategory.fromJson(Map<String, dynamic> json) {
    return DishCategory(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'slug': slug,
      };

  @override
  List<Object?> get props => [id];
}

/// Dish summary - dùng cho list (menu, featured, search).
/// Response từ DishController::transformDishSummary().
class Dish extends Equatable {
  const Dish({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    required this.price,
    this.salePrice,
    this.image,
    this.isFeatured = false,
    this.isAvailable = true,
    this.availabilityLabel,
    this.category,
  });

  final int id;
  final String name;
  final String slug;
  final String? description;
  final int price;
  final int? salePrice;
  final String? image;
  final bool isFeatured;
  final bool isAvailable;
  final String? availabilityLabel;
  final DishCategory? category;

  /// Có đang sale không
  bool get hasDiscount => salePrice != null && salePrice! < price;

  /// Giá hiện tại (sale nếu có, không thì price gốc)
  int get currentPrice => salePrice ?? price;

  /// % giảm giá (0-100), 0 nếu không sale
  int get discountPercent {
    if (!hasDiscount) return 0;
    return ((price - salePrice!) * 100 / price).round();
  }

  factory Dish.fromJson(Map<String, dynamic> json) {
    return Dish(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String?,
      price: (json['price'] as num).toInt(),
      salePrice: json['sale_price'] != null ? (json['sale_price'] as num).toInt() : null,
      image: json['image'] as String?,
      isFeatured: json['is_featured'] as bool? ?? false,
      isAvailable: json['is_available'] as bool? ?? true,
      availabilityLabel: json['availability_label'] as String?,
      category: json['category'] is Map<String, dynamic>
          ? DishCategory.fromJson(json['category'] as Map<String, dynamic>)
          : null,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'slug': slug,
        'description': description,
        'price': price,
        'sale_price': salePrice,
        'image': image,
        'is_featured': isFeatured,
        'is_available': isAvailable,
        'availability_label': availabilityLabel,
        'category': category?.toJson(),
      };

  @override
  List<Object?> get props => [id, name, price, salePrice];
}

/// Dish chi tiết (dùng cho trang detail) - chưa cần implement đầy đủ,
/// sẽ bổ sung khi team cần (gallery, options, time slots, ...).
class DishDetail {
  DishDetail({
    required this.id,
    required this.name,
    required this.slug,
    required this.description,
    required this.price,
    required this.image,
    required this.isFeatured,
    required this.gallery,
    required this.availability,
    required this.options,
    this.content,
    this.ingredients,
    this.salePrice,
    this.category,
  });

  final int id;
  final String name;
  final String slug;
  final String? description;
  final String? content;
  final String? ingredients;
  final int price;
  final int? salePrice;
  final String? image;
  final bool isFeatured;
  final DishCategory? category;
  final List<String> gallery;
  final DishAvailability availability;
  final List<DishOptionGroup> options;

  factory DishDetail.fromJson(Map<String, dynamic> json) {
    return DishDetail(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String?,
      content: json['content'] as String?,
      ingredients: json['ingredients'] as String?,
      price: (json['price'] as num).toInt(),
      salePrice: json['sale_price'] != null ? (json['sale_price'] as num).toInt() : null,
      image: json['image'] as String?,
      isFeatured: json['is_featured'] as bool? ?? false,
      category: json['category'] is Map<String, dynamic>
          ? DishCategory.fromJson(json['category'] as Map<String, dynamic>)
          : null,
      gallery: (json['gallery'] as List<dynamic>? ?? [])
          .map((e) => e as String)
          .toList(),
      availability: DishAvailability.fromJson(
          json['availability'] as Map<String, dynamic>? ?? {}),
      options: (json['options'] as List<dynamic>? ?? [])
          .map((e) => DishOptionGroup.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class DishAvailability {
  DishAvailability({
    required this.available,
    this.label,
    this.timeSlots = const [],
  });

  final bool available;
  final String? label;
  final List<DishTimeSlot> timeSlots;

  factory DishAvailability.fromJson(Map<String, dynamic> json) {
    return DishAvailability(
      available: json['available'] as bool? ?? true,
      label: json['label'] as String?,
      timeSlots: (json['time_slots'] as List<dynamic>? ?? [])
          .map((e) => DishTimeSlot.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class DishTimeSlot {
  DishTimeSlot({required this.id, required this.name, this.startTime, this.endTime});

  final int id;
  final String name;
  final String? startTime;
  final String? endTime;

  factory DishTimeSlot.fromJson(Map<String, dynamic> json) => DishTimeSlot(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        startTime: json['start_time'] as String?,
        endTime: json['end_time'] as String?,
      );
}

class DishOptionGroup {
  DishOptionGroup({
    required this.id,
    required this.name,
    this.description,
    required this.type,
    required this.isRequired,
    required this.minSelect,
    required this.maxSelect,
    required this.options,
  });

  final int id;
  final String name;
  final String? description;
  final String type;
  final bool isRequired;
  final int minSelect;
  final int maxSelect;
  final List<DishOptionItem> options;

  factory DishOptionGroup.fromJson(Map<String, dynamic> json) => DishOptionGroup(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        description: json['description'] as String?,
        type: json['type'] as String? ?? 'single',
        isRequired: json['is_required'] as bool? ?? false,
        minSelect: json['min_select'] as int? ?? 0,
        maxSelect: json['max_select'] as int? ?? 1,
        options: (json['options'] as List<dynamic>? ?? [])
            .map((e) => DishOptionItem.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

class DishOptionItem {
  DishOptionItem({
    required this.id,
    required this.name,
    required this.priceDelta,
    this.isDefault = false,
  });

  final int id;
  final String name;
  final int priceDelta;
  final bool isDefault;

  factory DishOptionItem.fromJson(Map<String, dynamic> json) => DishOptionItem(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        priceDelta: (json['price_delta'] as num?)?.toInt() ?? 0,
        isDefault: json['is_default'] as bool? ?? false,
      );
}