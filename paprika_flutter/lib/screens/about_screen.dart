import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/utils/image_helper.dart';
import '../data/models/about_model.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';

/// About screen — trang Giới thiệu.
///
/// Lấy data từ [aboutProvider]. Hiển thị:
///   - Cover image
///   - Title + subtitle
///   - Story / Mission / Vision
///   - Team members
///   - Stats grid
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
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Page hero
                  Container(
                    padding: const EdgeInsets.all(AppConstants.spaceMd),
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [AppColors.primary, AppColors.primaryStrong],
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 4,
                              height: 18,
                              decoration: BoxDecoration(
                                color: AppColors.gold,
                                borderRadius: BorderRadius.circular(2),
                              ),
                            ),
                            const SizedBox(width: 8),
                            const Text(
                              'GIỚI THIỆU',
                              style: TextStyle(
                                color: AppColors.gold,
                                fontSize: 11,
                                fontWeight: FontWeight.w900,
                                letterSpacing: 0.18,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        aboutAsync.maybeWhen(
                          data: (about) => Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                about.title,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 22,
                                  fontWeight: FontWeight.w900,
                                  letterSpacing: -0.3,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                about.subtitle,
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.85),
                                  fontSize: 13,
                                ),
                              ),
                            ],
                          ),
                          orElse: () => const SizedBox(height: 50),
                        ),
                      ],
                    ),
                  ),

                  // Content
                  Padding(
                    padding: const EdgeInsets.all(AppConstants.spaceMd),
                    child: aboutAsync.when(
                      data: (about) => _AboutContent(about: about),
                      loading: () => const _LoadingContent(),
                      error: (error, _) => _ErrorContent(
                        message: error.toString(),
                        onRetry: () => ref.invalidate(aboutProvider),
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

class _AboutContent extends StatelessWidget {
  const _AboutContent({required this.about});
  final AboutData about;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Cover image
        if (about.coverImage.isNotEmpty)
          ClipRRect(
            borderRadius: BorderRadius.circular(AppConstants.radius),
            child: AspectRatio(
              aspectRatio: 16 / 7,
              child: Image.network(
                ImageHelper.url(about.coverImage) ?? '',
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) => Container(
                  color: AppColors.warm,
                  child: const Center(
                    child: Icon(Icons.restaurant,
                        size: 64, color: AppColors.primary),
                  ),
                ),
              ),
            ),
          ),

        const SizedBox(height: AppConstants.spaceLg),

        // Story
        if (about.story.isNotEmpty) ...[
          _ContentSection(
            title: 'Câu chuyện của chúng tôi',
            icon: Icons.auto_stories_outlined,
            content: about.story,
          ),
          const SizedBox(height: AppConstants.spaceLg),
        ],

        // Mission
        if (about.mission.isNotEmpty) ...[
          _ContentSection(
            title: 'Sứ mệnh',
            icon: Icons.flag_outlined,
            content: about.mission,
          ),
          const SizedBox(height: AppConstants.spaceLg),
        ],

        // Vision
        if (about.vision.isNotEmpty) ...[
          _ContentSection(
            title: 'Tầm nhìn',
            icon: Icons.visibility_outlined,
            content: about.vision,
          ),
          const SizedBox(height: AppConstants.spaceLg),
        ],

        // Team
        if (about.teamMembers.isNotEmpty) ...[
          _SectionTitle(label: 'Đội ngũ'),
          const SizedBox(height: AppConstants.spaceMd),
          ...about.teamMembers.map(
            (m) => Padding(
              padding: const EdgeInsets.only(bottom: AppConstants.spaceSm),
              child: _TeamMemberCard(member: m),
            ),
          ),
          const SizedBox(height: AppConstants.spaceLg),
        ],

        // Stats
        if (about.stats.isNotEmpty) ...[
          _SectionTitle(label: 'Con số ấn tượng'),
          const SizedBox(height: AppConstants.spaceMd),
          _StatsGrid(stats: about.stats),
        ],
      ],
    );
  }
}

class _ContentSection extends StatelessWidget {
  const _ContentSection({
    required this.title,
    required this.icon,
    required this.content,
  });
  final String title;
  final IconData icon;
  final String content;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppConstants.spaceMd),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppConstants.radius),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 18, color: AppColors.accent),
              const SizedBox(width: 8),
              Text(
                title,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w900,
                  color: AppColors.primaryStrong,
                ),
              ),
            ],
          ),
          const SizedBox(height: AppConstants.spaceSm),
          Text(
            content,
            style: const TextStyle(
              fontSize: 14,
              color: AppColors.textPrimary,
              height: 1.7,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.label});
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 4,
          height: 18,
          decoration: BoxDecoration(
            color: AppColors.accent,
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(width: 8),
        Text(
          label.toUpperCase(),
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w900,
            color: AppColors.primaryStrong,
            letterSpacing: 0.1,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(child: Container(height: 1, color: AppColors.border)),
      ],
    );
  }
}

class _TeamMemberCard extends StatelessWidget {
  const _TeamMemberCard({required this.member});
  final TeamMember member;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppConstants.spaceMd),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppConstants.radius),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: AppColors.primary,
            child: member.avatar.isNotEmpty
                ? ClipOval(
                    child: Image.network(
                      ImageHelper.url(member.avatar) ?? '',
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Text(
                        member.name.isNotEmpty
                            ? member.name[0].toUpperCase()
                            : '?',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 18,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                  )
                : Text(
                    member.name.isNotEmpty
                        ? member.name[0].toUpperCase()
                        : '?',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  member.name,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w900,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  member.role,
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textMuted,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _StatsGrid extends StatelessWidget {
  const _StatsGrid({required this.stats});
  final List<AboutStat> stats;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final cols = constraints.maxWidth >= 600 ? 4 : 2;
        return Wrap(
          spacing: AppConstants.spaceSm,
          runSpacing: AppConstants.spaceSm,
          children: [
            for (final stat in stats)
              SizedBox(
                width: cols == 4
                    ? (constraints.maxWidth - 3 * AppConstants.spaceSm) / 4
                    : (constraints.maxWidth - AppConstants.spaceSm) / 2,
                child: Container(
                  padding: const EdgeInsets.all(AppConstants.spaceMd),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(AppConstants.radius),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Column(
                    children: [
                      Text(
                        stat.value,
                        style: const TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.w900,
                          color: AppColors.accentStrong,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        stat.label,
                        style: const TextStyle(
                          fontSize: 11,
                          color: AppColors.textMuted,
                          fontWeight: FontWeight.w700,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ),
              ),
          ],
        );
      },
    );
  }
}

class _LoadingContent extends StatelessWidget {
  const _LoadingContent();

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          height: 160,
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(AppConstants.radius),
          ),
        ),
        const SizedBox(height: AppConstants.spaceMd),
        ...List.generate(
          3,
          (i) => Padding(
            padding: const EdgeInsets.only(bottom: AppConstants.spaceMd),
            child: Container(
              height: 100,
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppConstants.radius),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _ErrorContent extends StatelessWidget {
  const _ErrorContent({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppConstants.spaceLg),
      child: Column(
        children: [
          const Icon(Icons.wifi_off, size: 48, color: AppColors.textMuted),
          const SizedBox(height: 12),
          const Text(
            'Không tải được trang giới thiệu',
            style: TextStyle(fontWeight: FontWeight.w700, color: AppColors.textPrimary),
          ),
          const SizedBox(height: 4),
          Text(message, style: const TextStyle(fontSize: 12, color: AppColors.textMuted)),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh, size: 16),
            label: const Text('Thử lại'),
          ),
        ],
      ),
    );
  }
}
