import 'package:equatable/equatable.dart';

class GalleryBranch extends Equatable {
  const GalleryBranch({
    required this.id,
    required this.name,
    this.images = const [],
  });

  final int id;
  final String name;
  final List<GalleryImageItem> images;

  factory GalleryBranch.fromJson(Map<String, dynamic> json) => GalleryBranch(
        id: (json['id'] as num?)?.toInt() ?? 0,
        name: json['name'] as String? ?? '',
        images: (json['images'] as List? ?? const [])
            .whereType<Map>()
            .map((item) =>
                GalleryImageItem.fromJson(Map<String, dynamic>.from(item)))
            .toList(growable: false),
      );

  @override
  List<Object?> get props => [id, name, images];
}

class GalleryImageBranch extends Equatable {
  const GalleryImageBranch({
    required this.id,
    required this.name,
  });

  final int id;
  final String name;

  factory GalleryImageBranch.fromJson(Map<String, dynamic> json) =>
      GalleryImageBranch(
        id: (json['id'] as num?)?.toInt() ?? 0,
        name: json['name'] as String? ?? '',
      );

  @override
  List<Object?> get props => [id, name];
}

class GalleryImageItem extends Equatable {
  const GalleryImageItem({
    required this.id,
    required this.title,
    required this.slug,
    required this.description,
    required this.altText,
    required this.image,
    this.isFeatured = false,
    this.branch,
  });

  final int id;
  final String title;
  final String slug;
  final String description;
  final String altText;
  final String image;
  final bool isFeatured;
  final GalleryImageBranch? branch;

  factory GalleryImageItem.fromJson(Map<String, dynamic> json) {
    final branch = json['branch'];
    return GalleryImageItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      description: json['description'] as String? ?? '',
      altText: json['alt_text'] as String? ?? '',
      image: json['image'] as String? ?? '',
      isFeatured: json['is_featured'] as bool? ?? false,
      branch: branch is Map<String, dynamic>
          ? GalleryImageBranch.fromJson(branch)
          : null,
    );
  }

  @override
  List<Object?> get props => [
        id,
        title,
        slug,
        description,
        altText,
        image,
        isFeatured,
        branch,
      ];
}

class GalleryData extends Equatable {
  const GalleryData({
    required this.images,
    required this.branches,
    required this.sharedImages,
  });

  final List<GalleryImageItem> images;
  final List<GalleryBranch> branches;
  final List<GalleryImageItem> sharedImages;

  factory GalleryData.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? const {};
    return GalleryData(
      images: (data['images'] as List? ?? const [])
          .whereType<Map>()
          .map((item) =>
              GalleryImageItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(growable: false),
      branches: (data['branches'] as List? ?? const [])
          .whereType<Map>()
          .map((item) => GalleryBranch.fromJson(Map<String, dynamic>.from(item)))
          .toList(growable: false),
      sharedImages: (data['shared_images'] as List? ?? const [])
          .whereType<Map>()
          .map((item) =>
              GalleryImageItem.fromJson(Map<String, dynamic>.from(item)))
          .toList(growable: false),
    );
  }

  @override
  List<Object?> get props => [images, branches, sharedImages];
}
