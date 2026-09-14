import 'package:equatable/equatable.dart';
import 'package:flutter/material.dart';

/// Response cho GET /api/v1/home
/// BE trả: { success, message, data: { banners, categories, featured,
///   testimonials, latest_posts, promo_popup } }
class HomeData extends Equatable {
  const HomeData({
    required this.banners,
    required this.categories,
    required this.featured,
    required this.testimonials,
    required this.latestPosts,
    this.promoPopup,
  });

  final List<HomeBanner> banners;
  final List<HomeCategory> categories;
  final List<HomeFeaturedDish> featured;
  final List<HomeTestimonial> testimonials;
  final List<HomePost> latestPosts;
  final HomePromoPopup? promoPopup;

  factory HomeData.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};

    List<T> parseList<T>(
      String key,
      T Function(Map<String, dynamic>) parse,
    ) {
      final raw = data[key];
      if (raw is! List) return const [];
      return raw
          .whereType<Map<String, dynamic>>()
          .map(parse)
          .toList(growable: false);
    }

    return HomeData(
      banners: parseList('banners', HomeBanner.fromJson),
      categories: parseList('categories', HomeCategory.fromJson),
      featured: parseList('featured', HomeFeaturedDish.fromJson),
      testimonials: parseList('testimonials', HomeTestimonial.fromJson),
      latestPosts: parseList('latest_posts', HomePost.fromJson),
      promoPopup: data['promo_popup'] is Map<String, dynamic>
          ? HomePromoPopup.fromJson(data['promo_popup'] as Map<String, dynamic>)
          : null,
    );
  }

  @override
  List<Object?> get props => [
        banners,
        categories,
        featured,
        testimonials,
        latestPosts,
        promoPopup,
      ];
}

/// Banner (carousel hero trên home).
/// BE trả: { id, title, subtitle, image, cta_label, cta_link }
class HomeBanner extends Equatable {
  const HomeBanner({
    required this.id,
    required this.title,
    required this.subtitle,
    required this.image,
    required this.ctaLabel,
    required this.ctaLink,
  });

  final int id;
  final String title;
  final String subtitle;
  final String image;
  final String ctaLabel;
  final String ctaLink;

  factory HomeBanner.fromJson(Map<String, dynamic> json) => HomeBanner(
        id: (json['id'] as num).toInt(),
        title: json['title'] as String? ?? '',
        subtitle: json['subtitle'] as String? ?? '',
        image: json['image'] as String? ?? '',
        ctaLabel: json['cta_label'] as String? ?? '',
        ctaLink: json['cta_link'] as String? ?? '',
      );

  @override
  List<Object?> get props => [id, title, ctaLink];
}

/// Category cho grid "Khám phá" trên home.
/// BE trả: { id, name, icon } — icon là string ("appetizer","main","seafood",...)
/// FE map sang IconData qua [HomeCategory.iconData].
class HomeCategory extends Equatable {
  const HomeCategory({
    required this.id,
    required this.name,
    required this.icon,
  });

  final int id;
  final String name;
  final String icon;

  /// Map icon string (BE) sang IconData (Flutter).
  /// Mapping tham chiếu backend mock (HomeController). Có thể mở rộng
  /// khi BE đổi icon khác.
  static const _iconMap = <String, IconGlyph>{
    'appetizer': IconGlyph(Icons.restaurant),
    'main': IconGlyph(Icons.rice_bowl),
    'seafood': IconGlyph(Icons.set_meal),
    'hotpot': IconGlyph(Icons.soup_kitchen),
    'dessert': IconGlyph(Icons.cake),
    'drink': IconGlyph(Icons.local_drink),
  };

  /// Sub-model tránh phải import material.dart cho value-equality.
  IconGlyph get iconData => _iconMap[icon] ?? const IconGlyph(Icons.restaurant);

  factory HomeCategory.fromJson(Map<String, dynamic> json) => HomeCategory(
        id: (json['id'] as num).toInt(),
        name: json['name'] as String? ?? '',
        icon: json['icon'] as String? ?? '',
      );

  @override
  List<Object?> get props => [id, name, icon];
}

/// Wrapper nhỏ để HomeCategory có thể dùng trong Equatable mà không
/// kéo IconData vào props (IconData không Equatable).
/// Khi dùng thì render bằng `Icon(category.iconData.icon, ...)`.
class IconGlyph {
  const IconGlyph(this.icon);
  final Object icon; // thực ra là IconData nhưng tránh import material ở đây
}

/// Featured dish cho home.
/// BE trả: { id, name, image, price, old_price, rating, is_new }
class HomeFeaturedDish extends Equatable {
  const HomeFeaturedDish({
    required this.id,
    required this.name,
    required this.image,
    required this.price,
    this.oldPrice,
    this.rating,
    this.isNew = false,
  });

  final int id;
  final String name;
  final String image;
  final int price; // EUR minor units
  final int? oldPrice;
  final double? rating;
  final bool isNew;

  bool get hasDiscount => oldPrice != null && oldPrice! > price;

  int get discountPercent {
    if (!hasDiscount) return 0;
    return ((oldPrice! - price) * 100 / oldPrice!).round();
  }

  factory HomeFeaturedDish.fromJson(Map<String, dynamic> json) {
    return HomeFeaturedDish(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String? ?? '',
      image: json['image'] as String? ?? '',
      price: (json['price'] as num).toInt(),
      oldPrice: json['old_price'] != null
          ? (json['old_price'] as num).toInt()
          : null,
      rating: (json['rating'] as num?)?.toDouble(),
      isNew: json['is_new'] as bool? ?? false,
    );
  }

  @override
  List<Object?> get props => [id, name, price, oldPrice, rating, isNew];
}

/// Testimonial (review) trên home.
/// BE trả: { id, name, avatar, rating, content }
class HomeTestimonial extends Equatable {
  const HomeTestimonial({
    required this.id,
    required this.name,
    required this.avatar,
    required this.rating,
    required this.content,
  });

  final String id; // mock trả string (vd "r1")
  final String name;
  final String avatar;
  final int rating;
  final String content;

  factory HomeTestimonial.fromJson(Map<String, dynamic> json) {
    final raw = json['id'];
    return HomeTestimonial(
      id: raw is String ? raw : raw.toString(),
      name: json['name'] as String? ?? '',
      avatar: json['avatar'] as String? ?? '',
      rating: (json['rating'] as num?)?.toInt() ?? 5,
      content: json['content'] as String? ?? '',
    );
  }

  @override
  List<Object?> get props => [id, name, rating];
}

/// Latest post (blog) trên home.
/// BE trả: { id, title, excerpt, image, author, created_at }
class HomePost extends Equatable {
  const HomePost({
    required this.id,
    required this.title,
    required this.excerpt,
    required this.image,
    required this.author,
    required this.createdAt,
  });

  final int id;
  final String title;
  final String excerpt;
  final String image;
  final String author;
  final DateTime createdAt;

  factory HomePost.fromJson(Map<String, dynamic> json) {
    DateTime parseDate(Object? raw) {
      if (raw is String && raw.isNotEmpty) {
        return DateTime.tryParse(raw) ?? DateTime.now();
      }
      return DateTime.now();
    }

    return HomePost(
      id: (json['id'] as num).toInt(),
      title: json['title'] as String? ?? '',
      excerpt: json['excerpt'] as String? ?? '',
      image: json['image'] as String? ?? '',
      author: json['author'] as String? ?? '',
      createdAt: parseDate(json['created_at']),
    );
  }

  @override
  List<Object?> get props => [id, title, createdAt];
}

/// Promo popup (modal quảng cáo).
/// BE trả: { enabled, title, content, image, cta_label, cta_link, expires_at }
class HomePromoPopup extends Equatable {
  const HomePromoPopup({
    required this.enabled,
    required this.title,
    required this.content,
    required this.image,
    required this.ctaLabel,
    required this.ctaLink,
    this.expiresAt,
  });

  final bool enabled;
  final String title;
  final String content;
  final String image;
  final String ctaLabel;
  final String ctaLink;
  final DateTime? expiresAt;

  /// Popup còn hiệu lực (enabled và chưa hết hạn).
  bool get isActive {
    if (!enabled) return false;
    final exp = expiresAt;
    if (exp == null) return true;
    return DateTime.now().isBefore(exp);
  }

  factory HomePromoPopup.fromJson(Map<String, dynamic> json) {
    DateTime? parse(Object? raw) {
      if (raw is String && raw.isNotEmpty) {
        return DateTime.tryParse(raw);
      }
      return null;
    }

    return HomePromoPopup(
      enabled: json['enabled'] as bool? ?? false,
      title: json['title'] as String? ?? '',
      content: json['content'] as String? ?? '',
      image: json['image'] as String? ?? '',
      ctaLabel: json['cta_label'] as String? ?? '',
      ctaLink: json['cta_link'] as String? ?? '',
      expiresAt: parse(json['expires_at']),
    );
  }

  @override
  List<Object?> get props => [enabled, title, ctaLink, expiresAt];
}
