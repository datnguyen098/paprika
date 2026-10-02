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

class BlogScreen extends ConsumerWidget {
  const BlogScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final posts = ref.watch(postsProvider);
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(activeRoute: AppRoutes.blog),
          Expanded(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const _BlogHero(),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
                    child: Center(
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(
                          maxWidth: AppConstants.maxContentWidth,
                        ),
                        child: posts.when(
                          data: (page) => page.items.isEmpty
                              ? const _EmptyBlog()
                              : _PostGrid(posts: page.items),
                          loading: () => const Padding(
                            padding: EdgeInsets.all(30),
                            child: Center(
                              child: CircularProgressIndicator(
                                color: AppColors.primary,
                              ),
                            ),
                          ),
                          error: (error, _) => _ErrorBox(
                            message: '${UiText.of(context).blogLoadError} $error',
                            onRetry: () => ref.invalidate(postsProvider),
                          ),
                        ),
                      ),
                    ),
                  ),
                  const PaprikaFooter(),
                ],
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

class _BlogHero extends StatelessWidget {
  const _BlogHero();

  @override
  Widget build(BuildContext context) {
    final t = UiText.of(context);
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.primaryStrong, AppColors.primary],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      padding: const EdgeInsets.fromLTRB(16, 28, 16, 30),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(
            maxWidth: AppConstants.maxContentWidth,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                t.blogTitle,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 34,
                  height: 1.02,
                  fontWeight: FontWeight.w900,
                  fontStyle: FontStyle.italic,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                t.blogSubtitle,
                style: const TextStyle(
                  color: Color(0xFFD6E5DB),
                  fontSize: 14,
                  height: 1.45,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _PostGrid extends StatelessWidget {
  const _PostGrid({required this.posts});

  final List<PostSummary> posts;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final isWide = constraints.maxWidth >= 760;
        if (!isWide) {
          return Column(
            children: [
              for (final post in posts) ...[
                _PostCard(post: post),
                const SizedBox(height: 12),
              ],
            ],
          );
        }

        return Wrap(
          spacing: 14,
          runSpacing: 14,
          children: [
            for (final post in posts)
              SizedBox(
                width: (constraints.maxWidth - 14) / 2,
                child: _PostCard(post: post),
              ),
          ],
        );
      },
    );
  }
}

class _PostCard extends StatelessWidget {
  const _PostCard({required this.post});

  final PostSummary post;

  @override
  Widget build(BuildContext context) {
    final imageUrl = ImageHelper.url(post.thumbnail);
    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(AppConstants.radius),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppConstants.radius),
        onTap: () {
          context.showPageLoader();
          context.go(AppRoutes.blogDetailPath(post.slug));
        },
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppConstants.radius),
            border: Border.all(color: AppColors.border),
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              AspectRatio(
                aspectRatio: 16 / 9,
                child: imageUrl == null
                    ? const _ImageFallback()
                    : Image.network(
                        imageUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const _ImageFallback(),
                      ),
              ),
              Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Wrap(
                      spacing: 8,
                      runSpacing: 6,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [
                        if (post.category?.name.isNotEmpty == true)
                          _MiniBadge(label: post.category!.name),
                        if (post.isFeatured)
                          _MiniBadge(label: UiText.of(context).featured, accent: true),
                        if (post.publishedAt != null)
                          Text(
                            _formatDate(post.publishedAt!),
                            style: const TextStyle(
                              color: AppColors.textMuted,
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Text(
                      post.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppColors.textPrimary,
                        fontSize: 18,
                        height: 1.2,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    if (post.excerpt.trim().isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        post.excerpt.trim(),
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 13,
                          height: 1.45,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Text(
                          UiText.of(context).readMore,
                          style: const TextStyle(
                            color: AppColors.accent,
                            fontSize: 13,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        const SizedBox(width: 4),
                        const Icon(
                          Icons.arrow_forward,
                          color: AppColors.accent,
                          size: 16,
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ImageFallback extends StatelessWidget {
  const _ImageFallback();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.warm,
      alignment: Alignment.center,
      child: const Icon(
        Icons.article_outlined,
        color: AppColors.primary,
        size: 42,
      ),
    );
  }
}

class _MiniBadge extends StatelessWidget {
  const _MiniBadge({
    required this.label,
    this.accent = false,
  });

  final String label;
  final bool accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: accent ? AppColors.accentSoft : AppColors.successBg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: accent ? AppColors.accent : AppColors.successText,
          fontSize: 10,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

class _EmptyBlog extends StatelessWidget {
  const _EmptyBlog();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppConstants.radius),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          const Icon(Icons.article_outlined, color: AppColors.primary, size: 42),
          const SizedBox(height: 12),
          Text(
            UiText.of(context).emptyBlog,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: AppColors.textPrimary,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorBox extends StatelessWidget {
  const _ErrorBox({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppConstants.radius),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.errorText),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(UiText.of(context).retry),
          ),
        ],
      ),
    );
  }
}

String _formatDate(DateTime date) {
  final day = date.day.toString().padLeft(2, '0');
  final month = date.month.toString().padLeft(2, '0');
  return '$day/$month/${date.year}';
}
