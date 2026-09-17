import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/utils/image_helper.dart';
import '../data/models/home_model.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/coming_soon.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';

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
                  const _SectionTitle(text: 'Món nổi bật'),
                  const SizedBox(height: AppConstants.spaceMd),
                  const _BestSellersSection(), // featured.take(3), 1 cột
                  const SizedBox(height: AppConstants.spaceLg),
                  const _GallerySection(), // 3 ảnh không gian (NEW)
                  const SizedBox(height: AppConstants.spaceLg),
                  const _ServicesSection(), // 3 cards (NEW)
                  const SizedBox(height: AppConstants.spaceLg),
                  const _SectionTitle(text: 'Khách hàng nói gì'),
                  const SizedBox(height: AppConstants.spaceMd),
                  const _TestimonialsSection(), // từ homeProvider
                  const SizedBox(height: AppConstants.spaceLg),
                  const _SectionTitle(text: 'Về Paprika Patras'),
                  const SizedBox(height: AppConstants.spaceMd),
                  const _AboutCard(), // static
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
// SECTION TITLE — heading nhỏ + divider
// ===========================================================================
class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.text});
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
      child: Row(
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

    final title = banner?.title.isNotEmpty == true
        ? banner!.title
        : 'PAPRIKA -\nẨM THỰC\nVIỆT NAM';
    final subtitle = banner?.subtitle.isNotEmpty == true
        ? banner!.subtitle
        : 'Phở bò nấu chậm 12 tiếng, gyros Hy Lạp chuẩn vị Athens — '
            'ẩm thực đỉnh cao giữa lòng Patras';
    final ctaLabel = banner?.ctaLabel.isNotEmpty == true
        ? banner!.ctaLabel
        : 'ĐẶT MÓN NGAY';

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
      padding: const EdgeInsets.all(AppConstants.spaceLg),
      decoration: BoxDecoration(
        color: AppColors.primaryStrong, // #043427
        borderRadius: BorderRadius.circular(AppConstants.radiusLg),
        boxShadow: [
          BoxShadow(
            color: AppColors.primaryStrong.withValues(alpha: 0.4),
            blurRadius: 24,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Badge "ĐẶT MÓN ONLINE" đỏ với chấm vàng pulsing (giống screenshot)
          Row(
            children: [
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: AppColors.accent,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _PulsingDot(),
                    SizedBox(width: 6),
                    Text(
                      'ĐẶT MÓN ONLINE',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.16,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: AppConstants.spaceLg),
          // Title (italic uppercase — giống screenshot "PAPRIKA - ẨM THỰC VIỆT NAM")
          Text(
            title,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 36,
              fontWeight: FontWeight.w900,
              fontStyle: FontStyle.italic,
              height: 1.05,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: AppConstants.spaceMd),
          Text(
            subtitle,
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.82),
              fontSize: 13,
              height: 1.5,
            ),
          ),
          const SizedBox(height: AppConstants.spaceLg),
          // CTA chính - 1 nút đỏ full-width (giống screenshot)
          _PrimaryCta(
            icon: Icons.restaurant_menu,
            label: ctaLabel,
            route: AppRoutes.menu,
          ),
          const SizedBox(height: AppConstants.spaceLg),
          // Stats — 3 cột (giống Laravel)
          Container(
            padding: const EdgeInsets.only(top: AppConstants.spaceMd),
            decoration: BoxDecoration(
              border: Border(
                top: BorderSide(
                  color: Colors.white.withValues(alpha: 0.15),
                  width: 1,
                ),
              ),
            ),
            child: const Row(
              children: [
                Expanded(
                  child: _Stat(value: '100%', label: 'Nguyên liệu tươi'),
                ),
                Expanded(
                  child: _Stat(value: 'Nhanh', label: 'Giao hàng'),
                ),
                Expanded(
                  child: _Stat(value: 'Dễ', label: 'Đặt món'),
                ),
              ],
            ),
          ),
        ],
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
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          value,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 22,
            fontWeight: FontWeight.w900,
            height: 1.1,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          label,
          style: TextStyle(
            color: Colors.white.withValues(alpha: 0.65),
            fontSize: 10,
            fontWeight: FontWeight.w800,
            letterSpacing: 0.6,
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
            : () => context.push(route!),
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
        onTap: () => context.push(route ?? AppRoutes.reservation),
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
                  const Text(
                    'ƯU ĐÃI ĐẶC BIỆT',
                    style: TextStyle(
                      color: AppColors.accent,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.5,
                    ),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Món ngon đang được yêu thích',
                    style: TextStyle(
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
                      child: const Text(
                        'ƯU ĐÃI MỚI NHẤT',
                        style: TextStyle(
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
  const _BestSellersSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeAsync = ref.watch(homeProvider);

    return homeAsync.when(
      data: (home) {
        if (home.featured.isEmpty) return const SizedBox.shrink();
        final bestSellers = home.featured.take(3).toList();
        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Title (giống Laravel "best seller" eyebrow + title)
            Padding(
              padding: const EdgeInsets.symmetric(
                  horizontal: AppConstants.spaceMd),
              child: Column(
                children: [
                  const Text(
                    'BEST SELLER',
                    style: TextStyle(
                      color: AppColors.accent,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.5,
                    ),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Món nổi bật',
                    style: TextStyle(
                      color: AppColors.primaryStrong,
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Những món ăn được yêu thích nhất tại Paprika Patras',
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
              padding: const EdgeInsets.symmetric(
                  horizontal: AppConstants.spaceMd),
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
      },
      loading: () => const SizedBox(
        height: 120,
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (_, __) => const SizedBox.shrink(),
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
        onTap: () => context.push(AppRoutes.dishDetailPath(dish.id)),
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
                            child: const Text(
                              'MỚI',
                              style: TextStyle(
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
        if (home.galleryImages.isEmpty) return const SizedBox.shrink();
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
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _PulsingDot(),
                    SizedBox(width: 6),
                    Text(
                      'KHÔNG GIAN',
                      style: TextStyle(
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
              const Text(
                'Bên trong\nPaprika Patras',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 26,
                  fontWeight: FontWeight.w900,
                  fontStyle: FontStyle.italic,
                  height: 1.1,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Không gian ấm cúng, bếp mở & phòng VIP cho nhóm — '
                'một chút Việt Nam giữa lòng Patras.',
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.75),
                  fontSize: 13,
                  height: 1.5,
                ),
              ),
              const SizedBox(height: AppConstants.spaceMd),
              // 3 ảnh xếp dọc, ảnh giữa thụt xuống (giống Laravel sm:mt-8)
              for (int i = 0; i < home.galleryImages.length; i++) ...[
                if (i == 1) const SizedBox(height: 16),
                _GalleryTile(image: home.galleryImages[i], offsetDown: i == 1),
                if (i < home.galleryImages.length - 1)
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
                    child: const Padding(
                      padding: EdgeInsets.symmetric(
                          horizontal: 18, vertical: 10),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            'XEM THÊM ẢNH',
                            style: TextStyle(
                              color: AppColors.primaryStrong,
                              fontSize: 12,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 1.0,
                            ),
                          ),
                          SizedBox(width: 6),
                          Icon(Icons.arrow_forward,
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
}

class _GalleryTile extends StatelessWidget {
  const _GalleryTile({required this.image, this.offsetDown = false});
  final HomeGalleryImage image;
  final bool offsetDown;

  @override
  Widget build(BuildContext context) {
    final imageUrl =
        image.image.isNotEmpty ? ImageHelper.url(image.image) : null;

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
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding:
              const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
          child: Column(
            children: [
              const Text(
                'DỊCH VỤ',
                style: TextStyle(
                  color: AppColors.accent,
                  fontSize: 10,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 1.5,
                ),
              ),
              const SizedBox(height: 4),
              const Text(
                'Chọn cách thưởng thức',
                style: TextStyle(
                  color: AppColors.primaryStrong,
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                  fontStyle: FontStyle.italic,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Ba cách thưởng thức món ăn yêu thích của bạn',
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
                title: 'Giao hàng',
                subtitle:
                    'Đặt online, giao tận nơi trong 30 phút tại Patras.',
                ctaLabel: 'Đặt hàng ngay',
                route: AppRoutes.menu,
                icon: Icons.local_shipping,
                gradientColors: const [AppColors.primary, AppColors.primaryStrong],
                accentColor: AppColors.gold,
              ),
              const SizedBox(height: 12),
              _ServiceCard(
                title: 'Nhận tại quán',
                subtitle: 'Đặt trước, đến lấy nhanh không phải xếp hàng.',
                ctaLabel: 'Đặt hàng ngay',
                route: AppRoutes.menu,
                icon: Icons.shopping_bag,
                gradientColors: const [AppColors.accent, AppColors.accentStrong],
                accentColor: Colors.white,
              ),
              const SizedBox(height: 12),
              _ServiceCard(
                title: 'Tại quán',
                subtitle: 'Đặt bàn trước để có chỗ ngồi đẹp nhất.',
                ctaLabel: 'Đặt bàn ngay',
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
        onTap: () => context.push(route),
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
// TESTIMONIALS SECTION — horizontal scroll (giữ nguyên)
// ===========================================================================
class _TestimonialsSection extends ConsumerWidget {
  const _TestimonialsSection();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final homeAsync = ref.watch(homeProvider);

    return homeAsync.when(
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
  const _AboutCard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return GestureDetector(
      onTap: () => context.push(AppRoutes.about),
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
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'PAPRIKA PATRAS',
                        style: TextStyle(
                          color: AppColors.primaryStrong,
                          fontSize: 16,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.04,
                        ),
                      ),
                      SizedBox(height: 2),
                      Text(
                        'Vietnamese & Greek Restaurant · Patras, GR',
                        style: TextStyle(
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
            const Text(
              'Mở cửa từ năm 2019, Paprika Patras mang đến thực đơn '
              'fusion độc đáo giữa hai nền ẩm thực: phở, bún chả, bánh mì '
              'Việt Nam kết hợp gyros, moussaka, souvlaki Hy Lạp. '
              'Tất cả nguyên liệu nhập tươi hàng tuần, nấu thủ công mỗi ngày.',
              style: TextStyle(
                color: AppColors.textPrimary,
                fontSize: 13,
                height: 1.6,
              ),
            ),
            const SizedBox(height: AppConstants.spaceMd),
            Wrap(
              spacing: AppConstants.spaceSm,
              runSpacing: AppConstants.spaceSm,
              children: const [
                _Pill(icon: Icons.schedule, label: 'Mở cửa 11:30 - 23:00'),
                _Pill(icon: Icons.location_on, label: 'Patras, Greece'),
                _Pill(icon: Icons.star, label: '4.7 / 5 trên Google'),
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
// Chỉ là UI — logic gọi/chat vẫn dùng ComingSoon như cart.
// ===========================================================================
class _FloatingContactButtons extends StatelessWidget {
  const _FloatingContactButtons();

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        FloatingActionButton(
          heroTag: 'fab-call',
          mini: true,
          backgroundColor: AppColors.accent,
          onPressed: () =>
              ComingSoon.show(context, feature: 'Gọi điện thoại'),
          tooltip: 'Gọi điện',
          child: const Icon(Icons.phone, color: Colors.white, size: 22),
        ),
        const SizedBox(height: 12),
        FloatingActionButton(
          heroTag: 'fab-chat',
          mini: true,
          backgroundColor: AppColors.primary,
          onPressed: () => ComingSoon.show(context, feature: 'Chat trực tuyến'),
          tooltip: 'Chat trực tuyến',
          child: const Icon(Icons.chat_bubble, color: Colors.white, size: 20),
        ),
      ],
    );
  }
}
