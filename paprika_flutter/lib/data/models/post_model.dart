import 'package:equatable/equatable.dart';

class PostCategory extends Equatable {
  const PostCategory({
    required this.id,
    required this.name,
    required this.slug,
  });

  final int id;
  final String name;
  final String slug;

  factory PostCategory.fromJson(Map<String, dynamic> json) => PostCategory(
        id: (json['id'] as num?)?.toInt() ?? 0,
        name: json['name'] as String? ?? '',
        slug: json['slug'] as String? ?? '',
      );

  @override
  List<Object?> get props => [id, name, slug];
}

class PostSummary extends Equatable {
  const PostSummary({
    required this.id,
    required this.title,
    required this.slug,
    required this.excerpt,
    this.thumbnail,
    this.isFeatured = false,
    this.publishedAt,
    this.category,
  });

  final int id;
  final String title;
  final String slug;
  final String excerpt;
  final String? thumbnail;
  final bool isFeatured;
  final DateTime? publishedAt;
  final PostCategory? category;

  factory PostSummary.fromJson(Map<String, dynamic> json) {
    final category = json['category'];
    return PostSummary(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      excerpt: json['excerpt'] as String? ?? '',
      thumbnail: json['thumbnail'] as String?,
      isFeatured: json['is_featured'] as bool? ?? false,
      publishedAt: _parseDate(json['published_at']),
      category: category is Map<String, dynamic>
          ? PostCategory.fromJson(category)
          : null,
    );
  }

  @override
  List<Object?> get props => [
        id,
        title,
        slug,
        excerpt,
        thumbnail,
        isFeatured,
        publishedAt,
        category,
      ];
}

class PostSeo extends Equatable {
  const PostSeo({
    required this.title,
    required this.description,
    required this.keywords,
    this.ogImage,
  });

  final String title;
  final String description;
  final String keywords;
  final String? ogImage;

  factory PostSeo.fromJson(Map<String, dynamic> json) => PostSeo(
        title: json['title'] as String? ?? '',
        description: json['description'] as String? ?? '',
        keywords: json['keywords'] as String? ?? '',
        ogImage: json['og_image'] as String?,
      );

  @override
  List<Object?> get props => [title, description, keywords, ogImage];
}

class PostDetail extends PostSummary {
  const PostDetail({
    required super.id,
    required super.title,
    required super.slug,
    required super.excerpt,
    required this.content,
    super.thumbnail,
    super.isFeatured,
    super.publishedAt,
    super.category,
    this.seo,
    this.relatedPosts = const [],
  });

  final String content;
  final PostSeo? seo;
  final List<PostSummary> relatedPosts;

  factory PostDetail.fromJson(Map<String, dynamic> json) {
    final summary = PostSummary.fromJson(json);
    final seo = json['seo'];
    final related = json['related_posts'];
    return PostDetail(
      id: summary.id,
      title: summary.title,
      slug: summary.slug,
      excerpt: summary.excerpt,
      thumbnail: summary.thumbnail,
      isFeatured: summary.isFeatured,
      publishedAt: summary.publishedAt,
      category: summary.category,
      content: json['content'] as String? ?? '',
      seo: seo is Map<String, dynamic> ? PostSeo.fromJson(seo) : null,
      relatedPosts: related is List
          ? related
              .whereType<Map>()
              .map((item) =>
                  PostSummary.fromJson(Map<String, dynamic>.from(item)))
              .toList(growable: false)
          : const [],
    );
  }

  @override
  List<Object?> get props => [
        ...super.props,
        content,
        seo,
        relatedPosts,
      ];
}

DateTime? _parseDate(Object? raw) {
  if (raw is String && raw.isNotEmpty) {
    return DateTime.tryParse(raw)?.toLocal();
  }
  return null;
}
