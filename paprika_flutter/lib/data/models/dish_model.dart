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

/// Dish chi tiết (dùng cho trang detail) — API: GET /api/v1/dishes/{id}
///
/// Mirror với `storefront/menu/show.blade.php`:
///   - Ảnh: image + imageSrcset + imageFallback
///   - Stats: prep/energy/branch (chips ở hero)
///   - Tabs: options / nutrition / allergens
///   - Footer: relatedDishes (4 món deduped)
///   - SEO + breadcrumbs cho share/metadata
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
    this.imageSrcset = const [],
    this.imageFallback,
    this.nutrition,
    this.allergens = const [],
    this.relatedDishes = const [],
    this.stats,
    this.breadcrumbs = const [],
    this.seo,
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

  /// Các variant ảnh (card/large/hero) từ image_srcset.
  final List<DishImageSrcset> imageSrcset;
  final String? imageFallback;
  final DishNutrition? nutrition;
  final List<DishAllergen> allergens;
  final List<Dish> relatedDishes;
  final DishStats? stats;
  final List<DishBreadcrumb> breadcrumbs;
  final DishSeo? seo;

  /// Giá hiện tại (sale nếu có, không thì price gốc).
  int get currentPrice => salePrice ?? price;
  bool get hasDiscount => salePrice != null && salePrice! < price;

  factory DishDetail.fromJson(Map<String, dynamic> json) {
    return DishDetail(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String?,
      content: json['content'] as String?,
      ingredients: json['ingredients'] as String?,
      price: (json['price'] as num).toInt(),
      salePrice: json['sale_price'] != null
          ? (json['sale_price'] as num).toInt()
          : null,
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
      imageSrcset: (json['image_srcset'] as List<dynamic>? ?? [])
          .map((e) => DishImageSrcset.fromJson(e as Map<String, dynamic>))
          .toList(),
      imageFallback: json['image_fallback'] as String?,
      nutrition: json['nutrition'] is Map<String, dynamic>
          ? DishNutrition.fromJson(json['nutrition'] as Map<String, dynamic>)
          : null,
      allergens: (json['allergens'] as List<dynamic>? ?? [])
          .map((e) => DishAllergen.fromJson(e as Map<String, dynamic>))
          .toList(),
      relatedDishes: (json['related_dishes'] as List<dynamic>? ?? [])
          .map((e) => Dish.fromJson(e as Map<String, dynamic>))
          .toList(),
      stats: json['stats'] is Map<String, dynamic>
          ? DishStats.fromJson(json['stats'] as Map<String, dynamic>)
          : null,
      breadcrumbs: (json['breadcrumbs'] as List<dynamic>? ?? [])
          .map((e) => DishBreadcrumb.fromJson(e as Map<String, dynamic>))
          .toList(),
      seo: json['seo'] is Map<String, dynamic>
          ? DishSeo.fromJson(json['seo'] as Map<String, dynamic>)
          : null,
    );
  }
}

/// Image variant từ image_srcset (card/large/hero).
class DishImageSrcset {
  const DishImageSrcset({
    required this.variant,
    required this.url,
    required this.width,
  });

  final String variant;
  final String url;
  final int width;

  factory DishImageSrcset.fromJson(Map<String, dynamic> json) =>
      DishImageSrcset(
        variant: json['variant'] as String? ?? '',
        url: json['url'] as String? ?? '',
        width: (json['width'] as num?)?.toInt() ?? 0,
      );
}

/// Nutrition block (5 rows: energy/fat/carbs/protein/salt) + calories.
class DishNutrition {
  const DishNutrition({
    required this.calories,
    required this.rows,
  });

  final int calories;
  final List<DishNutritionRow> rows;

  factory DishNutrition.fromJson(Map<String, dynamic> json) => DishNutrition(
        calories: (json['calories'] as num?)?.toInt() ?? 0,
        rows: (json['rows'] as List<dynamic>? ?? [])
            .map((e) => DishNutritionRow.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

class DishNutritionRow {
  const DishNutritionRow({
    required this.key,
    required this.label,
    required this.value,
  });

  final String key;
  final String label;
  final String value;

  factory DishNutritionRow.fromJson(Map<String, dynamic> json) =>
      DishNutritionRow(
        key: json['key'] as String? ?? '',
        label: json['label'] as String? ?? '',
        value: json['value'] as String? ?? '',
      );
}

/// Allergen item (gluten/dairy/soy/sesame/egg/mustard).
class DishAllergen {
  const DishAllergen({
    required this.key,
    required this.name,
    required this.contains,
  });

  final String key;
  final String name;
  final bool contains;

  factory DishAllergen.fromJson(Map<String, dynamic> json) => DishAllergen(
        key: json['key'] as String? ?? '',
        name: json['name'] as String? ?? '',
        contains: json['contains'] as bool? ?? false,
      );
}

/// Stats row trên hero (prep / energy / branch).
class DishStats {
  const DishStats({
    required this.prepLabel,
    required this.prepValue,
    required this.energyLabel,
    required this.energyValue,
    required this.branchLabel,
    required this.branchValue,
  });

  final String prepLabel;
  final String prepValue;
  final String energyLabel;
  final String energyValue;
  final String branchLabel;
  final String branchValue;

  factory DishStats.fromJson(Map<String, dynamic> json) => DishStats(
        prepLabel: json['prep_label'] as String? ?? '',
        prepValue: json['prep_value'] as String? ?? '',
        energyLabel: json['energy_label'] as String? ?? '',
        energyValue: json['energy_value'] as String? ?? '',
        branchLabel: json['branch_label'] as String? ?? '',
        branchValue: json['branch_value'] as String? ?? '',
      );
}

/// Breadcrumb item (Home / Menu / Tên món).
class DishBreadcrumb {
  const DishBreadcrumb({required this.label, this.url});
  final String label;
  final String? url;

  factory DishBreadcrumb.fromJson(Map<String, dynamic> json) => DishBreadcrumb(
        label: json['label'] as String? ?? '',
        url: json['url'] as String?,
      );
}

/// SEO meta cho share/preview.
class DishSeo {
  const DishSeo({
    required this.title,
    required this.description,
    required this.keywords,
    this.ogImage,
  });

  final String title;
  final String description;
  final String keywords;
  final String? ogImage;

  factory DishSeo.fromJson(Map<String, dynamic> json) => DishSeo(
        title: json['title'] as String? ?? '',
        description: json['description'] as String? ?? '',
        keywords: json['keywords'] as String? ?? '',
        ogImage: json['og_image'] as String?,
      );
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
    this.hint,
  });

  final int id;
  final String name;
  final String? description;
  final String type;
  final bool isRequired;
  final int minSelect;
  final int maxSelect;
  final String? hint;
  final List<DishOptionItem> options;

  /// Helper cho UI - group là single-choice (radio) hay multi (checkbox) hay exclude.
  bool get isSingle => type == 'single';
  bool get isExclude => type == 'exclude';
  bool get isMultiple => type == 'multiple';

  factory DishOptionGroup.fromJson(Map<String, dynamic> json) => DishOptionGroup(
        id: (json['id'] as num).toInt(),
        name: json['name'] as String? ?? '',
        description: json['description'] as String?,
        type: json['type'] as String? ?? 'single',
        isRequired: json['is_required'] as bool? ?? false,
        minSelect: (json['min_select'] as num?)?.toInt() ?? 0,
        maxSelect: (json['max_select'] as num?)?.toInt() ?? 1,
        hint: json['hint'] as String?,
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
    this.description,
  });

  final int id;
  final String name;
  final String? description;
  final int priceDelta;
  final bool isDefault;

  factory DishOptionItem.fromJson(Map<String, dynamic> json) => DishOptionItem(
        id: (json['id'] as num).toInt(),
        name: json['name'] as String? ?? '',
        description: json['description'] as String?,
        priceDelta: (json['price_delta'] as num?)?.toInt() ?? 0,
        isDefault: json['is_default'] as bool? ?? false,
      );
}