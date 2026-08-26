import 'package:equatable/equatable.dart';

/// Model cho Category - trả về từ GET /api/v1/categories
///
/// Sample response:
/// ```
/// {
///   "id": 1,
///   "name": "Món chính",
///   "slug": "mon-chinh",
///   "description": "...",
///   "image": "https://.../cat.webp",
///   "dishes_count": 12
/// }
/// ```
class Category extends Equatable {
  const Category({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    this.image,
    this.dishesCount = 0,
  });

  final int id;
  final String name;
  final String slug;
  final String? description;
  final String? image;
  final int dishesCount;

  factory Category.fromJson(Map<String, dynamic> json) {
    return Category(
      id: json['id'] as int,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String?,
      image: json['image'] as String?,
      dishesCount: json['dishes_count'] as int? ?? 0,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'slug': slug,
        'description': description,
        'image': image,
        'dishes_count': dishesCount,
      };

  @override
  List<Object?> get props => [id, name, slug];
}