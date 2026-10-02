import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/config/locales.dart';
import '../core/i18n/locale_controller.dart';
import '../core/i18n/ui_text.dart';
import '../l10n/generated/app_localizations.dart';
import '../providers/providers.dart';
import 'cart_drawer.dart';
import 'coming_soon.dart';
import 'page_transition_loader.dart';

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
/// [cartItemsCount] chỉ còn là fallback cho preview/test; trong app thật badge
/// đọc từ `cartCountProvider`.
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

  /// Build nav items list từ AppLocalizations.
  /// Phải là method (không const) vì phụ thuộc locale hiện tại.
  List<_NavItem> _buildNavItems(AppLocalizations l, UiText t) => <_NavItem>[
    _NavItem(label: l.navHome, route: AppRoutes.home),
    _NavItem(label: l.navMenu, route: AppRoutes.menu),
    _NavItem(
      label: t.blog,
      route: AppRoutes.blog,
      icon: Icons.article_outlined,
    ),
    _NavItem(
      label: t.gallery,
      route: AppRoutes.gallery,
      icon: Icons.photo_library_outlined,
    ),
    _NavItem(
      label: t.pages,
      route: AppRoutes.pages,
      icon: Icons.description_outlined,
    ),
    _NavItem(
      label: t.vouchers,
      route: AppRoutes.vouchers,
      icon: Icons.local_offer_outlined,
    ),
    _NavItem(label: l.navAbout, route: AppRoutes.about, icon: Icons.info_outline),
    _NavItem(label: l.navBranches, route: AppRoutes.branches, icon: Icons.storefront_outlined),
    _NavItem(label: l.navContact, route: AppRoutes.contact, icon: Icons.contact_mail_outlined),
    _NavItem(
      label: t.allergens,
      route: AppRoutes.allergenSettings,
      icon: Icons.health_and_safety_outlined,
    ),
    _NavItem(label: l.footerLinkOrderLookup, route: AppRoutes.orders, icon: Icons.manage_search_outlined),
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
        route == AppRoutes.menu ||
        route == AppRoutes.search ||
        route == AppRoutes.blog ||
        route == AppRoutes.gallery ||
        route == AppRoutes.pages ||
        route == AppRoutes.vouchers ||
        route == AppRoutes.cart ||
        route == AppRoutes.checkout ||
        route == AppRoutes.reservation ||
        route == AppRoutes.reservations ||
        route == AppRoutes.orders ||
        route == AppRoutes.allergenSettings) {
      context.showPageLoader();
      context.go(route);
      return;
    }

    // Route chưa có screen -> show snackbar "đang phát triển".
    final l = AppLocalizations.of(context);
    final featureName = _featureNameFor(route, l);
    ComingSoon.show(context, feature: featureName);
  }

  String? _featureNameFor(String route, AppLocalizations l) {
    switch (route) {
      case AppRoutes.menu:
        return l.featureMenu;
      case AppRoutes.cart:
        return l.featureCart;
      case AppRoutes.reservations:
        return l.featureReservations;
      case AppRoutes.search:
        return l.featureSearch;
      case AppRoutes.profile:
        return l.featureProfile;
      case AppRoutes.orders:
        return l.featureOrders;
      case AppRoutes.notifications:
        return l.featureNotifications;
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
    final screenWidth = MediaQuery.sizeOf(context).width;
    final isCompact = screenWidth < 380;
    final isTiny = screenWidth < 320;
    return Container(
      decoration: const BoxDecoration(
        border: Border(
          bottom: BorderSide(color: AppColors.primaryStrong, width: 1),
        ),
      ),
      padding: EdgeInsets.symmetric(
        horizontal: isTiny ? 6 : (isCompact ? 10 : AppConstants.spaceMd),
        vertical: AppConstants.spaceSm,
      ),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final isWide = constraints.maxWidth >= 1040;
          const showWordmark = true;
          const showLanguage = true;
          return Row(
            children: [
              _buildLogo(context, showWordmark: showWordmark),
              if (isWide) ...[
                const SizedBox(width: AppConstants.spaceMd),
                Expanded(child: _buildDesktopNav(context)),
                const SizedBox(width: AppConstants.spaceSm),
                _buildLanguageSelector(context),
                const SizedBox(width: AppConstants.spaceXs),
              ] else ...[
                const Spacer(),
                if (showLanguage) ...[
                  _buildLanguageSelector(context),
                  const SizedBox(width: AppConstants.spaceXs),
                ],
              ],
              _buildHeaderActions(context, showBooking: isWide),
              if (!isWide) ...[
                SizedBox(width: isTiny ? 2 : AppConstants.spaceXs),
                _buildMobileToggle(context),
              ],
            ],
          );
        },
      ),
    );
  }

  Widget _buildLogo(BuildContext context, {bool showWordmark = true}) {
    final width = MediaQuery.sizeOf(context).width;
    final isTiny = width < 320;
    final isCompact = width < 380;
    final logoSize = isTiny ? 34.0 : (isCompact ? 38.0 : 48.0);
    return InkWell(
      onTap: () => _go(context, AppRoutes.home),
      borderRadius: BorderRadius.circular(8),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Logo tròn 48x48 với nền trắng + shadow + viền trắng (giống PHP)
          Container(
            width: logoSize,
            height: logoSize,
            decoration: BoxDecoration(
              color: Colors.white,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.18),
                  blurRadius: 6,
                  offset: const Offset(0, 2),
                ),
              ],
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.3),
                width: 1,
              ),
            ),
            padding: const EdgeInsets.all(3),
            child: ClipOval(
              child: Image.asset(
                AppConstants.logoHeader,
                fit: BoxFit.contain,
              ),
            ),
          ),
          if (showWordmark) ...[
            SizedBox(width: isTiny ? 4 : 8),
            // Wordmark - ảnh "Paprika" viết tay (h-8 mobile, h-9 desktop như PHP)
            Builder(
              builder: (context) {
                final isDesktop = width >= 768;
                final maxWordmarkWidth =
                    isTiny ? 44.0 : (isCompact ? 66.0 : 118.0);
                return ConstrainedBox(
                  constraints: BoxConstraints(
                    maxWidth: isDesktop ? 180 : maxWordmarkWidth,
                  ),
                  child: SizedBox(
                    height: isDesktop ? 36 : (isTiny ? 22 : 28),
                    child: Image.asset(
                      AppConstants.wordmark,
                      fit: BoxFit.contain,
                      alignment: Alignment.centerLeft,
                    ),
                  ),
                );
              },
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildDesktopNav(BuildContext context) {
    final current = _currentRoute;
    final navItems = _buildNavItems(AppLocalizations.of(context), UiText.of(context));
    return Wrap(
      alignment: WrapAlignment.center,
      crossAxisAlignment: WrapCrossAlignment.center,
      spacing: 2,
      runSpacing: 2,
      children: [
        for (final item in navItems)
          _NavLink(
            label: item.label,
            icon: item.icon,
            isActive: _isActive(current, item.route),
            onTap: () => _go(context, item.route),
          ),
      ],
    );
  }

  Widget _buildLanguageSelector(BuildContext context) {
    // Đọc locale hiện tại từ LocaleController để highlight + rebuild khi đổi.
    final current = ref.watch(localeProvider).languageCode;
    final currentMeta = AppLocales.metaOf(current);
    final isTiny = MediaQuery.sizeOf(context).width < 320;

    return Material(
      color: Colors.white.withValues(alpha: 0.15),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(999),
        side: const BorderSide(color: Colors.white24),
      ),
      child: PopupMenuButton<String>(
        tooltip: AppLocalizations.of(context).languageSwitcherTooltip,
        position: PopupMenuPosition.under,
        onSelected: (code) {
          ref.read(localeProvider.notifier).setLocale(code);
        },
        itemBuilder: (_) {
          return AppLocales.supportedList.map((meta) {
            final isSelected = meta.code == current;
            return PopupMenuItem<String>(
              value: meta.code,
              child: Row(
                children: [
                  Text(
                    meta.flag,
                    style: const TextStyle(fontSize: 18),
                  ),
                  const SizedBox(width: 10),
                  Text(
                    meta.native,
                    style: TextStyle(
                      fontWeight:
                          isSelected ? FontWeight.bold : FontWeight.w500,
                      color: isSelected
                          ? AppColors.accent
                          : Theme.of(context).textTheme.bodyLarge?.color,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    '(${meta.code.toUpperCase()})',
                    style: TextStyle(
                      color: isSelected
                          ? AppColors.accent
                          : Colors.grey[600],
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  if (isSelected) ...[
                    const SizedBox(width: 6),
                    const Icon(
                      Icons.check,
                      size: 14,
                      color: AppColors.accent,
                    ),
                  ],
                ],
              ),
            );
          }).toList(growable: false);
        },
        child: Padding(
          padding: EdgeInsets.symmetric(
            horizontal: isTiny ? 6 : 8,
            vertical: isTiny ? 5 : 6,
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                currentMeta.flag,
                style: const TextStyle(fontSize: 13),
              ),
              const SizedBox(width: 4),
              Text(
                currentMeta.code.toUpperCase(),
                style: TextStyle(
                  color: Colors.white,
                  fontSize: isTiny ? 10 : 11,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 0.12,
                ),
              ),
              const SizedBox(width: 2),
              Icon(
                Icons.keyboard_arrow_down,
                color: Colors.white,
                size: isTiny ? 12 : 14,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeaderActions(BuildContext context, {bool showBooking = true}) {
    final l = AppLocalizations.of(context);
    final watchedCount = ref.watch(cartCountProvider);
    final count = watchedCount > 0 ? watchedCount : widget.cartItemsCount;
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        _HeaderCircleAction(
          tooltip: 'Tìm kiếm',
          icon: Icons.search,
          backgroundColor: Colors.white.withValues(alpha: 0.15),
          borderColor: Colors.white24,
          onTap: () => _go(context, AppRoutes.search),
        ),
        SizedBox(
          width: MediaQuery.sizeOf(context).width < 320
              ? 4
              : AppConstants.spaceXs,
        ),
        if (showBooking) ...[
          _HeaderCircleAction(
            tooltip: l.navReservation,
            icon: Icons.calendar_month_outlined,
            backgroundColor: Colors.white.withValues(alpha: 0.15),
            borderColor: Colors.white24,
            onTap: () => _go(context, AppRoutes.reservation),
          ),
          SizedBox(
            width: MediaQuery.sizeOf(context).width < 320
                ? 4
                : AppConstants.spaceXs,
          ),
        ],
        Stack(
          clipBehavior: Clip.none,
          children: [
            _HeaderCircleAction(
              tooltip: l.navCart,
              icon: Icons.shopping_bag_outlined,
              backgroundColor: AppColors.accent,
              borderColor: Colors.transparent,
              onTap: () => showCartDrawer(context),
            ),
            if (count > 0)
              Positioned(
                top: -5,
                right: -5,
                child: Container(
                  constraints:
                      const BoxConstraints(minWidth: 20, minHeight: 20),
                  padding: const EdgeInsets.symmetric(horizontal: 5),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(999),
                    border: Border.all(color: AppColors.accent, width: 1),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.18),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  alignment: Alignment.center,
                  child: Text(
                    '$count',
                    style: const TextStyle(
                      color: AppColors.accent,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      height: 1.0,
                    ),
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }

  Widget _buildMobileToggle(BuildContext context) {
    final l = AppLocalizations.of(context);
    final isTiny = MediaQuery.sizeOf(context).width < 320;
    final isCompact = MediaQuery.sizeOf(context).width < 380;
    return IconButton(
      tooltip: _isMobileMenuOpen ? l.commonClose : l.navMenu,
      onPressed: () => setState(() => _isMobileMenuOpen = !_isMobileMenuOpen),
      constraints: BoxConstraints.tightFor(
        width: isTiny ? 32 : (isCompact ? 34 : 42),
        height: isTiny ? 32 : (isCompact ? 34 : 42),
      ),
      padding: EdgeInsets.zero,
      icon: Icon(
        _isMobileMenuOpen ? Icons.close : Icons.menu,
        color: Colors.white,
        size: isTiny ? 20 : (isCompact ? 21 : 24),
      ),
    );
  }

  Widget _buildMobileMenu() {
    final current = _currentRoute;
    final navItems = _buildNavItems(AppLocalizations.of(context), UiText.of(context));
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
          for (final item in navItems) ...[
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

class _HeaderCircleAction extends StatelessWidget {
  const _HeaderCircleAction({
    required this.tooltip,
    required this.icon,
    required this.backgroundColor,
    required this.borderColor,
    required this.onTap,
  });

  final String tooltip;
  final IconData icon;
  final Color backgroundColor;
  final Color borderColor;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final isTiny = width < 320;
    final isCompact = width < 380;
    return Tooltip(
      message: tooltip,
      child: Container(
        width: isTiny ? 30 : (isCompact ? 34 : 42),
        height: isTiny ? 30 : (isCompact ? 34 : 42),
        decoration: BoxDecoration(
          color: backgroundColor,
          shape: BoxShape.circle,
          border: Border.all(color: borderColor),
        ),
        child: Material(
          color: Colors.transparent,
          shape: const CircleBorder(),
          child: InkWell(
            customBorder: const CircleBorder(),
            onTap: onTap,
            child: Center(
              child: Icon(
                icon,
                color: Colors.white,
                size: isTiny ? 17 : (isCompact ? 19 : 20),
              ),
            ),
          ),
        ),
      ),
    );
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
          horizontal: 10,
          vertical: 8,
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
              fontSize: 10,
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
