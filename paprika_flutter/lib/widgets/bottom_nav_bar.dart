import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../l10n/generated/app_localizations.dart';
import '../providers/providers.dart';
import 'coming_soon.dart';
import 'page_transition_loader.dart';

/// Bottom navigation bar 4 tab theo Laravel Blade storefront
/// (`components.bottom-nav` / `_bottom_nav.blade.php`).
///
/// Tab đã có screen sẽ navigate; tab chưa có sẽ show ComingSoon snackbar.
class BottomNavBar extends ConsumerWidget {
  const BottomNavBar({super.key});

  /// Build nav items từ AppLocalizations (mirror paprika_header pattern).
  List<_BottomNavItem> _buildItems(AppLocalizations l) => <_BottomNavItem>[
    _BottomNavItem(
      label: l.bottomNavHome,
      route: AppRoutes.home,
      icon: Icons.home_outlined,
      activeIcon: Icons.home,
    ),
    _BottomNavItem(
      label: l.bottomNavMenu,
      route: AppRoutes.menu,
      icon: Icons.restaurant_menu_outlined,
      activeIcon: Icons.restaurant_menu,
    ),
    _BottomNavItem(
      label: l.bottomNavReservation,
      route: AppRoutes.reservation,
      icon: Icons.event_outlined,
      activeIcon: Icons.event_available,
    ),
    _BottomNavItem(
      label: l.bottomNavCart,
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
      items: _buildItems(AppLocalizations.of(context)),
      currentRoute: current,
      cartCount: ref.watch(cartCountProvider),
      onTap: (route) => _go(context, route),
    );
  }

  void _go(BuildContext context, String route) {
    // Các route đã có screen (xem lib/app/routes.dart).
    if (route == AppRoutes.home ||
        route == AppRoutes.splash ||
        route == AppRoutes.menu ||
        route == AppRoutes.cart ||
        route == AppRoutes.reservation) {
      context.showPageLoader();
      context.go(route);
      return;
    }

    // Route chưa có screen -> show snackbar "đang phát triển".
    final l = AppLocalizations.of(context);
    final feature = _featureFor(route, l);
    ComingSoon.show(context, feature: feature);
  }

  String? _featureFor(String route, AppLocalizations l) {
    switch (route) {
      case AppRoutes.menu:
        return l.featureMenu;
      case AppRoutes.cart:
        return l.featureCart;
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
    required this.cartCount,
    required this.onTap,
  });

  final List<_BottomNavItem> items;
  final String currentRoute;
  final int cartCount;
  final void Function(String route) onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.primary,
        border: const Border(
          top: BorderSide(color: AppColors.primaryStrong, width: 1),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.18),
            blurRadius: 12,
            offset: const Offset(0, -3),
          ),
        ],
      ),
      padding: EdgeInsets.only(
        left: 8,
        right: 8,
        top: 6,
        bottom: MediaQuery.of(context).padding.bottom + 6,
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
                  count: item.route == AppRoutes.cart ? cartCount : 0,
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
    required this.count,
    required this.onTap,
  });

  final _BottomNavItem item;
  final bool isActive;
  final int count;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 2),
        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
        decoration: BoxDecoration(
          color: isActive ? AppColors.primaryStrong : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Stack(
              clipBehavior: Clip.none,
              children: [
                Icon(
                  isActive ? item.activeIcon : item.icon,
                  size: 20,
                  color: isActive ? AppColors.gold : const Color(0xFFD6D3D1),
                ),
                if (count > 0)
                  Positioned(
                    top: -7,
                    right: -10,
                    child: Container(
                      constraints:
                          const BoxConstraints(minWidth: 16, minHeight: 16),
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                      decoration: BoxDecoration(
                        color: AppColors.accent,
                        borderRadius: BorderRadius.circular(999),
                        border: Border.all(color: AppColors.primary, width: 1),
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        '$count',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 9,
                          fontWeight: FontWeight.w900,
                          height: 1,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              item.label,
              style: TextStyle(
                fontSize: 9,
                fontWeight: FontWeight.w900,
                letterSpacing: 0.5,
                color: isActive ? AppColors.gold : const Color(0xFFD6D3D1),
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
