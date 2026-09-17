import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../data/models/category_model.dart';
import '../data/models/dish_model.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_header.dart';

/// MenuScreen — Trang thực đơn, lấy dữ liệu từ API.
///
/// Layout:
///   - Sticky header + bottom nav giống các screen khác
///   - Banner tiêu đề "Thực đơn Paprika"
///   - Thanh category chips ngang (horizontal) — chọn 1 để filter
///     - Chip "Tất cả" luôn ở đầu (không filter)
///     - Các chip khác lấy từ [categoriesProvider] (GET /api/v1/categories)
///   - Grid 2 cột hiển thị danh sách món, lấy từ [menuProvider] (GET /api/v1/menu)
///     - Khi chọn 1 category → truyền `categoryId` cho filter
///   - Pull-to-refresh để reload
///   - Empty / loading / error state đều có UI riêng
class MenuScreen extends ConsumerStatefulWidget {
  const MenuScreen({super.key});

  @override
  ConsumerState<MenuScreen> createState() => _MenuScreenState();
}

class _MenuScreenState extends ConsumerState<MenuScreen> {
  /// ID của category đang chọn; null = "Tất cả".
  int? _selectedCategoryId;

  /// Filter hiện tại cho menuProvider.
  MenuFilter _currentFilter() {
    return MenuFilter(
      categoryId: _selectedCategoryId,
      page: 1,
      perPage: 30,
    );
  }

  void _onSelectCategory(int? id) {
    setState(() => _selectedCategoryId = id);
  }

  Future<void> _onRefresh() async {
    ref.invalidate(categoriesProvider);
    ref.invalidate(menuProvider(_currentFilter()));
    // Đợi Riverpod recompute xong.
    await ref.read(menuProvider(_currentFilter()).future);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(cartItemsCount: 0),
          Expanded(
            child: RefreshIndicator(
              onRefresh: _onRefresh,
              color: AppColors.primary,
              child: CustomScrollView(
                slivers: [
                  const SliverToBoxAdapter(child: _MenuBanner()),
                  SliverToBoxAdapter(
                    child: _CategoryChipsBar(
                      selectedId: _selectedCategoryId,
                      onSelect: _onSelectCategory,
                    ),
                  ),
                  const SliverToBoxAdapter(child: SizedBox(height: 12)),
                  _DishGrid(filter: _currentFilter()),
                  const SliverToBoxAdapter(child: SizedBox(height: 24)),
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
// BANNER
// ===========================================================================

class _MenuBanner extends StatelessWidget {
  const _MenuBanner();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.all(AppConstants.spaceMd),
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
            color: AppColors.primary.withValues(alpha: 0.3),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: AppColors.accent.withValues(alpha: 0.25),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.restaurant_menu,
                  color: Colors.white,
                  size: 22,
                ),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Text(
                  'Thực đơn Paprika',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -0.3,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            'Phở ninh chậm, nem nướng trui than, gyros pita chuẩn vị Athens — '
            'chọn món bạn yêu thích.',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.85),
              fontSize: 13,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }
}

// ===========================================================================
// CATEGORY CHIPS BAR
// ===========================================================================

class _CategoryChipsBar extends ConsumerWidget {
  const _CategoryChipsBar({
    required this.selectedId,
    required this.onSelect,
  });

  final int? selectedId;
  final void Function(int? id) onSelect;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final categoriesAsync = ref.watch(categoriesProvider);

    return categoriesAsync.when(
      loading: () => const SizedBox(
        height: 44,
        child: Center(
          child: SizedBox(
            width: 22,
            height: 22,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
        ),
      ),
      error: (err, _) => SizedBox(
        height: 44,
        child: Center(
          child: Text(
            'Lỗi tải danh mục',
            style: TextStyle(
              color: AppColors.textMuted,
              fontSize: 12,
            ),
          ),
        ),
      ),
      data: (categories) => _buildChipsList(categories),
    );
  }

  Widget _buildChipsList(List<Category> categories) {
    return SizedBox(
      height: 44,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: AppConstants.spaceMd),
        children: [
          _Chip(
            label: 'Tất cả',
            isSelected: selectedId == null,
            onTap: () => onSelect(null),
          ),
          const SizedBox(width: 8),
          for (final c in categories) ...[
            _Chip(
              label: c.name,
              isSelected: selectedId == c.id,
              onTap: () => onSelect(c.id),
            ),
            const SizedBox(width: 8),
          ],
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: isSelected ? AppColors.primary : AppColors.surface,
      borderRadius: BorderRadius.circular(999),
      elevation: isSelected ? 2 : 0,
      child: InkWell(
        borderRadius: BorderRadius.circular(999),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(
            horizontal: AppConstants.spaceMd,
            vertical: 10,
          ),
          alignment: Alignment.center,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(999),
            border: Border.all(
              color: isSelected ? AppColors.primary : AppColors.border,
              width: 1,
            ),
          ),
          child: Text(
            label,
            style: TextStyle(
              color: isSelected ? Colors.white : AppColors.textPrimary,
              fontSize: 12,
              fontWeight: FontWeight.w800,
              letterSpacing: 0.1,
            ),
          ),
        ),
      ),
    );
  }
}

// ===========================================================================
// DISH GRID
// ===========================================================================

class _DishGrid extends ConsumerWidget {
  const _DishGrid({required this.filter});

  final MenuFilter filter;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final menuAsync = ref.watch(menuProvider(filter));

    return menuAsync.when(
      loading: () => const SliverToBoxAdapter(
        child: Padding(
          padding: EdgeInsets.symmetric(vertical: 60),
          child: Center(child: CircularProgressIndicator()),
        ),
      ),
      error: (err, _) => SliverToBoxAdapter(
        child: _ErrorState(
          message: '$err',
          onRetry: () => ref.invalidate(menuProvider(filter)),
        ),
      ),
      data: (paged) {
        if (paged.items.isEmpty) {
          return const SliverToBoxAdapter(child: _EmptyState());
        }
        return SliverPadding(
          padding: const EdgeInsets.symmetric(
            horizontal: AppConstants.spaceMd,
          ),
          sliver: SliverGrid(
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              crossAxisSpacing: AppConstants.spaceSm,
              mainAxisSpacing: AppConstants.spaceSm,
              childAspectRatio: 0.72,
            ),
            delegate: SliverChildBuilderDelegate(
              (context, index) => _DishCard(dish: paged.items[index]),
              childCount: paged.items.length,
            ),
          ),
        );
      },
    );
  }
}

class _DishCard extends StatelessWidget {
  const _DishCard({required this.dish});

  final Dish dish;

  IconData _iconForCategory(String? slug) {
    switch (slug) {
      case 'pho':
      case 'bun':
      case 'mi':
        return Icons.ramen_dining;
      case 'nem':
      case 'grill':
      case 'nuong':
        return Icons.local_fire_department;
      case 'banh-mi':
      case 'bread':
        return Icons.bakery_dining;
      case 'gyros':
      case 'pita':
        return Icons.kebab_dining;
      default:
        return Icons.restaurant_menu;
    }
  }

  @override
  Widget build(BuildContext context) {
    final hasDiscount = dish.hasDiscount;
    final finalPrice = dish.currentPrice;

    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(AppConstants.radius),
      elevation: 1,
      shadowColor: Colors.black.withValues(alpha: 0.06),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppConstants.radius),
        onTap: () => context.push(AppRoutes.dishDetailPath(dish.id)),
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
                    child: ClipRRect(
                      borderRadius:
                          BorderRadius.circular(AppConstants.radiusSm),
                      child: dish.image != null && dish.image!.isNotEmpty
                          ? Image.network(
                              dish.image!,
                              fit: BoxFit.cover,
                              errorBuilder: (_, __, ___) =>
                                  _placeholder(slug: dish.category?.slug),
                              loadingBuilder: (context, child, p) {
                                if (p == null) return child;
                                return _placeholder(
                                    slug: dish.category?.slug,
                                    isLoading: true);
                              },
                            )
                          : _placeholder(slug: dish.category?.slug),
                    ),
                  ),
                  if (dish.isFeatured)
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
                        child: const Text(
                          'NỔI BẬT',
                          style: TextStyle(
                            color: AppColors.gold,
                            fontSize: 9,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 0.12,
                          ),
                        ),
                      ),
                    ),
                  if (!dish.isAvailable)
                    Positioned.fill(
                      child: Container(
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.45),
                          borderRadius:
                              BorderRadius.circular(AppConstants.radiusSm),
                        ),
                        alignment: Alignment.center,
                        child: const Text(
                          'HẾT MÓN',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.w900,
                            letterSpacing: 0.14,
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
                dish.description ?? '',
                style: const TextStyle(
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
                  Flexible(
                    child: Text(
                      _formatPrice(finalPrice),
                      style: TextStyle(
                        color: AppColors.accentStrong,
                        fontSize: 14,
                        fontWeight: FontWeight.w900,
                        decoration: hasDiscount
                            ? TextDecoration.lineThrough
                            : null,
                        decorationColor: AppColors.textMuted,
                      ),
                    ),
                  ),
                  if (hasDiscount) ...[
                    const SizedBox(width: 4),
                    Flexible(
                      child: Text(
                        _formatPrice(dish.price),
                        style: const TextStyle(
                          color: AppColors.accentStrong,
                          fontSize: 14,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                  ],
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

  Widget _placeholder({String? slug, bool isLoading = false}) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [AppColors.accentSoft, AppColors.warm],
        ),
      ),
      alignment: Alignment.center,
      child: isLoading
          ? const SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(strokeWidth: 2),
            )
          : Icon(
              _iconForCategory(slug),
              size: 40,
              color: AppColors.accentStrong,
            ),
    );
  }

  /// API trả price là cents (int). BE DishController dùng VND-style int.
  /// Chia 100 để hiển thị euro. Nếu BE đã là số thập phân thì không cần đổi.
  String _formatPrice(int cents) {
    final euros = cents / 100;
    return '€ ${euros.toStringAsFixed(2)}';
  }
}

// ===========================================================================
// EMPTY / ERROR STATE
// ===========================================================================

class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(
        vertical: 60,
        horizontal: AppConstants.spaceLg,
      ),
      child: Column(
        children: [
          Icon(
            Icons.restaurant_menu,
            size: 48,
            color: AppColors.textMuted.withValues(alpha: 0.5),
          ),
          const SizedBox(height: 12),
          Text(
            'Chưa có món nào trong danh mục này',
            style: TextStyle(
              color: AppColors.textMuted,
              fontSize: 13,
              fontWeight: FontWeight.w700,
            ),
            textAlign: TextAlign.center,
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
      padding: const EdgeInsets.symmetric(
        vertical: 60,
        horizontal: AppConstants.spaceLg,
      ),
      child: Column(
        children: [
          const Icon(
            Icons.error_outline,
            color: Colors.redAccent,
            size: 40,
          ),
          const SizedBox(height: 10),
          Text(
            'Không tải được thực đơn',
            style: TextStyle(
              color: AppColors.textPrimary,
              fontSize: 14,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            message,
            style: TextStyle(
              color: AppColors.textMuted,
              fontSize: 11,
            ),
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 12),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh, size: 16),
            label: const Text('Thử lại'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(
                horizontal: 20,
                vertical: 10,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
