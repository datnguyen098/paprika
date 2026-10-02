import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/utils/image_helper.dart';
import '../data/models/branch_model.dart';
import '../data/models/dish_model.dart';
import '../data/models/home_model.dart';
import '../l10n/generated/app_localizations.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/coming_soon.dart';
import '../widgets/contact_actions.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';
import '../widgets/page_transition_loader.dart';

/// Home screen — Landing page nhà hàng Paprika Patras (giống
/// `resources/views/storefront/home.blade.php` bên Laravel).
///
/// Cấu trúc:
///   Scaffold
///     body: Column([
///       PaprikaHeader (sticky top),
///       Expanded(SingleChildScrollView([
///         _HeroSection()                — dark green + stats (Laravel hero)
///         _PromotionsSection()          — promo cards dark green (NEW)
///         _BestSellersSection()         — featured.take(3), 1 cột banner dọc
///         _GallerySection()             — 3 ảnh không gian + text (NEW)
///         _ServicesSection()            — 3 cards Delivery/Pickup/Dine-in (NEW)
///         _TestimonialsSection()        — từ homeProvider
///         _AboutCard()                  — static brand card
///         PaprikaFooter,
///       ])),
///     ])
///     bottomNavigationBar: BottomNavBar
///
/// Tất cả data động đều dùng [homeProvider] — khi user đổi locale,
/// provider sẽ invalidate và fetch lại đúng ngôn ngữ.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(cartItemsCount: 0),
          Expanded(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisSize: MainAxisSize.min,
                children: [
                  const _HeroSection(), // dark green hero
                  const SizedBox(height: AppConstants.spaceLg),
                  const _PromotionsSection(), // promo cards (NEW)
                  const SizedBox(height: AppConstants.spaceLg),
                  _BestSellersSection(
                    eyebrow: AppLocalizations.of(context).sectionDiscover,
                    title: AppLocalizations.of(context).sectionBestSellers,
                  ),
                  const SizedBox(height: AppConstants.spaceLg),
                  const _GallerySection(), // 3 ảnh không gian (NEW)
                  const SizedBox(height: AppConstants.spaceLg),
                  const _ServicesSection(), // 3 cards (NEW)
                  const SizedBox(height: AppConstants.spaceLg),
                  const _BranchMapSection(),
                  const SizedBox(height: AppConstants.spaceLg),
                  _TestimonialsSection(
                    title: AppLocalizations.of(context).sectionTestimonials,
                  ),
                  const SizedBox(height: AppConstants.spaceLg),
                  _AboutCard(
                    eyebrow: AppLocalizations.of(context).sectionAbout,
                    title: AppLocalizations.of(context).aboutCardBrand,
                  ),
                  const SizedBox(height: AppConstants.spaceLg),
                  const PaprikaFooter(),
                ],
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: const BottomNavBar(),
      // 2 nút nổi góc phải dưới: gọi điện + chat (giống screenshot)
      floatingActionButton: const _FloatingContactButtons(),
      floatingActionButtonLocation: FloatingActionButtonLocation.endFloat,
    );
  }
}

// ===========================================================================
// SECTION TITLE — eyebrow label (đỏ) + heading lớn + divider
// ===========================================================================
class _SectionTitle extends StatelessWidget {
  const _SectionTitle({
    required this.text,
    this.eyebrow,
  });
  final String text;
  final String? eyebrow;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Eyebrow label (nếu có) - màu đỏ như PHP
          if (eyebrow != null) ...[
            Text(
              eyebrow!.toUpperCase(),
              style: const TextStyle(
                color: AppColors.accent,
                fontSize: 10,
                fontWeight: FontWeight.w900,
                letterSpacing: 1.5,
              ),
            ),
            const SizedBox(height: 4),
          ],
          // Heading + divider
          Row(
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
                text,
                style: const TextStyle(
                  color: AppColors.primaryStrong,
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                  letterSpacing: -0.2,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(child: Container(height: 1, color: AppColors.border)),
            ],
          ),
        ],
      ),
    );
  }
}

// ===========================================================================
// HERO SECTION — dark green, dynamic title/subtitle/CTA (giống Laravel hero)
// ===========================================================================
class _HeroSection extends ConsumerWidget {
  const _HeroSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeAsync = ref.watch(homeProvider);

    // Lấy banner đầu tiên làm hero (nếu có), fallback về text tĩnh.
    final banner = homeAsync.maybeWhen(
      data: (h) => h.banners.isNotEmpty ? h.banners.first : null,
      orElse: () => null,
    );

    final l = AppLocalizations.of(context);

    // Title và subtitle cho Hero (khớp PHP)
    final title = banner?.title.isNotEmpty == true
        ? banner!.title
        : l.heroTitleFallback;
    final subtitle = banner?.subtitle.isNotEmpty == true
        ? banner!.subtitle
        : l.heroSubtitleFallback;

    return Container(
      width: double.infinity,
      // Bỏ margin để Hero full-width, che viền trắng 2 bên
      decoration: BoxDecoration(
        // Gradient phủ toàn bộ Hero: xanh đậm → nhạt → trong suốt (giống PHP)
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            const Color(0xFF043427), // Xanh đậm ở TOP
            const Color(0xFF064535), // 20%
            const Color(0xFF0A5540), // 40%
            const Color(0xFF106B50), // 60%
            const Color(0xFF1A8060), // 80% - bắt đầu nhạt rõ
            const Color(0xFF1A8060).withValues(alpha: 0.6), // 90% - rất nhạt
            const Color(0xFF1A8060).withValues(alpha: 0.0), // 100% - transparent
          ],
          stops: const [0.0, 0.2, 0.4, 0.6, 0.78, 0.9, 1.0],
        ),
        borderRadius: const BorderRadius.only(
          topLeft: Radius.zero, // Không bo góc trên
          topRight: Radius.zero,
          bottomLeft: Radius.circular(18), // Bo góc dưới
          bottomRight: Radius.circular(18),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppConstants.spaceMd),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Badge "Đặt món online" - pill shape, margin-bottom lớn như PHP (mb-6)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: AppColors.accent,
                borderRadius: BorderRadius.circular(999),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const _PulsingDot(),
                  const SizedBox(width: 8),
                  Text(
                    l.heroBadge,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.5,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24), // Giống PHP: mb-6

            // Title - responsive, lớn như PHP (text-4xl sm:text-5xl)
            LayoutBuilder(
              builder: (context, constraints) {
                double fontSize;
                if (constraints.maxWidth < 320) {
                  fontSize = 32;
                } else if (constraints.maxWidth < 380) {
                  fontSize = 38;
                } else {
                  fontSize = 44; // ~text-4xl
                }
                return Text(
                  title.toUpperCase(),
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: fontSize,
                    fontWeight: FontWeight.w900,
                    fontStyle: FontStyle.italic,
                    height: 1.1,
                    letterSpacing: -1,
                  ),
                );
              },
            ),
            const SizedBox(height: 16), // mb-4

            // Description - giống PHP (max-w-lg, leading-relaxed)
            SizedBox(
              width: 300, // max-w-lg approximation
              child: Text(
                subtitle,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.8),
                  fontSize: 16,
                  height: 1.6, // leading-relaxed
                ),
              ),
            ),
            const SizedBox(height: 32), // mb-8

            // CTA Button - padding lớn hơn như PHP (px-8 py-4)
            SizedBox(
              width: double.infinity,
              child: Material(
                color: AppColors.accent,
                borderRadius: BorderRadius.circular(999), // rounded-full
                elevation: 4,
                shadowColor: const Color(0xFF7F1D1D).withValues(alpha: 0.4), // shadow-red-900/30
                child: InkWell(
                  borderRadius: BorderRadius.circular(999),
                  onTap: () {
                    context.showPageLoader();
                    context.push(AppRoutes.menu);
                  },
                  child: Container(
                    height: 52, // py-4
                    padding: const EdgeInsets.symmetric(horizontal: 32), // px-8
                    alignment: Alignment.center,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          l.heroCta,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 1.5,
                          ),
                        ),
                        const SizedBox(width: 8),
                        const Icon(
                          Icons.arrow_forward,
                          color: Colors.white,
                          size: 18,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 24), // Khoảng cách với benefits

            // Benefits — 3 cột với divider phía trên (giống PHP)
            Container(
              padding: const EdgeInsets.only(top: 16),
              decoration: BoxDecoration(
                border: Border(
                  top: BorderSide(
                    color: Colors.white.withValues(alpha: 0.15),
                    width: 1,
                  ),
                ),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Expanded(
                    child: _Stat(
                      value: l.statFreshValue,
                      label: l.statFreshLabel,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _Stat(
                      value: l.statFastValue,
                      label: l.statFastLabel,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _Stat(
                      value: l.statEasyValue,
                      label: l.statEasyLabel,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PulsingDot extends StatefulWidget {
  const _PulsingDot();
  @override
  State<_PulsingDot> createState() => _PulsingDotState();
}

class _PulsingDotState extends State<_PulsingDot>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat(reverse: true);
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: _ctrl,
      child: Container(
        width: 6,
        height: 6,
        decoration: const BoxDecoration(
          color: AppColors.gold,
          shape: BoxShape.circle,
        ),
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.value, required this.label});
  final String value;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          value,
          textAlign: TextAlign.center,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.w900,
            height: 1.2,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          textAlign: TextAlign.center,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.6),
            fontSize: 10,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.5,
          ),
        ),
      ],
    );
  }
}

class _PrimaryCta extends StatelessWidget {
  const _PrimaryCta({
    required this.icon,
    required this.label,
    this.route,
  });

  final IconData icon;
  final String label;
  final String? route;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.accent,
      borderRadius: BorderRadius.circular(AppConstants.radiusSm),
      elevation: 4,
      shadowColor: AppColors.accent.withValues(alpha: 0.4),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
        onTap: route == null
            ? null
            : () {
                context.showPageLoader();
                context.push(route!);
              },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          alignment: Alignment.center,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, color: Colors.white, size: 18),
              const SizedBox(width: 6),
              Text(
                label,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 13,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 0.14,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ===========================================================================
// PROMOTIONS SECTION — dark green gradient cards (giống Laravel promo)
// ===========================================================================
class _SecondaryCta extends StatelessWidget {
  // Giữ lại để dùng cho các screen khác (vd about) — Home không dùng
  // vì chỉ có 1 CTA "ĐẶT MÓN NGAY".
  // ignore: unused_element_parameter
  const _SecondaryCta({required this.icon, required this.label, this.route});
  final IconData icon;
  final String label;
  final String? route;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white.withValues(alpha: 0.12),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
        side: const BorderSide(color: Colors.white38),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
        onTap: () {
          context.showPageLoader();
          context.push(route ?? AppRoutes.reservation);
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          alignment: Alignment.center,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, color: Colors.white, size: 18),
              const SizedBox(width: 6),
              Text(
                label,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 13,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 0.14,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ===========================================================================
// PROMOTIONS SECTION — dark green gradient cards (giống Laravel promo)
// ===========================================================================
class _PromotionsSection extends ConsumerWidget {
  const _PromotionsSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeAsync = ref.watch(homeProvider);

    return homeAsync.when(
      data: (home) {
        if (home.promotions.isEmpty) return const SizedBox.shrink();
        final l = AppLocalizations.of(context);
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Eyebrow + title (giống Laravel "Ưu đãi đặc biệt")
            Padding(
              padding: const EdgeInsets.symmetric(
                  horizontal: AppConstants.spaceMd),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    l.sectionPromotions.toUpperCase(),
                    style: const TextStyle(
                      color: AppColors.accent,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.5,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    l.sectionPromotionsTitle,
                    style: const TextStyle(
                      color: AppColors.primaryStrong,
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: AppConstants.spaceMd),
            // 1 cột dọc (mobile-friendly), mỗi promo full-width
            Padding(
              padding: const EdgeInsets.symmetric(
                  horizontal: AppConstants.spaceMd),
              child: Column(
                children: [
                  for (final promo in home.promotions) ...[
                    _PromotionCard(promo: promo),
                    const SizedBox(height: AppConstants.spaceMd),
                  ],
                ],
              ),
            ),
          ],
        );
      },
      loading: () => const SizedBox.shrink(),
      error: (_, __) => const SizedBox.shrink(),
    );
  }
}

class _PromotionCard extends StatelessWidget {
  const _PromotionCard({required this.promo});
  final HomePromotion promo;

  @override
  Widget build(BuildContext context) {
    final imageUrl =
        promo.image.isNotEmpty ? ImageHelper.url(promo.image) : null;

    return Material(
      color: AppColors.primaryStrong,
      borderRadius: BorderRadius.circular(AppConstants.radiusLg),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () {
          // TODO: điều hướng theo promo.ctaLink sau khi có route map
          debugPrint('🟢 Promo tap → ${promo.ctaLink}');
        },
        child: Stack(
          children: [
            // Background image (nếu có)
            if (imageUrl != null)
              Positioned.fill(
                child: Opacity(
                  opacity: 0.35,
                  child: Image.network(
                    imageUrl,
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => const SizedBox.expand(),
                  ),
                ),
              ),
            // Gradient overlay
            Positioned.fill(
              child: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.centerLeft,
                    end: Alignment.centerRight,
                    colors: [
                      AppColors.primaryStrong.withValues(alpha: 0.95),
                      AppColors.primary.withValues(alpha: 0.55),
                    ],
                  ),
                ),
              ),
            ),
            // Decorative blobs
            Positioned(
              top: -30,
              right: -30,
              child: Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  color: AppColors.gold.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
              ),
            ),
            // Content
            Padding(
              padding: const EdgeInsets.all(AppConstants.spaceLg),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (promo.badge.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.gold,
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        promo.badge.toUpperCase(),
                        style: const TextStyle(
                          color: AppColors.primaryStrong,
                          fontSize: 10,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.8,
                        ),
                      ),
                    )
                  else
                    // Fallback badge mặc định (giống screenshot)
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.gold,
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        AppLocalizations.of(context).promotionsBadgeFallback,
                        style: const TextStyle(
                          color: AppColors.primaryStrong,
                          fontSize: 10,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.8,
                        ),
                      ),
                    ),
                  const SizedBox(height: 10),
                  Text(
                    promo.title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      fontStyle: FontStyle.italic,
                      height: 1.15,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    promo.subtitle.isNotEmpty
                        ? promo.subtitle
                        : promo.description,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.8),
                      fontSize: 13,
                      height: 1.45,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 14),
                  if (promo.ctaLabel.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 8),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            promo.ctaLabel,
                            style: const TextStyle(
                              color: AppColors.primaryStrong,
                              fontSize: 12,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 0.6,
                            ),
                          ),
                          const SizedBox(width: 6),
                          const Icon(Icons.arrow_forward,
                              size: 14, color: AppColors.primaryStrong),
                        ],
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ===========================================================================
// CATEGORIES SECTION — REMOVED: Laravel storefront home không có categories,
// chỉ có bestsellers + gallery + services. Nếu cần khám phá theo danh mục,
// user dùng menu_screen (route /menu).
// ===========================================================================

// ===========================================================================
// BEST SELLERS — featured.take(3), 1 cột banner dọc (giống Laravel bestsellers)
// ===========================================================================
class _BestSellersSection extends ConsumerWidget {
  const _BestSellersSection({required this.eyebrow, required this.title});
  final String eyebrow;
  final String title;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeAsync = ref.watch(homeProvider);
    final featuredFallbackAsync = ref.watch(featuredDishesProvider);
    final l = AppLocalizations.of(context);

    return homeAsync.when(
      data: (home) {
        if (home.featured.isNotEmpty) {
          return _buildContent(home.featured.take(3).toList(), l);
        }
        return featuredFallbackAsync.when(
          data: (dishes) {
            final bestSellers = dishes
                .take(3)
                .map(_featuredDishFromDish)
                .toList(growable: false);
            if (bestSellers.isEmpty) return const SizedBox.shrink();
            return _buildContent(bestSellers, l);
          },
          loading: () => const SizedBox(
            height: 120,
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (_, __) => const SizedBox.shrink(),
        );
      },
      loading: () => const SizedBox(
        height: 120,
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (_, __) => _buildFallbackContent(featuredFallbackAsync, l),
    );
  }

  Widget _buildFallbackContent(
    AsyncValue<List<Dish>> featuredFallbackAsync,
    AppLocalizations l,
  ) {
    return featuredFallbackAsync.when(
      data: (dishes) {
        final bestSellers = dishes
            .take(3)
            .map(_featuredDishFromDish)
            .toList(growable: false);
        if (bestSellers.isEmpty) return const SizedBox.shrink();
        return _buildContent(bestSellers, l);
      },
      loading: () => const SizedBox(
        height: 120,
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (_, __) => const SizedBox.shrink(),
    );
  }

  Widget _buildContent(
    List<HomeFeaturedDish> bestSellers,
    AppLocalizations l,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Title (giống Laravel "best seller" eyebrow + title)
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
          child: Column(
            children: [
              Text(
                eyebrow.toUpperCase(),
                style: const TextStyle(
                  color: AppColors.accent,
                  fontSize: 10,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 1.5,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                title,
                style: const TextStyle(
                  color: AppColors.primaryStrong,
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                  fontStyle: FontStyle.italic,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                l.sectionBestSellersSubtitle,
                style: TextStyle(
                  color: AppColors.textMuted.withValues(alpha: 0.85),
                  fontSize: 12,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
        const SizedBox(height: AppConstants.spaceMd),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
          child: Column(
            children: [
              for (final dish in bestSellers) ...[
                _BestSellerCard(dish: dish),
                const SizedBox(height: 12),
              ],
            ],
          ),
        ),
      ],
    );
  }

  HomeFeaturedDish _featuredDishFromDish(Dish dish) {
    return HomeFeaturedDish(
      id: dish.id,
      name: dish.name,
      image: dish.image ?? '',
      price: dish.currentPrice,
      oldPrice: dish.hasDiscount ? dish.price : null,
      rating: null,
      isNew: false,
    );
  }
}

class _BestSellerCard extends StatelessWidget {
  const _BestSellerCard({required this.dish});
  final HomeFeaturedDish dish;

  @override
  Widget build(BuildContext context) {
    final imageUrl =
        dish.image.isNotEmpty ? ImageHelper.url(dish.image) : null;
    final hasOldPrice = dish.hasDiscount;
    final priceStr = (dish.price / 100).toStringAsFixed(2);
    final oldPriceStr =
        hasOldPrice ? (dish.oldPrice! / 100).toStringAsFixed(2) : null;

    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(AppConstants.radius),
      elevation: 1,
      shadowColor: Colors.black.withValues(alpha: 0.06),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () {
          context.showPageLoader();
          context.push(AppRoutes.dishDetailPath(dish.id));
        },
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ============ ẢNH FULL-WIDTH 16:9 ============
            AspectRatio(
              aspectRatio: 16 / 9,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (imageUrl != null)
                    Image.network(
                      imageUrl,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        decoration: const BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [AppColors.accentSoft, AppColors.warm],
                          ),
                        ),
                        child: const Icon(Icons.restaurant,
                            size: 64, color: AppColors.accentStrong),
                      ),
                      loadingBuilder: (_, child, prog) {
                        if (prog == null) return child;
                        return Container(
                          color: AppColors.accentSoft.withValues(alpha: 0.3),
                          child: const Center(
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                        );
                      },
                    )
                  else
                    Container(
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [AppColors.accentSoft, AppColors.warm],
                        ),
                      ),
                      child: const Icon(Icons.restaurant,
                          size: 64, color: AppColors.accentStrong),
                    ),
                  // Gradient đen cho badge đọc rõ
                  Positioned.fill(
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Colors.transparent,
                            Colors.black.withValues(alpha: 0.55),
                          ],
                          stops: const [0.55, 1.0],
                        ),
                      ),
                    ),
                  ),
                  // Tags (NEW / -%)
                  Positioned(
                    top: 12,
                    left: 12,
                    child: Row(
                      children: [
                        if (dish.isNew)
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: AppColors.accent,
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(
                              AppLocalizations.of(context).dishTagNew,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                          ),
                        if (dish.isNew && hasOldPrice)
                          const SizedBox(width: 6),
                        if (hasOldPrice)
                          Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: AppColors.primaryStrong,
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(
                              '-${dish.discountPercent}%',
                              style: const TextStyle(
                                color: AppColors.gold,
                                fontSize: 11,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            // ============ TEXT DƯỚI ẢNH ============
            Padding(
              padding: const EdgeInsets.all(AppConstants.spaceMd),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    dish.name,
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 8),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      if (dish.rating != null) ...[
                        const Icon(Icons.star,
                            size: 16, color: AppColors.gold),
                        const SizedBox(width: 4),
                        Text(
                          dish.rating!.toStringAsFixed(1),
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                            color: AppColors.textMuted,
                          ),
                        ),
                        const SizedBox(width: 16),
                      ],
                      const Spacer(),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(
                            '€$priceStr',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w900,
                              color: hasOldPrice
                                  ? AppColors.primaryStrong
                                  : AppColors.textPrimary,
                            ),
                          ),
                          if (oldPriceStr != null)
                            Text(
                              '€$oldPriceStr',
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w600,
                                color: AppColors.textMuted,
                                decoration: TextDecoration.lineThrough,
                              ),
                            ),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ===========================================================================
// GALLERY SECTION — 3 ảnh không gian + text (giống Laravel gallery)
// ===========================================================================
class _GallerySection extends ConsumerWidget {
  const _GallerySection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeAsync = ref.watch(homeProvider);

    return homeAsync.when(
      data: (home) {
        final images = home.galleryImages.isNotEmpty
            ? home.galleryImages.take(3).toList()
            : _fallbackGalleryImages(AppLocalizations.of(context));
        return Container(
          margin: const EdgeInsets.symmetric(
              horizontal: AppConstants.spaceMd),
          padding: const EdgeInsets.all(AppConstants.spaceLg),
          decoration: BoxDecoration(
            color: AppColors.primaryStrong,
            borderRadius: BorderRadius.circular(AppConstants.radiusLg),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Eyebrow badge
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.accent,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const _PulsingDot(),
                    const SizedBox(width: 6),
                    Text(
                      AppLocalizations.of(context).galleryBadge,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 1.5,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 10),
              Text(
                AppLocalizations.of(context).sectionGallery,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 26,
                  fontWeight: FontWeight.w900,
                  fontStyle: FontStyle.italic,
                  height: 1.1,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                AppLocalizations.of(context).sectionGallerySubtitle,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.75),
                  fontSize: 13,
                  height: 1.5,
                ),
              ),
              const SizedBox(height: AppConstants.spaceMd),
              // 3 ảnh xếp dọc, ảnh giữa thụt xuống (giống Laravel sm:mt-8)
              for (int i = 0; i < images.length; i++) ...[
                if (i == 1) const SizedBox(height: 16),
                _GalleryTile(image: images[i], offsetDown: i == 1),
                if (i < images.length - 1)
                  const SizedBox(height: 10),
              ],
              const SizedBox(height: AppConstants.spaceMd),
              Center(
                child: Material(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(999),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(999),
                    onTap: () {
                      // TODO: navigate /gallery khi có route
                      debugPrint('🟢 Gallery tap → /gallery');
                    },
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 18, vertical: 10),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            AppLocalizations.of(context).promotionsViewMore,
                            style: const TextStyle(
                              color: AppColors.primaryStrong,
                              fontSize: 12,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 1.0,
                            ),
                          ),
                          const SizedBox(width: 6),
                          const Icon(Icons.arrow_forward,
                              size: 14, color: AppColors.primaryStrong),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
      loading: () => const SizedBox.shrink(),
      error: (_, __) => const SizedBox.shrink(),
    );
  }

  List<HomeGalleryImage> _fallbackGalleryImages(AppLocalizations l) {
    return [
      HomeGalleryImage(
        id: -1,
        title: l.sectionGallery,
        altText: l.sectionGallery,
        image: 'paprika/gallery/space-1.jpg',
        branchName: 'Paprika Patras',
      ),
      HomeGalleryImage(
        id: -2,
        title: l.galleryBadge,
        altText: l.galleryBadge,
        image: 'paprika/gallery/space-2.jpg',
        branchName: 'Paprika Patras',
      ),
      HomeGalleryImage(
        id: -3,
        title: l.sectionGallerySubtitle,
        altText: l.sectionGallerySubtitle,
        image: 'paprika/gallery/space-3.jpg',
        branchName: 'Paprika Patras',
      ),
    ];
  }
}

class _GalleryTile extends StatelessWidget {
  const _GalleryTile({required this.image, this.offsetDown = false});
  final HomeGalleryImage image;
  final bool offsetDown;

  @override
  Widget build(BuildContext context) {
    final imageUrl = ImageHelper.url(image.image);

    return Material(
      borderRadius: BorderRadius.circular(AppConstants.radius),
      clipBehavior: Clip.antiAlias,
      color: AppColors.primaryStrong,
      child: InkWell(
        onTap: () => debugPrint('🟢 Gallery image tap → ${image.id}'),
        child: Stack(
          children: [
            AspectRatio(
              aspectRatio: 4 / 3,
              child: imageUrl != null
                  ? Image.network(
                      imageUrl,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Container(
                        color: AppColors.primaryMuted,
                        child: const Icon(Icons.photo_library,
                            size: 48, color: AppColors.gold),
                      ),
                    )
                  : Container(
                      color: AppColors.primaryMuted,
                      child: const Icon(Icons.photo_library,
                          size: 48, color: AppColors.gold),
                    ),
            ),
            // Gradient đen phía dưới
            Positioned.fill(
              child: DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.transparent,
                      Colors.black.withValues(alpha: 0.7),
                    ],
                    stops: const [0.5, 1.0],
                  ),
                ),
              ),
            ),
            // Title + branch
            Positioned(
              left: 12,
              right: 12,
              bottom: 12,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    image.title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 13,
                      fontWeight: FontWeight.w900,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (image.branchName.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(
                      image.branchName.toUpperCase(),
                      style: const TextStyle(
                        color: AppColors.gold,
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.8,
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ===========================================================================
// SERVICES SECTION — 3 cards (Delivery/Pickup/Dine-in) giống Laravel
// ===========================================================================
class _ServicesSection extends ConsumerWidget {
  const _ServicesSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = AppLocalizations.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding:
              const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
          child: Column(
            children: [
              Text(
                l.sectionServices.toUpperCase(),
                style: const TextStyle(
                  color: AppColors.accent,
                  fontSize: 10,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 1.5,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                l.sectionServicesTitle,
                style: const TextStyle(
                  color: AppColors.primaryStrong,
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                  fontStyle: FontStyle.italic,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                l.sectionServicesSubtitle,
                style: TextStyle(
                  color: AppColors.textMuted.withValues(alpha: 0.85),
                  fontSize: 12,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
        const SizedBox(height: AppConstants.spaceMd),
        Padding(
          padding:
              const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
          child: Column(
            children: [
              _ServiceCard(
                title: l.serviceDeliveryTitle,
                subtitle: l.serviceDeliverySubtitle,
                ctaLabel: l.serviceDeliveryCta,
                route: AppRoutes.menu,
                icon: Icons.local_shipping,
                gradientColors: const [AppColors.primary, AppColors.primaryStrong],
                accentColor: AppColors.gold,
              ),
              const SizedBox(height: 12),
              _ServiceCard(
                title: l.servicePickupTitle,
                subtitle: l.servicePickupSubtitle,
                ctaLabel: l.servicePickupCta,
                route: AppRoutes.menu,
                icon: Icons.shopping_bag,
                gradientColors: const [AppColors.accent, AppColors.accentStrong],
                accentColor: Colors.white,
              ),
              const SizedBox(height: 12),
              _ServiceCard(
                title: l.serviceDineInTitle,
                subtitle: l.serviceDineInSubtitle,
                ctaLabel: l.serviceDineInCta,
                route: AppRoutes.reservation,
                icon: Icons.event_seat,
                gradientColors: const [AppColors.brownDeep, AppColors.brownDark],
                accentColor: AppColors.gold,
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _ServiceCard extends StatelessWidget {
  const _ServiceCard({
    required this.title,
    required this.subtitle,
    required this.ctaLabel,
    required this.route,
    required this.icon,
    required this.gradientColors,
    required this.accentColor,
  });

  final String title;
  final String subtitle;
  final String ctaLabel;
  final String route;
  final IconData icon;
  final List<Color> gradientColors;
  final Color accentColor;

  @override
  Widget build(BuildContext context) {
    return Material(
      borderRadius: BorderRadius.circular(AppConstants.radiusLg),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () {
          context.showPageLoader();
          context.push(route);
        },
        child: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: gradientColors,
            ),
          ),
          padding: const EdgeInsets.all(AppConstants.spaceLg),
          child: Row(
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(AppConstants.radius),
                ),
                child: Icon(icon, color: accentColor, size: 30),
              ),
              const SizedBox(width: AppConstants.spaceMd),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      title.toUpperCase(),
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.w900,
                        fontStyle: FontStyle.italic,
                        letterSpacing: 0.4,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitle,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.75),
                        fontSize: 12,
                        height: 1.4,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Text(
                          ctaLabel,
                          style: TextStyle(
                            color: accentColor,
                            fontSize: 12,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 0.4,
                          ),
                        ),
                        const SizedBox(width: 4),
                        Icon(Icons.arrow_forward,
                            size: 14, color: accentColor),
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

// ===========================================================================
// BRANCH MAP SECTION — mirrors Laravel storefront.partials.branch-map
// ===========================================================================
class _BranchMapSection extends ConsumerWidget {
  const _BranchMapSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final branchesAsync = ref.watch(branchesProvider);

    return branchesAsync.when(
      data: (branches) {
        if (branches.isEmpty) return const SizedBox.shrink();
        return _BranchMapCard(branch: branches.first);
      },
      loading: () => const Padding(
        padding: EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
        child: SizedBox(
          height: 220,
          child: Center(child: CircularProgressIndicator()),
        ),
      ),
      error: (_, __) => const SizedBox.shrink(),
    );
  }
}

class _BranchMapCard extends StatelessWidget {
  const _BranchMapCard({required this.branch});

  final Branch branch;

  @override
  Widget build(BuildContext context) {
    final text = _BranchMapText.of(context);
    final phone = branch.displayHotline;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE7E5E4)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.all(AppConstants.spaceMd),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.location_on,
                        size: 14,
                        color: AppColors.accent,
                      ),
                      const SizedBox(width: 6),
                      Text(
                        text.eyebrow,
                        style: const TextStyle(
                          color: AppColors.primary,
                          fontSize: 10,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 1.3,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                Text(
                  text.title,
                  style: const TextStyle(
                    color: AppColors.textPrimary,
                    fontSize: 24,
                    fontWeight: FontWeight.w900,
                    fontStyle: FontStyle.italic,
                    height: 1.08,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  text.description,
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 13,
                    height: 1.55,
                  ),
                ),
                const SizedBox(height: AppConstants.spaceMd),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(AppConstants.spaceMd),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFAFAF9),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        branch.name.isNotEmpty ? branch.name : text.defaultName,
                        style: const TextStyle(
                          color: AppColors.primary,
                          fontSize: 13,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      if (branch.address.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        _BranchInfoRow(
                          icon: Icons.location_on_outlined,
                          text: branch.address,
                        ),
                      ],
                      if (phone.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        _BranchInfoRow(
                          icon: Icons.phone_outlined,
                          text: phone,
                          strong: true,
                        ),
                      ],
                      if ((branch.openingHours ?? '').isNotEmpty) ...[
                        const SizedBox(height: 8),
                        _BranchInfoRow(
                          icon: Icons.access_time,
                          text: branch.openingHours!,
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: AppConstants.spaceMd),
                Row(
                  children: [
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: () {
                          launchBranchDirections(context, branch);
                        },
                        icon: const Icon(Icons.arrow_forward, size: 16),
                        label: Text(text.directions),
                        style: FilledButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          minimumSize: const Size.fromHeight(44),
                          textStyle: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 0.9,
                          ),
                        ),
                      ),
                    ),
                    if (phone.isNotEmpty) ...[
                      const SizedBox(width: 10),
                      OutlinedButton(
                        onPressed: () => launchPhoneCall(context, phone),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppColors.primary,
                          side: const BorderSide(color: Color(0xFFE7E5E4)),
                          minimumSize: const Size(104, 44),
                          textStyle: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 0.9,
                          ),
                        ),
                        child: Text(text.callStore),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
          Container(
            height: 224,
            decoration: const BoxDecoration(
              color: Color(0xFFE7E5E4),
              border: Border(top: BorderSide(color: Color(0xFFE7E5E4))),
            ),
            child: _BranchMapPreview(branch: branch),
          ),
        ],
      ),
    );
  }
}

class _BranchInfoRow extends StatelessWidget {
  const _BranchInfoRow({
    required this.icon,
    required this.text,
    this.strong = false,
  });

  final IconData icon;
  final String text;
  final bool strong;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 15, color: AppColors.accent),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: TextStyle(
              color: AppColors.textPrimary,
              fontSize: 12,
              height: 1.4,
              fontWeight: strong ? FontWeight.w800 : FontWeight.w500,
            ),
          ),
        ),
      ],
    );
  }
}

class _BranchMapPreview extends StatelessWidget {
  const _BranchMapPreview({required this.branch});

  final Branch branch;

  @override
  Widget build(BuildContext context) {
    final mapImageUrl = branch.staticMapImageUrl();
    final hasMap = mapImageUrl != null || (branch.mapEmbedSrc ?? '').isNotEmpty;
    final text = _BranchMapText.of(context);

    return Stack(
      fit: StackFit.expand,
      children: [
        _StaticMapBackground(imageUrl: mapImageUrl),
        DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                Colors.transparent,
                Colors.black.withValues(alpha: 0.08),
              ],
            ),
          ),
        ),
        Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  color: AppColors.accent,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.accent.withValues(alpha: 0.25),
                      blurRadius: 16,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: const Icon(
                  Icons.location_on,
                  color: Colors.white,
                  size: 30,
                ),
              ),
              const SizedBox(height: 10),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.92),
                  borderRadius: BorderRadius.circular(999),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.08),
                      blurRadius: 10,
                    ),
                  ],
                ),
                child: Text(
                  hasMap ? branch.name : text.mapUnavailable,
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontSize: 12,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ),
        ),
        Positioned(
          left: 12,
          right: 12,
          bottom: 12,
          child: FilledButton.icon(
            onPressed: () {
              launchBranchMap(context, branch);
            },
            icon: const Icon(Icons.map_outlined, size: 16),
            label: Text(text.openMap),
            style: FilledButton.styleFrom(
              backgroundColor: Colors.white,
              foregroundColor: AppColors.primary,
              minimumSize: const Size.fromHeight(40),
            ),
          ),
        ),
      ],
    );
  }
}

class _StaticMapBackground extends StatelessWidget {
  const _StaticMapBackground({required this.imageUrl});

  final String? imageUrl;

  @override
  Widget build(BuildContext context) {
    final url = imageUrl;
    if (url == null || url.isEmpty) return const _FallbackMapBackground();

    return Image.network(
      url,
      fit: BoxFit.cover,
      errorBuilder: (_, __, ___) => const _FallbackMapBackground(),
      loadingBuilder: (context, child, progress) {
        if (progress == null) return child;
        return const _FallbackMapBackground();
      },
    );
  }
}

class _FallbackMapBackground extends StatelessWidget {
  const _FallbackMapBackground();

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        DecoratedBox(
          decoration: BoxDecoration(
            color: const Color(0xFFDDE9EF),
            backgroundBlendMode: BlendMode.multiply,
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                const Color(0xFFBFE3EC).withValues(alpha: 0.9),
                const Color(0xFFF3EFE6).withValues(alpha: 0.95),
              ],
            ),
          ),
        ),
        CustomPaint(painter: _MapGridPainter()),
      ],
    );
  }
}

class _MapGridPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final roadPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.82)
      ..strokeWidth = 7
      ..strokeCap = StrokeCap.round;
    final thinRoadPaint = Paint()
      ..color = Colors.white.withValues(alpha: 0.65)
      ..strokeWidth = 3
      ..strokeCap = StrokeCap.round;
    final waterPaint = Paint()
      ..color = const Color(0xFF8FD0DA).withValues(alpha: 0.7)
      ..style = PaintingStyle.fill;

    final waterPath = Path()
      ..moveTo(0, 0)
      ..lineTo(size.width * 0.34, 0)
      ..quadraticBezierTo(size.width * 0.24, size.height * 0.38,
          size.width * 0.34, size.height)
      ..lineTo(0, size.height)
      ..close();
    canvas.drawPath(waterPath, waterPaint);

    for (var x = size.width * 0.44; x < size.width; x += 46) {
      canvas.drawLine(Offset(x, -20), Offset(x - 90, size.height + 20),
          thinRoadPaint);
    }
    for (var y = 22.0; y < size.height; y += 42) {
      canvas.drawLine(Offset(size.width * 0.22, y), Offset(size.width, y + 16),
          thinRoadPaint);
    }
    canvas.drawLine(
      Offset(size.width * 0.18, size.height * 0.85),
      Offset(size.width * 0.92, size.height * 0.08),
      roadPaint,
    );
    canvas.drawLine(
      Offset(size.width * 0.38, -10),
      Offset(size.width * 0.78, size.height + 20),
      roadPaint,
    );
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _BranchMapText {
  const _BranchMapText({
    required this.eyebrow,
    required this.title,
    required this.description,
    required this.defaultName,
    required this.directions,
    required this.callStore,
    required this.openMap,
    required this.mapUnavailable,
  });

  final String eyebrow;
  final String title;
  final String description;
  final String defaultName;
  final String directions;
  final String callStore;
  final String openMap;
  final String mapUnavailable;

  static _BranchMapText of(BuildContext context) {
    switch (Localizations.localeOf(context).languageCode) {
      case 'en':
        return const _BranchMapText(
          eyebrow: 'Paprika Patras',
          title: 'Visit Us In Patras',
          description:
              'Visit our restaurant, explore your favourite dishes and come by for Vietnamese food, Greek drinks and bookings.',
          defaultName: 'Paprika Patras',
          directions: 'Directions',
          callStore: 'Call Store',
          openMap: 'Open map',
          mapUnavailable: 'Map unavailable',
        );
      case 'el':
        return const _BranchMapText(
          eyebrow: 'Paprika Patras',
          title: 'Επισκεφθείτε μας στην Πάτρα',
          description:
              'Δείτε την τοποθεσία, καλέστε ή ελάτε από κοντά για βιετναμέζικο φαγητό, ελληνικά ποτά και κρατήσεις.',
          defaultName: 'Paprika Patras',
          directions: 'Οδηγίες',
          callStore: 'Καλέστε',
          openMap: 'Άνοιγμα χάρτη',
          mapUnavailable: 'Ο χάρτης δεν είναι διαθέσιμος',
        );
      default:
        return const _BranchMapText(
          eyebrow: 'Paprika Patras',
          title: 'Ghé Thăm Paprika Tại Patras',
          description:
              'Xem địa chỉ quán, gọi nhanh hoặc ghé thưởng thức món Việt, đồ uống Hy Lạp và đặt bàn.',
          defaultName: 'Paprika Patras',
          directions: 'Chỉ đường',
          callStore: 'Gọi quán',
          openMap: 'Mở bản đồ',
          mapUnavailable: 'Bản đồ không khả dụng',
        );
    }
  }
}

// ===========================================================================
// TESTIMONIALS SECTION — horizontal scroll (giữ nguyên)
// ===========================================================================
class _TestimonialsSection extends ConsumerWidget {
  const _TestimonialsSection({required this.title});
  final String title;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeAsync = ref.watch(homeProvider);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _SectionTitle(text: title),
        const SizedBox(height: AppConstants.spaceMd),
        homeAsync.when(
          data: (home) {
            if (home.testimonials.isEmpty) return const SizedBox.shrink();
            return SizedBox(
              height: 130,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(
                    horizontal: AppConstants.spaceMd),
                itemCount: home.testimonials.length,
                separatorBuilder: (_, __) =>
                    const SizedBox(width: AppConstants.spaceSm),
                itemBuilder: (_, i) {
                  return _TestimonialCard(testimonial: home.testimonials[i]);
                },
              ),
            );
          },
          loading: () => const SizedBox(height: 130),
          error: (_, __) => const SizedBox.shrink(),
        ),
      ],
    );
  }
}

class _TestimonialCard extends StatelessWidget {
  const _TestimonialCard({required this.testimonial});
  final HomeTestimonial testimonial;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 280,
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
              CircleAvatar(
                radius: 18,
                backgroundColor: AppColors.primary,
                child: Text(
                  testimonial.name.isNotEmpty
                      ? testimonial.name[0].toUpperCase()
                      : '?',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      testimonial.name,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w900,
                        color: AppColors.textPrimary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Row(
                      children: List.generate(
                        5,
                        (i) => Icon(
                          Icons.star,
                          size: 12,
                          color: i < testimonial.rating
                              ? AppColors.gold
                              : AppColors.border,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Expanded(
            child: Text(
              testimonial.content,
              style: const TextStyle(
                fontSize: 12,
                color: AppColors.textMuted,
                height: 1.4,
              ),
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}

// ===========================================================================
// ABOUT CARD — static brand card
// ===========================================================================
class _AboutCard extends ConsumerWidget {
  const _AboutCard({required this.eyebrow, required this.title});
  final String eyebrow;
  final String title;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = AppLocalizations.of(context);
    return GestureDetector(
      onTap: () {
        context.showPageLoader();
        context.push(AppRoutes.about);
      },
      child: Container(
        width: double.infinity,
        margin:
            const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
        padding: const EdgeInsets.all(AppConstants.spaceLg),
        decoration: BoxDecoration(
          color: AppColors.warm,
          borderRadius: BorderRadius.circular(AppConstants.radius),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: const BoxDecoration(
                    color: AppColors.accent,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.local_fire_department,
                      color: Colors.white, size: 22),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        l.aboutCardBrand,
                        style: const TextStyle(
                          color: AppColors.primaryStrong,
                          fontSize: 16,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.04,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        l.aboutCardTagline,
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppConstants.spaceMd),
            Text(
              l.aboutCardBody,
              style: const TextStyle(
                color: AppColors.textPrimary,
                fontSize: 13,
                height: 1.6,
              ),
            ),
            const SizedBox(height: AppConstants.spaceMd),
            Wrap(
              spacing: AppConstants.spaceSm,
              runSpacing: AppConstants.spaceSm,
              children: [
                _Pill(icon: Icons.schedule, label: l.aboutPillHours),
                _Pill(icon: Icons.location_on, label: l.aboutPillLocation),
                _Pill(icon: Icons.star, label: l.aboutPillRating),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.icon, required this.label});
  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppColors.primary),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              color: AppColors.textPrimary,
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

// ===========================================================================
// FLOATING CONTACT BUTTONS — phone (đỏ) + chat (xanh) góc dưới phải
// (giống Laravel chat-widget + hotline button trong screenshot).
// ===========================================================================
class _FloatingContactButtons extends ConsumerWidget {
  const _FloatingContactButtons();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = AppLocalizations.of(context);
    final branch = _preferredContactBranch(ref);
    final phone = branch?.displayHotline;
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        FloatingActionButton(
          heroTag: 'fab-call',
          mini: true,
          backgroundColor: AppColors.accent,
          onPressed: () => launchPhoneCall(context, phone),
          tooltip: l.fabCallTooltip,
          child: const Icon(Icons.phone, color: Colors.white, size: 22),
        ),
        const SizedBox(height: 12),
        FloatingActionButton(
          heroTag: 'fab-chat',
          mini: true,
          backgroundColor: AppColors.primary,
          onPressed: () => openChatSupport(context, branchId: branch?.id),
          tooltip: l.fabChatTooltip,
          child: const Icon(Icons.chat_bubble, color: Colors.white, size: 20),
        ),
      ],
    );
  }

  Branch? _preferredContactBranch(WidgetRef ref) {
    final branches = ref.watch(branchesProvider).asData?.value;
    if (branches == null || branches.isEmpty) return null;

    final activeBranchId = ref.watch(storageServiceProvider).getActiveBranchId();
    if (activeBranchId != null) {
      for (final branch in branches) {
        if (branch.id == activeBranchId) return branch;
      }
    }

    return branches.first;
  }
}
