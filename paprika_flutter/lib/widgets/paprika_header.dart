import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import 'coming_soon.dart';

/// Header sticky theo Laravel Blade storefront (`header.blade.php`).
///
/// Mount vào `Scaffold` dạng:
///
/// ```dart
/// body: Column(children: [
///   const PaprikaHeader(),
///   Expanded(child: SingleChildScrollView(child: ...)),
///   const PaprikaFooter(),
/// ]),
/// ```
///
/// để header "sticky" tự nhiên — Flutter không có CSS `position: sticky`,
/// nên widget KHÔNG tự xử lý sticky; parent screen quyết định layout.
///
/// Hiện tại [cartItemsCount] là `int` truyền vào (hardcode 0 trong base).
/// Team FE/BE sẽ thay bằng `ref.watch(cartCountProvider)` sau khi có cart repo.
class PaprikaHeader extends ConsumerStatefulWidget {
  const PaprikaHeader({
    super.key,
    this.cartItemsCount = 0,
    this.activeRoute,
  });

  /// Số item hiển thị trên badge giỏ hàng. 0 = ẩn badge.
  final int cartItemsCount;

  /// Route hiện tại để highlight nav item. Mặc định detect từ [GoRouter].
  /// Có thể truyền thẳng nếu muốn override (vd: preview trong test_design).
  final String? activeRoute;

  @override
  ConsumerState<PaprikaHeader> createState() => _PaprikaHeaderState();
}

class _PaprikaHeaderState extends ConsumerState<PaprikaHeader> {
  bool _isMobileMenuOpen = false;

  static const _navItems = <_NavItem>[
    _NavItem(label: 'Trang chủ', route: AppRoutes.home),
    _NavItem(label: 'Thực đơn', route: AppRoutes.menu),
    _NavItem(label: 'Giới thiệu', route: AppRoutes.about, icon: Icons.info_outline),
    _NavItem(label: 'Chi nhánh', route: AppRoutes.branches, icon: Icons.storefront_outlined),
    _NavItem(label: 'Liên hệ', route: AppRoutes.contact, icon: Icons.contact_mail_outlined),
    _NavItem(label: 'Đặt bàn', route: AppRoutes.reservation),
  ];

  String get _currentRoute {
    if (widget.activeRoute != null) return widget.activeRoute!;
    final router = GoRouter.of(context);
    return router.routerDelegate.currentConfiguration.uri.path;
  }

  void _go(BuildContext context, String route) {
    setState(() => _isMobileMenuOpen = false);

    // Các route đã có screen (xem lib/app/routes.dart).
    if (route == AppRoutes.home ||
        route == AppRoutes.splash ||
        route == AppRoutes.about ||
        route == AppRoutes.branches ||
        route == AppRoutes.contact ||
        route == AppRoutes.reservation) {
      context.go(route);
      return;
    }

    // Route chưa có screen -> show snackbar "đang phát triển".
    final featureName = _featureNameFor(route);
    ComingSoon.show(context, feature: featureName);
  }

  String? _featureNameFor(String route) {
    switch (route) {
      case AppRoutes.menu:
        return 'Thực đơn';
      case AppRoutes.cart:
        return 'Giỏ hàng';
      case AppRoutes.reservations:
        return 'Danh sách đặt bàn';
      case AppRoutes.search:
        return 'Tìm món';
      case AppRoutes.profile:
        return 'Hồ sơ';
      case AppRoutes.orders:
        return 'Đơn hàng';
      case AppRoutes.notifications:
        return 'Thông báo';
      default:
        return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.primary,
      elevation: 4,
      shadowColor: Colors.black.withValues(alpha: 0.18),
      child: SafeArea(
        bottom: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildTopBar(context),
            AnimatedSize(
              duration: const Duration(milliseconds: 200),
              curve: Curves.easeOut,
              child:
                  _isMobileMenuOpen ? _buildMobileMenu() : const SizedBox.shrink(),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTopBar(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        border: Border(
          bottom: BorderSide(color: AppColors.primaryStrong, width: 1),
        ),
      ),
      padding: const EdgeInsets.symmetric(
        horizontal: AppConstants.spaceMd,
        vertical: AppConstants.spaceSm,
      ),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final isWide = constraints.maxWidth >= 768;
          return Row(
            children: [
              _buildLogo(context),
              if (isWide) ...[
                const SizedBox(width: AppConstants.spaceLg),
                Expanded(child: _buildDesktopNav(context)),
                const SizedBox(width: AppConstants.spaceMd),
                _buildLanguageSelector(context),
                const SizedBox(width: AppConstants.spaceXs),
              ] else ...[
                const Spacer(),
                _buildLanguageSelector(context),
                const SizedBox(width: AppConstants.spaceXs),
              ],
              _buildCartIcon(context),
              const SizedBox(width: AppConstants.spaceXs),
              _buildMobileToggle(context),
            ],
          );
        },
      ),
    );
  }

  Widget _buildLogo(BuildContext context) {
    return InkWell(
      onTap: () => _go(context, AppRoutes.home),
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(
              Icons.local_fire_department,
              color: Colors.white,
              size: 26,
            ),
            const SizedBox(width: 4),
            const Text(
              'paprika',
              style: TextStyle(
                color: Colors.white,
                fontSize: 22,
                fontWeight: FontWeight.w900,
                fontStyle: FontStyle.italic,
                letterSpacing: -0.5,
                height: 1.0,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDesktopNav(BuildContext context) {
    final current = _currentRoute;
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        for (final item in _navItems) ...[
          _NavLink(
            label: item.label,
            icon: item.icon,
            isActive: _isActive(current, item.route),
            onTap: () => _go(context, item.route),
          ),
        ],
      ],
    );
  }

  Widget _buildLanguageSelector(BuildContext context) {
    return Material(
      color: Colors.white.withValues(alpha: 0.15),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(999),
        side: const BorderSide(color: Colors.white24),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(999),
        onTap: () => ComingSoon.show(context, feature: 'Ngôn ngữ'),
        child: const Padding(
          padding: EdgeInsets.symmetric(
            horizontal: 10,
            vertical: 6,
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'VN',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 11,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 0.12,
                ),
              ),
              SizedBox(width: 2),
              Icon(
                Icons.keyboard_arrow_down,
                color: Colors.white,
                size: 14,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCartIcon(BuildContext context) {
    final count = widget.cartItemsCount;
    return Stack(
      clipBehavior: Clip.none,
      children: [
        IconButton(
          tooltip: 'Giỏ hàng',
          onPressed: () => ComingSoon.show(context, feature: 'Giỏ hàng'),
          icon: const Icon(Icons.shopping_bag_outlined, color: Colors.white),
        ),
        if (count > 0)
          Positioned(
            top: 4,
            right: 4,
            child: Container(
              constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
              padding: const EdgeInsets.symmetric(horizontal: 4),
              decoration: BoxDecoration(
                color: AppColors.accent,
                shape: BoxShape.circle,
                border: Border.all(color: Colors.white, width: 1.5),
              ),
              alignment: Alignment.center,
              child: Text(
                '$count',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 9,
                  fontWeight: FontWeight.w900,
                  height: 1.0,
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _buildMobileToggle(BuildContext context) {
    return IconButton(
      tooltip: _isMobileMenuOpen ? 'Đóng menu' : 'Mở menu',
      onPressed: () => setState(() => _isMobileMenuOpen = !_isMobileMenuOpen),
      icon: Icon(
        _isMobileMenuOpen ? Icons.close : Icons.menu,
        color: Colors.white,
      ),
    );
  }

  Widget _buildMobileMenu() {
    final current = _currentRoute;
    return Container(
      width: double.infinity,
      decoration: const BoxDecoration(
        color: AppColors.primaryStrong,
        border: Border(
          top: BorderSide(color: Colors.white24, width: 1),
        ),
      ),
      padding: const EdgeInsets.all(AppConstants.spaceMd),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          for (final item in _navItems) ...[
            _MobileNavLink(
              label: item.label,
              icon: item.icon,
              isActive: _isActive(current, item.route),
              onTap: () => _go(context, item.route),
            ),
            const SizedBox(height: AppConstants.spaceXs),
          ],
        ],
      ),
    );
  }

  bool _isActive(String currentRoute, String itemRoute) {
    if (itemRoute == AppRoutes.home) {
      return currentRoute == AppRoutes.home || currentRoute == AppRoutes.splash;
    }
    return currentRoute.startsWith(itemRoute);
  }
}

class _NavItem {
  const _NavItem({
    required this.label,
    required this.route,
    this.icon,
  });
  final String label;
  final String route;
  final IconData? icon;
}

class _NavLink extends StatelessWidget {
  const _NavLink({
    required this.label,
    required this.isActive,
    required this.onTap,
    this.icon,
  });
  final String label;
  final bool isActive;
  final VoidCallback onTap;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return TextButton(
      onPressed: onTap,
      style: TextButton.styleFrom(
        foregroundColor:
            isActive ? Colors.white : Colors.white.withValues(alpha: 0.8),
        padding: const EdgeInsets.symmetric(
          horizontal: AppConstants.spaceMd,
          vertical: AppConstants.spaceSm,
        ),
        minimumSize: const Size(0, AppConstants.minTouchTarget),
        shape: const RoundedRectangleBorder(borderRadius: BorderRadius.zero),
        side: BorderSide(
          color: isActive ? AppColors.accent : Colors.transparent,
          width: 2,
        ),
      ).copyWith(
        overlayColor:
            WidgetStatePropertyAll(Colors.white.withValues(alpha: 0.1)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(
              icon,
              size: 14,
              color: isActive ? Colors.white : Colors.white70,
            ),
            const SizedBox(width: 4),
          ],
          Text(
            label.toUpperCase(),
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w800,
              letterSpacing: 0.14,
            ),
          ),
        ],
      ),
    );
  }
}

class _MobileNavLink extends StatelessWidget {
  const _MobileNavLink({
    required this.label,
    required this.isActive,
    required this.onTap,
    this.icon,
  });
  final String label;
  final bool isActive;
  final VoidCallback onTap;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: isActive ? AppColors.accent : Colors.transparent,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
        side: isActive
            ? const BorderSide(color: Colors.white, width: 3)
            : BorderSide.none,
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
        child: Container(
          constraints:
              const BoxConstraints(minHeight: AppConstants.minTouchTarget),
          alignment: Alignment.centerLeft,
          padding: const EdgeInsets.symmetric(
            horizontal: AppConstants.spaceMd,
            vertical: AppConstants.spaceSm,
          ),
          child: Row(
            children: [
              if (icon != null) ...[
                Icon(
                  icon,
                  color: Colors.white,
                  size: 20,
                ),
                const SizedBox(width: AppConstants.spaceSm),
              ],
              Expanded(
                child: Text(
                  label.toUpperCase(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.14,
                  ),
                ),
              ),
              const Icon(
                Icons.chevron_right,
                color: Colors.white54,
                size: 18,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
