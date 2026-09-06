import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../widgets/coming_soon.dart';

/// Bottom navigation bar 4 tab theo Laravel Blade storefront
/// (`components.bottom-nav` / `_bottom_nav.blade.php`).
///
/// Tab đã có screen sẽ navigate; tab chưa có sẽ show ComingSoon snackbar.
class BottomNavBar extends ConsumerWidget {
  const BottomNavBar({super.key});

  static const _items = <_BottomNavItem>[
    _BottomNavItem(
      label: 'TRANG CHỦ',
      route: AppRoutes.home,
      icon: Icons.home_outlined,
      activeIcon: Icons.home,
    ),
    _BottomNavItem(
      label: 'THỰC ĐƠN',
      route: AppRoutes.menu,
      icon: Icons.restaurant_menu_outlined,
      activeIcon: Icons.restaurant_menu,
    ),
    _BottomNavItem(
      label: 'ĐẶT BÀN',
      route: AppRoutes.reservation,
      icon: Icons.event_outlined,
      activeIcon: Icons.event_available,
    ),
    _BottomNavItem(
      label: 'GIỎ HÀNG',
      route: AppRoutes.cart,
      icon: Icons.shopping_bag_outlined,
      activeIcon: Icons.shopping_bag,
    ),
  ];

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final current = GoRouter.of(context)
        .routerDelegate
        .currentConfiguration
        .uri
        .path;
    return _BottomNavBarView(
      items: _items,
      currentRoute: current,
      onTap: (route) => _go(context, route),
    );
  }

  void _go(BuildContext context, String route) {
    // Các route đã có screen (xem lib/app/routes.dart).
    if (route == AppRoutes.home ||
        route == AppRoutes.splash ||
        route == AppRoutes.reservation) {
      context.go(route);
      return;
    }

    // Route chưa có screen -> show snackbar "đang phát triển".
    final feature = _featureFor(route);
    ComingSoon.show(context, feature: feature);
  }

  String? _featureFor(String route) {
    switch (route) {
      case AppRoutes.menu:
        return 'Thực đơn';
      case AppRoutes.cart:
        return 'Giỏ hàng';
      default:
        return null;
    }
  }
}

/// Internal view widget — tách riêng để có thể test/preview độc lập
/// với router (nhận currentRoute + onTap qua prop).
class _BottomNavBarView extends StatelessWidget {
  const _BottomNavBarView({
    required this.items,
    required this.currentRoute,
    required this.onTap,
  });

  final List<_BottomNavItem> items;
  final String currentRoute;
  final void Function(String route) onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        border: const Border(
          top: BorderSide(color: AppColors.border, width: 1),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 10,
            offset: const Offset(0, -2),
          ),
        ],
      ),
      padding: EdgeInsets.only(
        top: 6,
        bottom: MediaQuery.of(context).padding.bottom + 4,
      ),
      child: SafeArea(
        top: false,
        child: Row(
          children: [
            for (final item in items)
              Expanded(
                child: _NavTab(
                  item: item,
                  isActive: _isActive(currentRoute, item.route),
                  onTap: () => onTap(item.route),
                ),
              ),
          ],
        ),
      ),
    );
  }

  bool _isActive(String currentRoute, String itemRoute) {
    if (itemRoute == AppRoutes.home) {
      return currentRoute == AppRoutes.home || currentRoute == AppRoutes.splash;
    }
    return currentRoute == itemRoute || currentRoute.startsWith('$itemRoute/');
  }
}

class _BottomNavItem {
  const _BottomNavItem({
    required this.label,
    required this.route,
    required this.icon,
    required this.activeIcon,
  });

  final String label;
  final String route;
  final IconData icon;
  final IconData activeIcon;
}

class _NavTab extends StatelessWidget {
  const _NavTab({
    required this.item,
    required this.isActive,
    required this.onTap,
  });

  final _BottomNavItem item;
  final bool isActive;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    // Tab active có nền vàng (gold) pill bo tròn — khớp PHP design.
    return InkWell(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 4),
        padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 4),
        decoration: BoxDecoration(
          color: isActive ? AppColors.gold : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              isActive ? item.activeIcon : item.icon,
              size: 20,
              color: isActive ? AppColors.primaryStrong : AppColors.textMuted,
            ),
            const SizedBox(height: 4),
            Text(
              item.label,
              style: TextStyle(
                fontSize: 9,
                fontWeight: FontWeight.w900,
                letterSpacing: 0.08,
                color:
                    isActive ? AppColors.primaryStrong : AppColors.textMuted,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
