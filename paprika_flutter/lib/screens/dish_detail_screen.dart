import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/utils/image_helper.dart';
import '../data/models/dish_model.dart';
import '../providers/providers.dart';

/// DishDetailScreen — Trang chi tiết món ăn (mirror `storefront/menu/show.blade.php`).
///
/// Layout mobile-first (giống mobile bottom sheet trên web):
///   1. Hero — Ảnh full-width + gradient overlay + breadcrumb chips + title + 3 stats chips
///   2. Sticky panel — Tabs Customise / Nutrition / Allergens
///   3. Content scroll — Options + note (Customise) | Rows (Nutrition) | Grid (Allergens)
///   4. Sticky bottom — Quantity stepper + Add to cart
///   5. Related dishes — Horizontal carousel 4 món
class DishDetailScreen extends ConsumerStatefulWidget {
  const DishDetailScreen({super.key, required this.dishId});

  final int dishId;

  @override
  ConsumerState<DishDetailScreen> createState() => _DishDetailScreenState();
}

class _DishDetailScreenState extends ConsumerState<DishDetailScreen> {
  /// Tab đang active.
  _DetailTab _activeTab = _DetailTab.customise;

  /// Số lượng.
  int _quantity = 1;

  /// Note tự do cho đơn (vd "Ít cay").
  final TextEditingController _noteController = TextEditingController();

  /// Mapping groupId → Set optionId đang chọn (khởi tạo từ default options).
  final Map<int, Set<int>> _selectedOptions = {};

  /// Đã init từ defaults lần đầu chưa (tránh reset khi rebuild).
  bool _defaultsInitialized = false;

  /// Các loại dị ứng user đã khai báo trong "Quản lý dị ứng"
  /// (đọc từ SharedPreferences key `user_allergens`, tên tiếng Việt —
  /// giống list ở [AllergenSettingsScreen]).
  Set<String> _userAllergens = {};

  @override
  void initState() {
    super.initState();
    _loadUserAllergens();
  }

  Future<void> _loadUserAllergens() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getStringList('user_allergens') ?? const [];
    if (!mounted) return;
    setState(() => _userAllergens = saved.toSet());
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  /// Khởi tạo lựa chọn mặc định từ `is_default` của options.
  void _initDefaults(DishDetail detail) {
    if (_defaultsInitialized) return;
    for (final group in detail.options) {
      final selected = <int>{};
      for (final opt in group.options) {
        if (opt.isDefault) selected.add(opt.id);
      }
      _selectedOptions[group.id] = selected;
    }
    _defaultsInitialized = true;
  }

  DishDetail? _currentDetail;

  /// Tăng/giảm số lượng (1-99).
  void _onInc() {
    if (_quantity < 99) setState(() => _quantity++);
  }

  void _onDec() {
    if (_quantity > 1) setState(() => _quantity--);
  }

  /// Tap "Thêm vào giỏ" — hiện tại chỉ show snackbar, khi nào cart API xong
  /// thì gọi _cartRepo.add(detail, _selectedOptions, _quantity, _noteController.text).
  void _onAddToCart() async {
    final detail = _currentDetail;
    if (detail == null) return;

    // Kiểm tra món có chứa allergen user tránh không
    final matchingAllergens = detail.allergens
        .where((a) => a.contains && _userAllergens.contains(a.name))
        .toList();

    if (matchingAllergens.isNotEmpty) {
      // Hiện confirm dialog
      final confirm = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          backgroundColor: AppColors.cream,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Row(
            children: [
              Icon(Icons.warning_rounded, color: AppColors.accent, size: 24),
              SizedBox(width: 10),
              Text(
                'Xác nhận đặt món',
                style: TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 16,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ],
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Món này chứa ${matchingAllergens.map((a) => a.name).join(', ')} — bạn đã đánh dấu tránh trong "Quản lý dị ứng".',
                style: const TextStyle(
                  color: AppColors.textSecondary,
                  fontSize: 13,
                  height: 1.5,
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'Bạn vẫn muốn thêm vào giỏ?',
                style: TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text(
                'Huỷ',
                style: TextStyle(
                  color: AppColors.textMuted,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(true),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.accent,
                foregroundColor: Colors.white,
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
              child: const Text(
                'Vẫn thêm',
                style: TextStyle(fontWeight: FontWeight.w900),
              ),
            ),
          ],
        ),
      );

      if (confirm != true) return; // User huỷ
    }

    // Proceed với add to cart
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        backgroundColor: AppColors.primaryStrong,
        behavior: SnackBarBehavior.floating,
        content: Row(
          children: [
            const Icon(Icons.check_circle, color: AppColors.gold, size: 18),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                'Đã thêm ${_quantity}x ${detail.name} vào giỏ (TODO: nối cart API).',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _toggleOption(DishOptionGroup group, DishOptionItem option) {
    setState(() {
      final current = _selectedOptions.putIfAbsent(group.id, () => <int>{});
      if (group.isExclude) {
        // Exclude: check = "loại bỏ option này" (toggle).
        if (current.contains(option.id)) {
          current.remove(option.id);
        } else {
          current.add(option.id);
        }
      } else if (group.isSingle) {
        // Single: chỉ chọn 1 — radio behavior.
        current
          ..clear()
          ..add(option.id);
      } else {
        // Multiple: toggle checkbox.
        if (current.contains(option.id)) {
          current.remove(option.id);
        } else {
          current.add(option.id);
        }
      }
    });
  }

  bool _isSelected(DishOptionGroup group, DishOptionItem option) {
    return _selectedOptions[group.id]?.contains(option.id) ?? false;
  }

  /// Note preview - tổng hợp option đã chọn + note text.
  String _summaryText(DishDetail detail) {
    final parts = <String>[];
    for (final group in detail.options) {
      final selected = _selectedOptions[group.id] ?? const <int>{};
      if (selected.isEmpty) continue;
      for (final opt in group.options) {
        if (selected.contains(opt.id)) {
          parts.add('${group.name}: ${opt.name}');
        }
      }
    }
    final note = _noteController.text.trim();
    if (note.isNotEmpty) parts.add('Ghi chú: $note');
    return parts.isEmpty ? 'Công thức tiêu chuẩn' : parts.join(' | ');
  }

  @override
  Widget build(BuildContext context) {
    final detailAsync = ref.watch(dishDetailProvider(widget.dishId));

    return Scaffold(
      backgroundColor: AppColors.cream,
      body: detailAsync.when(
        loading: () => const _LoadingState(),
        error: (err, _) => _ErrorState(
          message: '$err',
          onRetry: () => ref.invalidate(dishDetailProvider(widget.dishId)),
        ),
        data: (detail) {
          _currentDetail = detail;
          _initDefaults(detail);
          return _buildLoaded(detail);
        },
      ),
      bottomNavigationBar: detailAsync.maybeWhen(
        data: (detail) => _buildStickyBottomBar(detail),
        orElse: () => null,
      ),
    );
  }

  /// Bottom bar dính dưới màn hình — luôn hiện khi detail load xong,
  /// kể cả khi đang ở tab Nutrition/Allergens.
  Widget _buildStickyBottomBar(DishDetail detail) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        boxShadow: const [
          BoxShadow(
            color: Colors.black12,
            blurRadius: 6,
            offset: Offset(0, -2),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
          child: _BottomActionBar(
            quantity: _quantity,
            unitPriceCents: unitPrice(detail),
            onInc: _onInc,
            onDec: _onDec,
            onAddToCart: _onAddToCart,
          ),
        ),
      ),
    );
  }

  Widget _buildLoaded(DishDetail detail) {
    return CustomScrollView(
      slivers: [
        SliverToBoxAdapter(child: _DishHero(detail: detail)),
        SliverToBoxAdapter(
          child: _DetailPanelHeader(
            detail: detail,
            activeTab: _activeTab,
            onTabChange: (t) => setState(() => _activeTab = t),
          ),
        ),
        SliverPadding(
          padding: const EdgeInsets.fromLTRB(
            AppConstants.spaceMd,
            AppConstants.spaceMd,
            AppConstants.spaceMd,
            AppConstants.spaceLg,
          ),
          sliver: SliverToBoxAdapter(
            child: _buildTabContent(detail),
          ),
        ),
        if (detail.relatedDishes.isNotEmpty)
          SliverToBoxAdapter(child: _RelatedDishesRow(dishes: detail.relatedDishes)),
        const SliverToBoxAdapter(child: SizedBox(height: AppConstants.spaceLg)),
      ],
    );
  }

  Widget _buildTabContent(DishDetail detail) {
    switch (_activeTab) {
      case _DetailTab.customise:
        return _CustomisePanel(
          detail: detail,
          selectedOptions: _selectedOptions,
          noteController: _noteController,
          onToggleOption: _toggleOption,
          isSelected: _isSelected,
          summaryText: _summaryText(detail),
        );
      case _DetailTab.nutrition:
        return _NutritionPanel(nutrition: detail.nutrition);
      case _DetailTab.allergens:
        return _AllergensPanel(
          allergens: detail.allergens,
          userAllergens: _userAllergens,
        );
    }
  }
}

/// Các tab ở panel dưới hero.
enum _DetailTab { customise, nutrition, allergens }

// ===========================================================================
// HERO
// ===========================================================================

class _DishHero extends StatelessWidget {
  const _DishHero({required this.detail});
  final DishDetail detail;

  @override
  Widget build(BuildContext context) {
    final media = MediaQuery.of(context);
    final heroHeight = media.size.height * 0.42;

    return SizedBox(
      height: heroHeight,
      child: Stack(
        fit: StackFit.expand,
        children: [
          // Ảnh nền (ưu tiên srcset hero, fallback image, fallback image_fallback).
          _HeroImage(detail: detail),
          // Gradient overlay.
          const DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Colors.black54,
                  Colors.transparent,
                  Color(0xCC032219),
                ],
                stops: [0.0, 0.35, 1.0],
              ),
            ),
          ),
          // Nút back.
          Positioned(
            top: media.padding.top + 8,
            left: 12,
            child: _CircleIconButton(
              icon: Icons.arrow_back_ios_new,
              onTap: () => Navigator.of(context).maybePop(),
            ),
          ),
          // Nội dung dưới đáy.
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _HeroChips(detail: detail),
                    const SizedBox(height: 12),
                    Text(
                      detail.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 30,
                        fontWeight: FontWeight.w900,
                        height: 1.05,
                        letterSpacing: -0.5,
                      ),
                    ),
                    if (detail.description != null &&
                        detail.description!.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        detail.description!,
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Colors.white.withValues(alpha: 0.85),
                          fontSize: 13,
                          height: 1.4,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                    const SizedBox(height: 14),
                    if (detail.stats != null) _StatsRow(stats: detail.stats!),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Hero image - ưu tiên srcset "hero" → "image" → "image_fallback".
class _HeroImage extends StatelessWidget {
  const _HeroImage({required this.detail});
  final DishDetail detail;

  String? get _url {
    final srcset = detail.imageSrcset;
    for (final entry in srcset) {
      if (entry.variant == 'hero' && entry.url.isNotEmpty) return entry.url;
    }
    if (detail.image != null && detail.image!.isNotEmpty) return detail.image;
    return detail.imageFallback;
  }

  @override
  Widget build(BuildContext context) {
    final url = _url;
    final resolved = ImageHelper.url(url);
    if (resolved == null) return _placeholder();
    return Image.network(
      resolved,
      fit: BoxFit.cover,
      errorBuilder: (_, __, ___) => _placeholder(),
      loadingBuilder: (context, child, progress) {
        if (progress == null) return child;
        return _placeholder(loading: true);
      },
    );
  }

  Widget _placeholder({bool loading = false}) => Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [AppColors.primary, AppColors.primaryStrong],
          ),
        ),
        alignment: Alignment.center,
        child: loading
            ? const SizedBox(
                width: 26,
                height: 26,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
                  valueColor: AlwaysStoppedAnimation(Colors.white),
                ),
              )
            : const Icon(Icons.restaurant_menu, color: Colors.white, size: 56),
      );
}

/// Chips trên hero (category + featured + availability).
class _HeroChips extends StatelessWidget {
  const _HeroChips({required this.detail});
  final DishDetail detail;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 6,
      runSpacing: 6,
      children: [
        if (detail.category != null)
          _ChipPill(
            label: detail.category!.name.toUpperCase(),
            background: AppColors.accent,
            textColor: Colors.white,
            icon: Icons.local_fire_department,
            iconColor: AppColors.gold,
          ),
        if (detail.isFeatured)
          const _ChipPill(
            label: 'NỔI BẬT',
            background: AppColors.primary,
            textColor: Colors.white,
          ),
        if (detail.availability.label != null &&
            detail.availability.label!.isNotEmpty)
          _ChipPill(
            label: detail.availability.label!.toUpperCase(),
            background: Colors.black54,
            textColor: Colors.white,
          ),
      ],
    );
  }
}

/// 3 stats chips dưới hero (prep / energy / branch).
class _StatsRow extends StatelessWidget {
  const _StatsRow({required this.stats});
  final DishStats stats;

  @override
  Widget build(BuildContext context) {
    final items = [
      (stats.prepLabel, stats.prepValue, Icons.timer_outlined),
      (stats.energyLabel, stats.energyValue, Icons.local_fire_department_outlined),
      (stats.branchLabel, stats.branchValue, Icons.store_outlined),
    ];
    return Row(
      children: [
        for (var i = 0; i < items.length; i++) ...[
          if (i > 0) const SizedBox(width: 8),
          Expanded(child: _StatTile(
            label: items[i].$1,
            value: items[i].$2,
            icon: items[i].$3,
          )),
        ],
      ],
    );
  }
}

class _StatTile extends StatelessWidget {
  const _StatTile({
    required this.label,
    required this.value,
    required this.icon,
  });

  final String label;
  final String value;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white10,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white24),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              Icon(icon, color: AppColors.gold, size: 13),
              const SizedBox(width: 4),
              Flexible(
                child: Text(
                  label.toUpperCase(),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.7),
                    fontSize: 9,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 13,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _ChipPill extends StatelessWidget {
  const _ChipPill({
    required this.label,
    required this.background,
    required this.textColor,
    this.icon,
    this.iconColor,
  });

  final String label;
  final Color background;
  final Color textColor;
  final IconData? icon;
  final Color? iconColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
        boxShadow: const [
          BoxShadow(
            color: Colors.black26,
            blurRadius: 4,
            offset: Offset(0, 1),
          ),
        ],
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, color: iconColor ?? textColor, size: 12),
            const SizedBox(width: 4),
          ],
          Text(
            label,
            style: TextStyle(
              color: textColor,
              fontSize: 9,
              fontWeight: FontWeight.w900,
              letterSpacing: 0.6,
            ),
          ),
        ],
      ),
    );
  }
}

class _CircleIconButton extends StatelessWidget {
  const _CircleIconButton({required this.icon, required this.onTap});

  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white.withValues(alpha: 0.92),
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: Container(
          width: 40,
          height: 40,
          alignment: Alignment.center,
          child: Icon(icon, size: 18, color: AppColors.primary),
        ),
      ),
    );
  }
}

// ===========================================================================
// DETAIL PANEL HEADER + TABS
// ===========================================================================

class _DetailPanelHeader extends StatelessWidget {
  const _DetailPanelHeader({
    required this.detail,
    required this.activeTab,
    required this.onTabChange,
  });

  final DishDetail detail;
  final _DetailTab activeTab;
  final ValueChanged<_DetailTab> onTabChange;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: AppColors.surface,
        border: Border(bottom: BorderSide(color: Color(0xFFE2D8C8))),
      ),
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text(
                      'CHI TIẾT',
                      style: TextStyle(
                        color: AppColors.primary,
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 1.5,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      detail.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppColors.textPrimary,
                        fontSize: 18,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  const Text(
                    'GIÁ TỪ',
                    style: TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 9,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.8,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    _formatPriceFromDetail(detail),
                    style: const TextStyle(
                      color: AppColors.primaryStrong,
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 14),
          _TabBar(active: activeTab, onChange: onTabChange),
        ],
      ),
    );
  }

  String _formatPriceFromDetail(DishDetail d) {
    final cents = d.currentPrice;
    final euros = cents / 100;
    return '€ ${euros.toStringAsFixed(2).replaceAll('.', ',')}';
  }
}

class _TabBar extends StatelessWidget {
  const _TabBar({required this.active, required this.onChange});

  final _DetailTab active;
  final ValueChanged<_DetailTab> onChange;

  @override
  Widget build(BuildContext context) {
    final tabs = [
      (_DetailTab.customise, 'TÙY CHỌN'),
      (_DetailTab.nutrition, 'DINH DƯỠNG'),
      (_DetailTab.allergens, 'DỊ ỨNG'),
    ];
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFFF5F0E6),
        borderRadius: BorderRadius.circular(12),
      ),
      padding: const EdgeInsets.all(4),
      child: Row(
        children: [
          for (final (tab, label) in tabs) ...[
            Expanded(
              child: _TabButton(
                label: label,
                isActive: active == tab,
                onTap: () => onChange(tab),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _TabButton extends StatelessWidget {
  const _TabButton({
    required this.label,
    required this.isActive,
    required this.onTap,
  });

  final String label;
  final bool isActive;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(10),
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.symmetric(vertical: 9),
          decoration: BoxDecoration(
            color: isActive ? AppColors.surface : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
            boxShadow: isActive
                ? const [
                    BoxShadow(
                      color: Colors.black12,
                      blurRadius: 2,
                      offset: Offset(0, 1),
                    ),
                  ]
                : null,
          ),
          alignment: Alignment.center,
          child: Text(
            label,
            style: TextStyle(
              color: isActive ? AppColors.primary : AppColors.textMuted,
              fontSize: 10,
              fontWeight: FontWeight.w900,
              letterSpacing: 0.6,
            ),
          ),
        ),
      ),
    );
  }
}

// ===========================================================================
// CUSTOMISE PANEL (default tab)
// ===========================================================================

class _CustomisePanel extends StatelessWidget {
  const _CustomisePanel({
    required this.detail,
    required this.selectedOptions,
    required this.noteController,
    required this.onToggleOption,
    required this.isSelected,
    required this.summaryText,
  });

  final DishDetail detail;
  final Map<int, Set<int>> selectedOptions;
  final TextEditingController noteController;
  final void Function(DishOptionGroup, DishOptionItem) onToggleOption;
  final bool Function(DishOptionGroup, DishOptionItem) isSelected;
  final String summaryText;

  @override
  Widget build(BuildContext context) {
    if (detail.options.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.primary.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.primary.withValues(alpha: 0.18)),
        ),
        child: const Row(
          children: [
            Icon(Icons.check_circle_outline,
                color: AppColors.primary, size: 18),
            SizedBox(width: 10),
            Expanded(
              child: Text(
                'Món này dùng công thức tiêu chuẩn — không cần tùy chọn thêm.',
                style: TextStyle(
                  color: AppColors.textPrimary,
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  height: 1.45,
                ),
              ),
            ),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (final group in detail.options) ...[
          _OptionGroupSection(
            group: group,
            selected: selectedOptions[group.id] ?? const <int>{},
            onToggle: (opt) => onToggleOption(group, opt),
            isSelected: (opt) => isSelected(group, opt),
          ),
          const SizedBox(height: 18),
        ],
        _NoteField(controller: noteController),
        const SizedBox(height: 12),
        _SummaryBox(text: summaryText),
      ],
    );
  }
}

class _OptionGroupSection extends StatelessWidget {
  const _OptionGroupSection({
    required this.group,
    required this.selected,
    required this.onToggle,
    required this.isSelected,
  });

  final DishOptionGroup group;
  final Set<int> selected;
  final void Function(DishOptionItem) onToggle;
  final bool Function(DishOptionItem) isSelected;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    group.name.toUpperCase(),
                    style: const TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 11,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.8,
                    ),
                  ),
                  if (group.description != null && group.description!.isNotEmpty) ...[
                    const SizedBox(height: 2),
                    Text(
                      group.description!,
                      style: const TextStyle(
                        color: AppColors.textMuted,
                        fontSize: 11,
                        height: 1.35,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            if (group.hint != null)
              Text(
                group.hint!,
                style: const TextStyle(
                  color: AppColors.textMuted,
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                ),
              ),
          ],
        ),
        const SizedBox(height: 10),
        Column(
          children: [
            for (final opt in group.options) ...[
              _OptionTile(
                item: opt,
                isSelected: isSelected(opt),
                isSingle: group.isSingle,
                isExclude: group.isExclude,
                onTap: () => onToggle(opt),
              ),
              if (opt != group.options.last) const SizedBox(height: 6),
            ],
          ],
        ),
      ],
    );
  }
}

class _OptionTile extends StatelessWidget {
  const _OptionTile({
    required this.item,
    required this.isSelected,
    required this.isSingle,
    required this.isExclude,
    required this.onTap,
  });

  final DishOptionItem item;
  final bool isSelected;
  final bool isSingle;
  final bool isExclude;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final cents = item.priceDelta;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: isSelected
                  ? AppColors.primary
                  : const Color(0xFFE2D8C8),
              width: isSelected ? 1.6 : 1,
            ),
            boxShadow: const [
              BoxShadow(
                color: Color(0x14000000),
                blurRadius: 2,
                offset: Offset(0, 1),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                width: 20,
                height: 20,
                decoration: BoxDecoration(
                  color: isSelected ? AppColors.primary : Colors.transparent,
                  border: Border.all(
                    color: isSelected
                        ? AppColors.primary
                        : const Color(0xFFD4CAB9),
                    width: 1.5,
                  ),
                  shape: isSingle ? BoxShape.circle : BoxShape.rectangle,
                  borderRadius: isSingle ? null : BorderRadius.circular(5),
                ),
                alignment: Alignment.center,
                child: isSelected
                    ? Icon(
                        isSingle ? Icons.circle : Icons.check,
                        size: isSingle ? 8 : 13,
                        color: Colors.white,
                      )
                    : null,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      item.name,
                      style: const TextStyle(
                        color: AppColors.textPrimary,
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    if (item.description != null &&
                        item.description!.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        item.description!,
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 10,
                          height: 1.35,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 8),
              if (cents != 0)
                Text(
                  (cents > 0 ? '+' : '−') + _formatPrice(cents.abs()),
                  style: TextStyle(
                    color: cents > 0 ? AppColors.primary : AppColors.accent,
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                    fontFeatures: const [FontFeature.tabularFigures()],
                  ),
                )
              else
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF5F0E6),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: const Text(
                    'GỒM',
                    style: TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 8,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.5,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatPrice(int cents) {
    final euros = cents / 100;
    return '€ ${euros.toStringAsFixed(2).replaceAll('.', ',')}';
  }
}

class _NoteField extends StatelessWidget {
  const _NoteField({required this.controller});
  final TextEditingController controller;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'GHI CHÚ CHO BẾP',
          style: TextStyle(
            color: AppColors.textMuted,
            fontSize: 11,
            fontWeight: FontWeight.w900,
            letterSpacing: 0.8,
          ),
        ),
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          maxLines: 3,
          maxLength: 500,
          onChanged: (_) => (context as Element).markNeedsBuild(),
          style: const TextStyle(
            color: AppColors.textPrimary,
            fontSize: 13,
            fontWeight: FontWeight.w600,
          ),
          decoration: InputDecoration(
            hintText: 'Vd: ít cay, không hành, thêm đá...',
            hintStyle: const TextStyle(
              color: AppColors.textMuted,
              fontSize: 13,
              fontWeight: FontWeight.w500,
            ),
            filled: true,
            fillColor: AppColors.surface,
            counterStyle: const TextStyle(
              color: AppColors.textMuted,
              fontSize: 10,
              fontWeight: FontWeight.w600,
            ),
            contentPadding: const EdgeInsets.all(12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFFE2D8C8)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFFE2D8C8)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
            ),
          ),
        ),
      ],
    );
  }
}

class _SummaryBox extends StatelessWidget {
  const _SummaryBox({required this.text});
  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.18)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 28,
            height: 28,
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(8),
            ),
            alignment: Alignment.center,
            child: const Icon(Icons.shield_outlined,
                color: AppColors.primary, size: 16),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'BẾP SẼ CHUẨN BỊ',
                  style: TextStyle(
                    color: AppColors.primary,
                    fontSize: 9,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1.0,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  text,
                  style: const TextStyle(
                    color: AppColors.textPrimary,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    height: 1.4,
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

// ===========================================================================
// NUTRITION PANEL
// ===========================================================================

class _NutritionPanel extends StatelessWidget {
  const _NutritionPanel({this.nutrition});
  final DishNutrition? nutrition;

  @override
  Widget build(BuildContext context) {
    if (nutrition == null || nutrition!.rows.isEmpty) {
      return const _EmptyPanelState(text: 'Chưa có thông tin dinh dưỡng.');
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: const Color(0xFFFFF7E0),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFFCD34D)),
          ),
          child: const Row(
            children: [
              Icon(Icons.info_outline,
                  color: Color(0xFF92400E), size: 18),
              SizedBox(width: 10),
              Expanded(
                child: Text(
                  'Số liệu dinh dưỡng là giá trị ước tính trung bình, có thể thay đổi tùy khẩu phần và nguyên liệu thực tế.',
                  style: TextStyle(
                    color: Color(0xFF92400E),
                    fontSize: 12,
                    height: 1.45,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        Container(
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFFE2D8C8)),
          ),
          child: Column(
            children: [
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                decoration: const BoxDecoration(
                  color: Color(0xFFF5F0E6),
                  borderRadius: BorderRadius.vertical(top: Radius.circular(14)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: const [
                    Text(
                      'THÀNH PHẦN',
                      style: TextStyle(
                        color: AppColors.textMuted,
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.8,
                      ),
                    ),
                    Text(
                      'GIÁ TRỊ',
                      style: TextStyle(
                        color: AppColors.textMuted,
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.8,
                      ),
                    ),
                  ],
                ),
              ),
              for (var i = 0; i < nutrition!.rows.length; i++) ...[
                if (i > 0)
                  const Divider(height: 1, color: Color(0xFFF1EADC)),
                _NutritionRow(row: nutrition!.rows[i]),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

class _NutritionRow extends StatelessWidget {
  const _NutritionRow({required this.row});
  final DishNutritionRow row;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            row.label,
            style: const TextStyle(
              color: AppColors.textPrimary,
              fontSize: 13,
              fontWeight: FontWeight.w700,
            ),
          ),
          Text(
            row.value,
            style: const TextStyle(
              color: AppColors.textPrimary,
              fontSize: 13,
              fontWeight: FontWeight.w900,
              fontFeatures: [FontFeature.tabularFigures()],
            ),
          ),
        ],
      ),
    );
  }
}

// ===========================================================================
// ALLERGENS PANEL
// ===========================================================================

class _AllergensPanel extends StatelessWidget {
  const _AllergensPanel({
    this.allergens = const [],
    this.userAllergens = const {},
  });
  final List<DishAllergen> allergens;
  final Set<String> userAllergens;

  @override
  Widget build(BuildContext context) {
    if (allergens.isEmpty) {
      return const _EmptyPanelState(text: 'Chưa có thông tin dị ứng.');
    }

    // Kiểm tra món có chứa allergen user tránh không
    final matchingAllergens = allergens
        .where((a) => a.contains && userAllergens.contains(a.name))
        .toList();
    final hasUserAllergen = matchingAllergens.isNotEmpty;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Personal warning nếu trùng
        if (hasUserAllergen) ...[
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: AppColors.accentSoft,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.accent, width: 2),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.warning_rounded, color: AppColors.accent, size: 22),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        '⚠️ CẢNH BÁO CÁ NHÂN',
                        style: TextStyle(
                          color: AppColors.accentStrong,
                          fontSize: 11,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.8,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Món này chứa ${matchingAllergens.map((a) => a.name).join(', ')} — bạn đã đánh dấu tránh trong "Quản lý dị ứng".',
                        style: const TextStyle(
                          color: AppColors.accentStrong,
                          fontSize: 13,
                          height: 1.5,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 8),
                      GestureDetector(
                        onTap: () => context.push(AppRoutes.allergenSettings),
                        child: const Text(
                          'Chỉnh sửa danh sách →',
                          style: TextStyle(
                            color: AppColors.accent,
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                            decoration: TextDecoration.underline,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
        ],

        // General warning banner
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: AppColors.accentSoft,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFFECACA)),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(Icons.shield, color: AppColors.accentStrong, size: 18),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
                    Text(
                      'CẢNH BÁO DỊ ỨNG',
                      style: TextStyle(
                        color: AppColors.accentStrong,
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.8,
                      ),
                    ),
                    SizedBox(height: 4),
                    Text(
                      'Bếp chế biến trong môi trường có chứa các loại dị ứng thông thường. Nếu bạn có nhu cầu đặc biệt, hãy ghi chú ở phần ghi chú phía trên.',
                      style: TextStyle(
                        color: Color(0xFF8F1F1B),
                        fontSize: 12,
                        height: 1.45,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 8,
          crossAxisSpacing: 8,
          childAspectRatio: 3.6,
          children: [
            for (final a in allergens)
              _AllergenTile(
                allergen: a,
                isUserAllergen: userAllergens.contains(a.name),
              ),
          ],
        ),
      ],
    );
  }
}

class _AllergenTile extends StatelessWidget {
  const _AllergenTile({
    required this.allergen,
    this.isUserAllergen = false,
  });
  final DishAllergen allergen;
  final bool isUserAllergen;

  @override
  Widget build(BuildContext context) {
    final contains = allergen.contains;
    // Nếu món chứa allergen và user đã đánh dấu tránh → highlight đậm hơn
    final isDanger = contains && isUserAllergen;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: isDanger
            ? AppColors.accentSoft
            : AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDanger
              ? AppColors.accent
              : (contains ? const Color(0xFFFCA5A5) : const Color(0xFFE2D8C8)),
          width: isDanger ? 2 : (contains ? 1.4 : 1),
        ),
      ),
      child: Row(
        children: [
          // Icon cảnh báo nếu trùng với user
          if (isDanger) ...[
            const Icon(Icons.warning_rounded, color: AppColors.accent, size: 14),
            const SizedBox(width: 4),
          ],
          Expanded(
            child: Text(
              allergen.name,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: isDanger
                    ? AppColors.accentStrong
                    : (contains ? const Color(0xFF7F1D1D) : AppColors.textMuted),
                fontSize: 12,
                fontWeight: isDanger ? FontWeight.w900 : FontWeight.w800,
                height: 1.2,
              ),
            ),
          ),
          const SizedBox(width: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: contains ? AppColors.accent : const Color(0xFFF5F0E6),
              borderRadius: BorderRadius.circular(4),
            ),
            child: Text(
              contains ? 'CÓ' : 'KHÔNG',
              style: TextStyle(
                color: contains ? Colors.white : AppColors.textMuted,
                fontSize: 9,
                fontWeight: FontWeight.w900,
                letterSpacing: 0.4,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ===========================================================================
// BOTTOM ACTION BAR (qty + add to cart)
// ===========================================================================

class _BottomActionBar extends StatelessWidget {
  const _BottomActionBar({
    required this.quantity,
    required this.unitPriceCents,
    required this.onInc,
    required this.onDec,
    required this.onAddToCart,
  });

  final int quantity;
  final int unitPriceCents;
  final VoidCallback onInc;
  final VoidCallback onDec;
  final VoidCallback onAddToCart;

  @override
  Widget build(BuildContext context) {
    final totalCents = unitPriceCents * quantity;
    return Container(
      padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2D8C8)),
        boxShadow: const [
          BoxShadow(
            color: Colors.black12,
            blurRadius: 8,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Stepper
          Container(
            decoration: BoxDecoration(
              color: const Color(0xFFF5F0E6),
              borderRadius: BorderRadius.circular(999),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
            child: Row(
              children: [
                _StepperBtn(
                  icon: Icons.remove,
                  onTap: quantity > 1 ? onDec : null,
                ),
                SizedBox(
                  width: 28,
                  child: Text(
                    '$quantity',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontSize: 14,
                      fontWeight: FontWeight.w900,
                      fontFeatures: [FontFeature.tabularFigures()],
                    ),
                  ),
                ),
                _StepperBtn(
                  icon: Icons.add,
                  onTap: quantity < 99 ? onInc : null,
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          // Subtotal + CTA
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'TẠM TÍNH',
                  style: TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 9,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 1.0,
                  ),
                ),
                Text(
                  _format(totalCents),
                  style: const TextStyle(
                    color: AppColors.primaryStrong,
                    fontSize: 20,
                    fontWeight: FontWeight.w900,
                    fontFeatures: [FontFeature.tabularFigures()],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Material(
            color: AppColors.accent,
            borderRadius: BorderRadius.circular(14),
            child: InkWell(
              borderRadius: BorderRadius.circular(14),
              onTap: onAddToCart,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.shopping_cart_outlined,
                        color: Colors.white, size: 18),
                    SizedBox(width: 6),
                    Text(
                      'THÊM',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 12,
                        fontWeight: FontWeight.w900,
                        letterSpacing: 0.8,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _format(int cents) {
    final euros = cents / 100;
    return '€ ${euros.toStringAsFixed(2).replaceAll('.', ',')}';
  }
}

class _StepperBtn extends StatelessWidget {
  const _StepperBtn({required this.icon, this.onTap});
  final IconData icon;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final enabled = onTap != null;
    return Material(
      color: AppColors.surface,
      shape: const CircleBorder(),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: SizedBox(
          width: 30,
          height: 30,
          child: Icon(
            icon,
            size: 16,
            color: enabled ? AppColors.primary : AppColors.textMuted,
          ),
        ),
      ),
    );
  }
}

// ===========================================================================
// RELATED DISHES
// ===========================================================================

class _RelatedDishesRow extends StatelessWidget {
  const _RelatedDishesRow({required this.dishes});
  final List<Dish> dishes;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 20, 16, 10),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  Text(
                    'CÓ THỂ BẠN CŨNG THÍCH',
                    style: TextStyle(
                      color: AppColors.accent,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 1.0,
                    ),
                  ),
                  SizedBox(height: 4),
                  Text(
                    'Gợi ý cho bạn',
                    style: TextStyle(
                      color: AppColors.textPrimary,
                      fontSize: 22,
                      fontWeight: FontWeight.w900,
                      letterSpacing: -0.3,
                    ),
                  ),
                ],
              ),
              InkWell(
                onTap: () => context.push(AppRoutes.menu),
                borderRadius: BorderRadius.circular(99),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    border: Border.all(color: AppColors.primary),
                    borderRadius: BorderRadius.circular(99),
                  ),
                  child: const Text(
                    'Xem tất cả',
                    style: TextStyle(
                      color: AppColors.primary,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.6,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
        SizedBox(
          height: 200,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: dishes.length,
            separatorBuilder: (_, __) => const SizedBox(width: 12),
            itemBuilder: (context, i) => _RelatedCard(dish: dishes[i]),
          ),
        ),
      ],
    );
  }
}

class _RelatedCard extends StatelessWidget {
  const _RelatedCard({required this.dish});
  final Dish dish;

  String _formatPrice(int cents) {
    final euros = cents / 100;
    return '€ ${euros.toStringAsFixed(2).replaceAll('.', ',')}';
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(14),
      elevation: 1,
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () =>
            context.push(AppRoutes.dishDetailPath(dish.id)),
        child: SizedBox(
          width: 150,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Ảnh
              ClipRRect(
                borderRadius:
                    const BorderRadius.vertical(top: Radius.circular(14)),
                child: SizedBox(
                  height: 110,
                  width: 150,
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      dish.image != null && dish.image!.isNotEmpty
                          ? Image.network(
                              ImageHelper.url(dish.image) ?? '',
                              fit: BoxFit.cover,
                              errorBuilder: (_, __, ___) =>
                                  const ColoredBox(color: AppColors.primarySoft),
                              loadingBuilder: (_, child, p) =>
                                  p == null ? child : const ColoredBox(color: AppColors.primarySoft),
                            )
                          : const ColoredBox(color: AppColors.primarySoft),
                      if (dish.isFeatured)
                        Positioned(
                          top: 6,
                          left: 6,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: AppColors.primaryStrong,
                              borderRadius: BorderRadius.circular(99),
                            ),
                            child: const Text(
                              'NỔI BẬT',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 8,
                                fontWeight: FontWeight.w900,
                                letterSpacing: 0.5,
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
              // Content
              Padding(
                padding: const EdgeInsets.all(8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      dish.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppColors.textPrimary,
                        fontSize: 12,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 2),
                    if (dish.description != null)
                      Text(
                        dish.description!,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 10,
                          height: 1.3,
                        ),
                      ),
                    const Spacer(),
                    Text(
                      _formatPrice(dish.currentPrice),
                      style: const TextStyle(
                        color: AppColors.primaryStrong,
                        fontSize: 13,
                        fontWeight: FontWeight.w900,
                      ),
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
// LOADING / ERROR STATE
// ===========================================================================

class _LoadingState extends StatelessWidget {
  const _LoadingState();

  @override
  Widget build(BuildContext context) {
    return const Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          SizedBox(
            width: 36,
            height: 36,
            child: CircularProgressIndicator(strokeWidth: 2.5),
          ),
          SizedBox(height: 14),
          Text(
            'Đang tải chi tiết món...',
            style: TextStyle(
              color: AppColors.textMuted,
              fontSize: 13,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, color: Colors.redAccent, size: 48),
          const SizedBox(height: 12),
          const Text(
            'Không tải được chi tiết món',
            style: TextStyle(
              color: AppColors.textPrimary,
              fontSize: 15,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: AppColors.textMuted,
              fontSize: 12,
            ),
          ),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh, size: 16),
            label: const Text('Thử lại'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              padding:
                  const EdgeInsets.symmetric(horizontal: 22, vertical: 12),
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyPanelState extends StatelessWidget {
  const _EmptyPanelState({required this.text});
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 28, horizontal: 16),
      child: Center(
        child: Text(
          text,
          style: const TextStyle(
            color: AppColors.textMuted,
            fontSize: 13,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    );
  }
}

// ===========================================================================
// EXTENSIONS trên DishDetailScreenState để các sub-widget gọi được
// ===========================================================================

extension on _DishDetailScreenState {
  /// Đơn giá (base + sum price_delta của options đã chọn).
  int unitPrice(DishDetail detail) {
    _initDefaults(detail);
    var sum = detail.price;
    for (final group in detail.options) {
      final selected = _selectedOptions[group.id] ?? const <int>{};
      for (final opt in group.options) {
        if (selected.contains(opt.id)) sum += opt.priceDelta;
      }
    }
    return sum < 0 ? 0 : sum;
  }
}
