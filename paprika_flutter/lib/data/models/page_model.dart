import 'package:equatable/equatable.dart';

class CmsPageSummary extends Equatable {
  const CmsPageSummary({
    required this.id,
    required this.title,
    required this.slug,
    required this.excerpt,
    this.template,
    this.image,
  });

  final int id;
  final String title;
  final String slug;
  final String excerpt;
  final String? template;
  final String? image;

  factory CmsPageSummary.fromJson(Map<String, dynamic> json) {
    return CmsPageSummary(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
      excerpt: json['excerpt'] as String? ?? '',
      template: json['template'] as String?,
      image: json['image'] as String?,
    );
  }

  @override
  List<Object?> get props => [id, title, slug, excerpt, template, image];
}

class CmsPageSeo extends Equatable {
  const CmsPageSeo({
    required this.title,
    required this.description,
    required this.keywords,
    this.ogImage,
  });

  final String title;
  final String description;
  final String keywords;
  final String? ogImage;

  factory CmsPageSeo.fromJson(Map<String, dynamic> json) => CmsPageSeo(
        title: json['title'] as String? ?? '',
        description: json['description'] as String? ?? '',
        keywords: json['keywords'] as String? ?? '',
        ogImage: json['og_image'] as String?,
      );

  @override
  List<Object?> get props => [title, description, keywords, ogImage];
}

class CmsPageDetail extends CmsPageSummary {
  const CmsPageDetail({
    required super.id,
    required super.title,
    required super.slug,
    required super.excerpt,
    required this.content,
    super.template,
    super.image,
    this.seo,
  });

  final String content;
  final CmsPageSeo? seo;

  factory CmsPageDetail.fromJson(Map<String, dynamic> json) {
    final summary = CmsPageSummary.fromJson(json);
    final seo = json['seo'];
    return CmsPageDetail(
      id: summary.id,
      title: summary.title,
      slug: summary.slug,
      excerpt: summary.excerpt,
      template: summary.template,
      image: summary.image,
      content: json['content'] as String? ?? '',
      seo: seo is Map<String, dynamic> ? CmsPageSeo.fromJson(seo) : null,
    );
  }

  @override
  List<Object?> get props => [...super.props, content, seo];
}
