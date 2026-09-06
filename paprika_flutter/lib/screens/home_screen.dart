import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/coming_soon.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';

/// Home screen — Landing page nhà hàng Paprika Patras.
///
/// Cấu trúc:
///   Scaffold
///     body: Column([
///       PaprikaHeader  (sticky top),
///       Expanded(SingleChildScrollView([
///         ... hero + popular + about ...,
///         PaprikaFooter  (cuộn theo, nằm cuối trang),
///       ])),
///     ])
///
/// Mọi CTA trong body đều show "Tính năng đang được phát triển"
/// qua [ComingSoon] — không navigate đến route chưa có.
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
                // Footer rất cao trên mobile (HotlineBand + 4 cột stack dọc +
                // CopyrightBar). Trước đây Footer đặt ngoài Expanded nên khi
                // intrinsic height của Footer > viewport - Header thì Expanded
                // bị vắt về 0 và Footer overflow xuống dưới (đã thấy
                // "BOTTOM OVERFLOWED BY 104 PIXELS" trên Chrome). Đưa Footer
                // vào trong SingleChildScrollView để cả page cuộn mượt, khớp
                // với cơ chế PHP web (header sticky, footer ở cuối page).
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: const [
                  Padding(
                    padding: EdgeInsets.symmetric(
                      vertical: AppConstants.spaceLg,
                      horizontal: AppConstants.spaceMd,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        _Hero(),
                        SizedBox(height: AppConstants.spaceLg),
                        _SectionTitle(text: 'Món nổi bật'),
                        SizedBox(height: AppConstants.spaceMd),
                        _PopularDishes(),
                        SizedBox(height: AppConstants.spaceLg),
                        _SectionTitle(text: 'Về Paprika Patras'),
                        SizedBox(height: AppConstants.spaceMd),
                        _AboutCard(),
                      ],
                    ),
                  ),
                  PaprikaFooter(),
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

// ===========================================================================
// HERO
// ===========================================================================

class _Hero extends StatelessWidget {
  const _Hero();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(AppConstants.spaceLg),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.primary, AppColors.primaryStrong],
        ),
        borderRadius: BorderRadius.circular(AppConstants.radius),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.35),
            blurRadius: 18,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: AppColors.accent,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: const Text(
                  'PATRAS · GR',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 0.16,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 4,
                ),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(color: Colors.white24),
                ),
                child: const Text(
                  '€ EUR  ·  vi / en / el',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 10,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.12,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: AppConstants.spaceLg),
          const Text(
            'Hương vị Việt Nam,\nTinh hoa Hy Lạp',
            style: TextStyle(
              color: Colors.white,
              fontSize: 28,
              fontWeight: FontWeight.w900,
              height: 1.15,
              letterSpacing: -0.5,
            ),
          ),
          const SizedBox(height: AppConstants.spaceMd),
          Text(
            'Phở bò nấu chậm 12 tiếng, nem nướng trui than hoạt, '
            'gyros pita chuẩn vị Athens — tất cả trong một không gian '
            'ấm cúng giữa lòng Patras.',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.85),
              fontSize: 14,
              height: 1.5,
            ),
          ),
          const SizedBox(height: AppConstants.spaceLg),
          Row(
            children: [
              Expanded(
                child: _PrimaryCta(
                  icon: Icons.restaurant_menu,
                  label: 'Xem thực đơn',
                  feature: 'Thực đơn',
                ),
              ),
              const SizedBox(width: AppConstants.spaceSm),
              Expanded(
                child: _SecondaryCta(
                  icon: Icons.calendar_today,
                  label: 'Đặt bàn',
                  feature: 'Đặt bàn',
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _PrimaryCta extends StatelessWidget {
  const _PrimaryCta({
    required this.icon,
    required this.label,
    required this.feature,
  });

  final IconData icon;
  final String label;
  final String feature;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.accent,
      borderRadius: BorderRadius.circular(AppConstants.radiusSm),
      elevation: 4,
      shadowColor: AppColors.accent.withValues(alpha: 0.4),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
        onTap: () => ComingSoon.show(context, feature: feature),
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

class _SecondaryCta extends StatelessWidget {
  const _SecondaryCta({
    required this.icon,
    required this.label,
    required this.feature,
  });

  final IconData icon;
  final String label;
  final String feature;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white.withValues(alpha: 0.15),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
        side: const BorderSide(color: Colors.white38),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
        onTap: () => ComingSoon.show(context, feature: feature),
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
// SECTION TITLE
// ===========================================================================

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.text});

  final String text;

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
          text,
          style: const TextStyle(
            color: AppColors.primaryStrong,
            fontSize: 18,
            fontWeight: FontWeight.w900,
            letterSpacing: -0.2,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Container(
            height: 1,
            color: AppColors.border,
          ),
        ),
      ],
    );
  }
}

// ===========================================================================
// POPULAR DISHES GRID
// ===========================================================================

class _PopularDishes extends StatelessWidget {
  const _PopularDishes();

  static const _dishes = [
    _DishItem(
      name: 'Phở Bò Tái',
      desc: 'Nước dùng ninh 12 tiếng, bò tái mềm, rau thơm Hà Nội',
      price: '€ 11.50',
      icon: Icons.ramen_dining,
      tag: 'Best seller',
    ),
    _DishItem(
      name: 'Nem Nướng Nha Trang',
      desc: 'Nem trui than hoạt, bánh tráng cuốn, nước chấm đặc biệt',
      price: '€ 9.80',
      icon: Icons.local_fire_department,
      tag: null,
    ),
    _DishItem(
      name: 'Gyros Pita',
      desc: 'Thịt heo nướng vị Hy Lạp, tzatziki, rau sống Địa Trung Hải',
      price: '€ 8.90',
      icon: Icons.kebab_dining,
      tag: 'Yêu thích',
    ),
    _DishItem(
      name: 'Bánh Mì Paprika',
      desc: 'Pate, thịt nguội, rau muối chua, ớt tươi, hành lá',
      price: '€ 6.50',
      icon: Icons.bakery_dining,
      tag: null,
    ),
  ];

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        // Grid 2 cột trên mobile, 4 cột trên desktop.
        final cols = constraints.maxWidth >= 900
            ? 4
            : constraints.maxWidth >= 600
                ? 3
                : 2;

        if (cols == 2) {
          return GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisSpacing: AppConstants.spaceSm,
            mainAxisSpacing: AppConstants.spaceSm,
            childAspectRatio: 0.72,
            children: [
              for (final d in _dishes) _DishCard(dish: d),
            ],
          );
        }
        return GridView.count(
          crossAxisCount: cols,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisSpacing: AppConstants.spaceMd,
          mainAxisSpacing: AppConstants.spaceMd,
          childAspectRatio: 0.78,
          children: [
            for (final d in _dishes) _DishCard(dish: d),
          ],
        );
      },
    );
  }
}

class _DishItem {
  const _DishItem({
    required this.name,
    required this.desc,
    required this.price,
    required this.icon,
    required this.tag,
  });

  final String name;
  final String desc;
  final String price;
  final IconData icon;
  final String? tag;
}

class _DishCard extends StatelessWidget {
  const _DishCard({required this.dish});

  final _DishItem dish;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(AppConstants.radius),
      elevation: 1,
      shadowColor: Colors.black.withValues(alpha: 0.06),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppConstants.radius),
        onTap: () => ComingSoon.show(context, feature: 'Chi tiết món'),
        child: Padding(
          padding: const EdgeInsets.all(AppConstants.spaceMd),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Stack(
                clipBehavior: Clip.none,
                children: [
                  AspectRatio(
                    aspectRatio: 1,
                    child: Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [
                            AppColors.accentSoft,
                            AppColors.warm,
                          ],
                        ),
                        borderRadius:
                            BorderRadius.circular(AppConstants.radiusSm),
                      ),
                      alignment: Alignment.center,
                      child: Icon(
                        dish.icon,
                        size: 40,
                        color: AppColors.accentStrong,
                      ),
                    ),
                  ),
                  if (dish.tag != null)
                    Positioned(
                      top: 6,
                      right: 6,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 6,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.primaryStrong,
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          dish.tag!,
                          style: const TextStyle(
                            color: AppColors.gold,
                            fontSize: 9,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 0.12,
                          ),
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                dish.name,
                style: const TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 14,
                  fontWeight: FontWeight.w900,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 4),
              Text(
                dish.desc,
                style: TextStyle(
                  color: AppColors.textMuted,
                  fontSize: 11,
                  height: 1.35,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const Spacer(),
              const SizedBox(height: 6),
              Row(
                children: [
                  Text(
                    dish.price,
                    style: const TextStyle(
                      color: AppColors.accentStrong,
                      fontSize: 14,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const Spacer(),
                  Icon(
                    Icons.add_circle,
                    color: AppColors.primary,
                    size: 22,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ===========================================================================
// ABOUT CARD
// ===========================================================================

class _AboutCard extends StatelessWidget {
  const _AboutCard();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
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
                decoration: BoxDecoration(
                  color: AppColors.accent,
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.local_fire_department,
                  color: Colors.white,
                  size: 22,
                ),
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
          Text(
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
            children: [
              _Pill(icon: Icons.schedule, label: 'Mở cửa 11:30 - 23:00'),
              _Pill(icon: Icons.location_on, label: 'Patras, Greece'),
              _Pill(icon: Icons.star, label: '4.7 / 5 trên Google'),
            ],
          ),
        ],
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
