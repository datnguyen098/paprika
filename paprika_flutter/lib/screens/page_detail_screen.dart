import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/i18n/ui_text.dart';
import '../core/utils/image_helper.dart';
import '../data/models/page_model.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';
import '../widgets/page_transition_loader.dart';

class PageDetailScreen extends ConsumerWidget {
  const PageDetailScreen({super.key, required this.slug});

  final String slug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final page = ref.watch(pageDetailProvider(slug));
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(activeRoute: AppRoutes.pages),
          Expanded(
            child: page.when(
              data: (item) => _PageDetailBody(page: item),
              loading: () => const Center(
                child: CircularProgressIndicator(color: AppColors.primary),
              ),
              error: (error, _) => SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _DetailHero(
                      title: UiText.of(context).pageLoadError,
                      subtitle: '$error',
                    ),
                    Padding(
                      padding: const EdgeInsets.all(16),
                      child: OutlinedButton.icon(
                        onPressed: () =>
                            ref.invalidate(pageDetailProvider(slug)),
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

class _PageDetailBody extends StatelessWidget {
  const _PageDetailBody({required this.page});

  final CmsPageDetail page;

  @override
  Widget build(BuildContext context) {
    final imageUrl = ImageHelper.url(page.image);
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _DetailHero(title: page.title, subtitle: page.excerpt),
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
                        _htmlToText(page.content),
                        style: const TextStyle(
                          color: AppColors.textPrimary,
                          fontSize: 15,
                          height: 1.65,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
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
  });

  final String title;
  final String subtitle;

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
                  context.go(AppRoutes.pages);
                },
                icon: const Icon(Icons.arrow_back, size: 17),
                label: Text(UiText.of(context).pages),
                style: TextButton.styleFrom(
                  foregroundColor: Colors.white,
                  padding: EdgeInsets.zero,
                ),
              ),
              const SizedBox(height: 10),
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
