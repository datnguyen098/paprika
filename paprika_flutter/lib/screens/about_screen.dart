import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../data/models/about_model.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';

/// Trang Giới thiệu — hiển thị dữ liệu thật từ DB.
///
/// BE: GET /api/v1/about → { success, data: { title, content, image } }
/// Đúng 3 cột trong bảng `pages` (title, content, image).
/// Không hardcode — mọi text trên hero và body lấy từ
/// `AboutData` do API trả về.
class AboutScreen extends ConsumerWidget {
  const AboutScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final aboutAsync = ref.watch(aboutProvider);

    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(),
          Expanded(
            child: aboutAsync.when(
              data: (about) => _AboutContent(about: about),
              loading: () => const _LoadingContent(),
              error: (err, _) => _ErrorContent(
                message: err.toString(),
                onRetry: () => ref.invalidate(aboutProvider),
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

// ════════════════════════════════════════════════════════════════════════════
//  NỘI DUNG KHI ĐÃ CÓ DATA
// ════════════════════════════════════════════════════════════════════════════
class _AboutContent extends StatelessWidget {
  const _AboutContent({required this.about});
  final AboutData about;

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // 1. HERO — lấy title + image từ DB
          _HeroSection(title: about.title, image: about.image),

          const SizedBox(height: AppConstants.spaceLg),

          // 2. BODY — render content HTML từ DB
          if (about.content.isNotEmpty)
            _ContentSection(content: about.content),

          const SizedBox(height: AppConstants.spaceLg),

          // 3. CTA — nút đặt bàn / xem thực đơn
          const _CtaSection(),

          const PaprikaFooter(),
        ],
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════════════
//  1. HERO
// ════════════════════════════════════════════════════════════════════════════
class _HeroSection extends StatelessWidget {
  const _HeroSection({required this.title, required this.image});
  final String title;
  final String image;

  @override
  Widget build(BuildContext context) {
    // Tách title thành 2 phần nếu có dấu xuống dòng để highlight phần sau.
    final lines = title.split('\n').where((s) => s.trim().isNotEmpty).toList();
    final firstLine = lines.isNotEmpty ? lines.first : '';
    final secondLine = lines.length > 1 ? lines[1] : '';

    final hasImage = image.isNotEmpty;

    return Container(
      padding: const EdgeInsets.all(AppConstants.spaceLg),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [AppColors.primary, AppColors.primaryStrong],
        ),
        image: hasImage
            ? DecorationImage(
                image: NetworkImage(image),
                fit: BoxFit.cover,
                opacity: 0.35,
              )
            : null,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Badge
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: AppColors.accent.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.local_fire_department,
                    size: 14, color: AppColors.gold),
                SizedBox(width: 4),
                Text(
                  'VỀ PAPRIKA',
                  style: TextStyle(
                    color: AppColors.gold,
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 0.15,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Title từ DB — 2 dòng, dòng 2 highlight màu accent
          Text(
            firstLine,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 28,
              fontWeight: FontWeight.w900,
              letterSpacing: -0.5,
              height: 1.1,
            ),
          ),
          if (secondLine.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              secondLine,
              style: const TextStyle(
                color: AppColors.accentStrong,
                fontSize: 28,
                fontWeight: FontWeight.w900,
                letterSpacing: -0.5,
                height: 1.1,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════════════
//  2. BODY CONTENT (từ DB pages.content)
// ════════════════════════════════════════════════════════════════════════════
class _ContentSection extends StatelessWidget {
  const _ContentSection({required this.content});
  final String content;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
      child: Container(
        padding: const EdgeInsets.all(AppConstants.spaceMd),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppConstants.radius),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.auto_stories_outlined,
                    size: 18, color: AppColors.accent),
                SizedBox(width: 8),
                Text(
                  'Câu Chuyện Của Chúng Tôi',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w900,
                    color: AppColors.primaryStrong,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            _HtmlText(html: content),
          ],
        ),
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════════════
//  3. CTA
// ════════════════════════════════════════════════════════════════════════════
class _CtaSection extends StatelessWidget {
  const _CtaSection();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
      child: Container(
        padding: const EdgeInsets.all(AppConstants.spaceLg),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [AppColors.primary, AppColors.primaryStrong],
          ),
          borderRadius: BorderRadius.circular(AppConstants.radius),
        ),
        child: const Column(
          children: [
            Text(
              'Đã Sẵn Sàng\nKhai Phá Vị Giác?',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: Colors.white,
                fontSize: 22,
                fontWeight: FontWeight.w900,
                height: 1.2,
              ),
            ),
            SizedBox(height: 12),
            Text(
              'Đặt món trực tuyến để nhận ưu đãi giao hàng, hoặc đặt bàn trực tiếp tại không gian ấm cúng mang phong cách Việt - Hy Lạp của chúng tôi ngay hôm nay!',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.white60, fontSize: 12, height: 1.6),
            ),
          ],
        ),
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════════════
//  LOADING
// ════════════════════════════════════════════════════════════════════════════
class _LoadingContent extends StatelessWidget {
  const _LoadingContent();

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Padding(
        padding: const EdgeInsets.all(AppConstants.spaceMd),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(
              height: 180,
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppConstants.radius),
              ),
            ),
            const SizedBox(height: AppConstants.spaceLg),
            Container(
              height: 320,
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppConstants.radius),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════════════
//  ERROR
// ════════════════════════════════════════════════════════════════════════════
class _ErrorContent extends StatelessWidget {
  const _ErrorContent({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(AppConstants.spaceLg),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline,
                size: 56, color: AppColors.accentStrong),
            const SizedBox(height: 16),
            const Text(
              'Không tải được trang Giới thiệu',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w900,
                color: AppColors.textPrimary,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              message,
              style: const TextStyle(
                fontSize: 12,
                color: AppColors.textMuted,
              ),
              textAlign: TextAlign.center,
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 20),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh, size: 16),
              label: const Text('Thử lại'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.accentStrong,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(
                    horizontal: 24, vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ════════════════════════════════════════════════════════════════════════════
//  HTML → PLAIN TEXT (cho content từ DB pages.content)
// ════════════════════════════════════════════════════════════════════════════
class _HtmlText extends StatelessWidget {
  const _HtmlText({required this.html});
  final String html;

  @override
  Widget build(BuildContext context) {
    // Tách theo <p>...</p> để render mỗi đoạn là 1 paragraph riêng.
    final paragraphs = html
        .replaceAll('\xa0', ' ')
        .replaceAll(RegExp(r'<br\s*/?\s*>'), '\n')
        .split(RegExp(r'</p>'))
        .map((p) => p
            .replaceAll(RegExp(r'<[^>]*>'), '')
            .replaceAll(RegExp(r'\n{3,}'), '\n\n')
            .trim())
        .where((p) => p.isNotEmpty)
        .toList();

    if (paragraphs.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: paragraphs
          .map(
            (p) => Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Text(
                p,
                style: const TextStyle(
                  fontSize: 14,
                  color: AppColors.textPrimary,
                  height: 1.7,
                ),
              ),
            ),
          )
          .toList(),
    );
  }
}
