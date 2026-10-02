import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/i18n/ui_text.dart';
import '../core/utils/image_helper.dart';
import '../data/models/post_model.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';
import '../widgets/page_transition_loader.dart';

class PostDetailScreen extends ConsumerWidget {
  const PostDetailScreen({super.key, required this.slug});

  final String slug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final post = ref.watch(postDetailProvider(slug));
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(activeRoute: AppRoutes.blog),
          Expanded(
            child: post.when(
              data: (item) => _PostDetailBody(post: item),
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.primary),
              ),
              error: (error, _) => SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _DetailHero(
                      title: UiText.of(context).postLoadError,
                      subtitle: '$error',
                    ),
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: OutlinedButton.icon(
                        onPressed: () =>
                            ref.invalidate(postDetailProvider(slug)),
                        icon: const Icon(Icons.refresh),
                        label: Text(UiText.of(context).retry),
                      ),
                    ),
                    const PaprikaFooter(),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

class _PostDetailBody extends StatelessWidget {
  const _PostDetailBody({required this.post});

  final PostDetail post;

  @override
  Widget build(BuildContext context) {
    final imageUrl = ImageHelper.url(post.thumbnail);
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _DetailHero(
            title: post.title,
            subtitle: post.excerpt,
            category: post.category?.name,
            publishedAt: post.publishedAt,
          ),
          Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(
                maxWidth: AppConstants.maxContentWidth,
              ),
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (imageUrl != null) ...[
                      ClipRRect(
                        borderRadius: BorderRadius.circular(AppConstants.radius),
                        child: AspectRatio(
                          aspectRatio: 16 / 9,
                          child: Image.network(
                            imageUrl,
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) =>
                                const SizedBox.shrink(),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                    ],
                    Container(
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(AppConstants.radius),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: SelectableText(
                        _htmlToText(post.content),
                        style: const TextStyle(
                          color: AppColors.textPrimary,
                          fontSize: 15,
                          height: 1.65,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                    if (post.relatedPosts.isNotEmpty) ...[
                      const SizedBox(height: 24),
                      Text(
                        UiText.of(context).relatedPosts,
                        style: const TextStyle(
                          color: AppColors.primaryStrong,
                          fontSize: 20,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      const SizedBox(height: 12),
                      for (final related in post.relatedPosts) ...[
                        _RelatedPostTile(post: related),
                        const SizedBox(height: 10),
                      ],
                    ],
                  ],
                ),
              ),
            ),
          ),
          const PaprikaFooter(),
        ],
      ),
    );
  }
}

class _DetailHero extends StatelessWidget {
  const _DetailHero({
    required this.title,
    required this.subtitle,
    this.category,
    this.publishedAt,
  });

  final String title;
  final String subtitle;
  final String? category;
  final DateTime? publishedAt;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.primaryStrong, AppColors.primary],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      padding: const EdgeInsets.fromLTRB(16, 18, 16, 30),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(
            maxWidth: AppConstants.maxContentWidth,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              TextButton.icon(
                onPressed: () {
                  context.showPageLoader();
                  context.go(AppRoutes.blog);
                },
                icon: const Icon(Icons.arrow_back, size: 17),
                label: Text(UiText.of(context).blog),
                style: TextButton.styleFrom(
                  foregroundColor: Colors.white,
                  padding: EdgeInsets.zero,
                ),
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  if (category != null && category!.isNotEmpty)
                    _HeroBadge(label: category!),
                  if (publishedAt != null)
                    _HeroBadge(label: _formatDate(publishedAt!)),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                title,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 32,
                  height: 1.08,
                  fontWeight: FontWeight.w900,
                ),
              ),
              if (subtitle.trim().isNotEmpty) ...[
                const SizedBox(height: 10),
                Text(
                  subtitle.trim(),
                  style: const TextStyle(
                    color: Color(0xFFD6E5DB),
                    fontSize: 14,
                    height: 1.5,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _HeroBadge extends StatelessWidget {
  const _HeroBadge({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: Colors.white24),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 11,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

class _RelatedPostTile extends StatelessWidget {
  const _RelatedPostTile({required this.post});

  final PostSummary post;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(AppConstants.radiusSm),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
        onTap: () {
          context.showPageLoader();
          context.go(AppRoutes.blogDetailPath(post.slug));
        },
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppConstants.radiusSm),
            border: Border.all(color: AppColors.border),
          ),
          child: Row(
            children: [
              const Icon(Icons.article_outlined, color: AppColors.primary),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  post.title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              const Icon(Icons.chevron_right, color: AppColors.textMuted),
            ],
          ),
        ),
      ),
    );
  }
}

String _formatDate(DateTime date) {
  final day = date.day.toString().padLeft(2, '0');
  final month = date.month.toString().padLeft(2, '0');
  return '$day/$month/${date.year}';
}

String _htmlToText(String html) {
  var text = html
      .replaceAll(RegExp(r'<\s*br\s*/?>', caseSensitive: false), '\n')
      .replaceAll(RegExp(r'</\s*p\s*>', caseSensitive: false), '\n\n')
      .replaceAll(RegExp(r'</\s*h[1-6]\s*>', caseSensitive: false), '\n\n')
      .replaceAll(RegExp(r'</\s*li\s*>', caseSensitive: false), '\n')
      .replaceAll(RegExp(r'<\s*li[^>]*>', caseSensitive: false), '- ')
      .replaceAll(RegExp(r'<[^>]+>'), '');

  const entities = {
    '&nbsp;': ' ',
    '&amp;': '&',
    '&quot;': '"',
    '&#039;': "'",
    '&apos;': "'",
    '&lt;': '<',
    '&gt;': '>',
  };

  for (final entry in entities.entries) {
    text = text.replaceAll(entry.key, entry.value);
  }

  return text
      .split('\n')
      .map((line) => line.trim())
      .join('\n')
      .replaceAll(RegExp(r'\n{3,}'), '\n\n')
      .trim();
}
