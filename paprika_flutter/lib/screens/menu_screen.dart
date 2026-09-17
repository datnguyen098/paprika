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

  /// Controller cho ô tìm kiếm.
  final TextEditingController _searchController = TextEditingController();

  /// Từ khoá tìm kiếm hiện tại.
  String _searchQuery = '';

  /// Trang hiện tại + tổng số trang (cập nhật từ API).
  int _currentPage = 1;
  int _lastPage = 1;

  /// Filter hiện tại cho menuProvider.
  MenuFilter _currentFilter() {
    return MenuFilter(
      categoryId: _selectedCategoryId,
      search: _searchQuery.isEmpty ? null : _searchQuery,
      page: _currentPage,
      perPage: 12,
    );
  }

  void _onSelectCategory(int? id) {
    setState(() {
      _selectedCategoryId = id;
      _currentPage = 1; // reset về trang 1 khi đổi category
    });
  }

  void _onSearchChanged(String value) {
    setState(() {
      _searchQuery = value.trim();
      _currentPage = 1; // reset về trang 1 khi tìm kiếm
    });
  }

  void _onClearSearch() {
    _searchController.clear();
    setState(() {
      _searchQuery = '';
      _currentPage = 1;
    });
  }

  void _onPageChanged(int page) {
    if (page < 1 || page > _lastPage) return;
    setState(() => _currentPage = page);
    // Scroll lên đầu danh sách mỗi khi đổi trang.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        Scrollable.ensureVisible(
          context,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  void _updatePagination(int current, int last) {
    if (_currentPage == current && _lastPage == last) return;
    setState(() {
      _currentPage = current;
      _lastPage = last;
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _onRefresh() async {
    // Reset pagination về trang 1 khi refresh.
    setState(() => _currentPage = 1);
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
                  SliverToBoxAdapter(
                    child: _SearchBar(
                      controller: _searchController,
                      onChanged: _onSearchChanged,
                      onClear: _onClearSearch,
                    ),
                  ),
                  const SliverToBoxAdapter(child: SizedBox(height: 12)),
                  _DishGrid(
                    filter: _currentFilter(),
                    onPaginationChanged: _updatePagination,
                  ),
                  SliverToBoxAdapter(
                    child: _PaginationBar(
                      currentPage: _currentPage,
                      lastPage: _lastPage,
                      onPageChanged: _onPageChanged,
                    ),
                  ),
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

class _DishGrid extends ConsumerStatefulWidget {
  const _DishGrid({
    required this.filter,
    required this.onPaginationChanged,
  });

  final MenuFilter filter;
  final void Function(int currentPage, int lastPage) onPaginationChanged;

  @override
  ConsumerState<_DishGrid> createState() => _DishGridState();
}

class _DishGridState extends ConsumerState<_DishGrid> {
  int _reportedPage = -1;
  int _reportedLast = -1;

  @override
  Widget build(BuildContext context) {
    final menuAsync = ref.watch(menuProvider(widget.filter));

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
          onRetry: () => ref.invalidate(menuProvider(widget.filter)),
        ),
      ),
      data: (paged) {
        // Báo cho parent biết current/last page (chỉ khi đổi).
        final cp = paged.meta.currentPage;
        final lp = paged.meta.lastPage;
        if (cp != _reportedPage || lp != _reportedLast) {
          _reportedPage = cp;
          _reportedLast = lp;
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (mounted) widget.onPaginationChanged(cp, lp);
          });
        }

        if (paged.items.isEmpty) {
          return const SliverToBoxAdapter(child: _EmptyState());
        }
        return SliverPadding(
          padding: const EdgeInsets.symmetric(
            horizontal: AppConstants.spaceMd,
          ),
          sliver: SliverList.separated(
            itemCount: paged.items.length,
            separatorBuilder: (_, __) =>
                const SizedBox(height: AppConstants.spaceSm),
            itemBuilder: (context, index) =>
                _DishCard(dish: paged.items[index]),
          ),
        );
      },
    );
  }
}

// ===========================================================================
// SEARCH BAR
// ===========================================================================

class _SearchBar extends StatelessWidget {
  const _SearchBar({
    required this.controller,
    required this.onChanged,
    required this.onClear,
  });

  final TextEditingController controller;
  final ValueChanged<String> onChanged;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(
        AppConstants.spaceMd,
        AppConstants.spaceMd,
        AppConstants.spaceMd,
        0,
      ),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppConstants.radius),
          border: Border.all(color: const Color(0xFFE7E5E4)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 4,
              offset: const Offset(0, 1),
            ),
          ],
        ),
        child: TextField(
          controller: controller,
          onChanged: onChanged,
          textInputAction: TextInputAction.search,
          style: const TextStyle(
            fontSize: 14,
            color: AppColors.textPrimary,
            fontWeight: FontWeight.w600,
          ),
          decoration: InputDecoration(
            hintText: 'Tìm món...',
            hintStyle: const TextStyle(
              color: AppColors.textMuted,
              fontSize: 13,
              fontWeight: FontWeight.w500,
            ),
            prefixIcon: const Icon(
              Icons.search,
              size: 20,
              color: AppColors.textMuted,
            ),
            suffixIcon: ValueListenableBuilder<TextEditingValue>(
              valueListenable: controller,
              builder: (context, value, _) {
                if (value.text.isEmpty) return const SizedBox.shrink();
                return IconButton(
                  onPressed: onClear,
                  icon: const Icon(
                    Icons.close,
                    size: 18,
                    color: AppColors.textMuted,
                  ),
                  tooltip: 'Xoá',
                );
              },
            ),
            border: InputBorder.none,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 12,
              vertical: 12,
            ),
          ),
        ),
      ),
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

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(AppConstants.radius),
        border: Border.all(color: const Color(0xFFF5F5F4)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 4,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(AppConstants.radius),
          onTap: () => context.push(AppRoutes.dishDetailPath(dish.id)),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                // Ảnh trên
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: SizedBox(
                    height: 140,
                    width: double.infinity,
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        dish.image != null && dish.image!.isNotEmpty
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
                        if (dish.isFeatured)
                          Positioned(
                            top: 8,
                            left: 8,
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 3,
                              ),
                              decoration: BoxDecoration(
                                color: AppColors.primaryStrong,
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: const Text(
                                'NỔI BẬT',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 9,
                                  fontWeight: FontWeight.w900,
                                  letterSpacing: 0.1,
                                ),
                              ),
                            ),
                          ),
                        if (!dish.isAvailable)
                          Positioned.fill(
                            child: Container(
                              color: Colors.black.withValues(alpha: 0.45),
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
                  ),
                ),
                const SizedBox(height: 12),
                // Tên món
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
                // Mô tả
                Text(
                  dish.description ?? '',
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 11,
                    height: 1.4,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 12),
                // Giá + nút +
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          if (hasDiscount)
                            Text(
                              _formatPrice(dish.price),
                              style: const TextStyle(
                                color: AppColors.textMuted,
                                fontSize: 11,
                                decoration: TextDecoration.lineThrough,
                                decorationColor: AppColors.textMuted,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          Text(
                            _formatPrice(finalPrice),
                            style: const TextStyle(
                              color: AppColors.primaryStrong,
                              fontSize: 18,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ],
                      ),
                    ),
                    InkWell(
                      onTap: () => context.push(AppRoutes.dishDetailPath(dish.id)),
                      borderRadius: BorderRadius.circular(999),
                      child: Container(
                        width: 36,
                        height: 36,
                        decoration: const BoxDecoration(
                          color: AppColors.primaryStrong,
                          shape: BoxShape.circle,
                        ),
                        alignment: Alignment.center,
                        child: const Icon(
                          Icons.add,
                          color: Colors.white,
                          size: 22,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
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

// ===========================================================================
// PAGINATION BAR
// ===========================================================================

class _PaginationBar extends StatelessWidget {
  const _PaginationBar({
    required this.currentPage,
    required this.lastPage,
    required this.onPageChanged,
  });

  final int currentPage;
  final int lastPage;
  final ValueChanged<int> onPageChanged;

  List<int> _buildPageList() {
    if (lastPage <= 1) return const [];
    final pages = <int>{};
    // Luôn hiển thị trang đầu, cuối, và quanh trang hiện tại.
    pages.add(1);
    pages.add(lastPage);
    for (var i = currentPage - 1; i <= currentPage + 1; i++) {
      if (i >= 1 && i <= lastPage) pages.add(i);
    }
    final list = pages.toList()..sort();
    return list;
  }

  @override
  Widget build(BuildContext context) {
    if (lastPage <= 1) return const SizedBox.shrink();

    final pageList = _buildPageList();
    final hasPrev = currentPage > 1;
    final hasNext = currentPage < lastPage;

    return Padding(
      padding: const EdgeInsets.fromLTRB(
        AppConstants.spaceMd,
        AppConstants.spaceLg,
        AppConstants.spaceMd,
        0,
      ),
      child: Container(
        padding: const EdgeInsets.symmetric(
          horizontal: 8,
          vertical: 10,
        ),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(AppConstants.radius),
          border: Border.all(color: const Color(0xFFE7E5E4)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 4,
              offset: const Offset(0, 1),
            ),
          ],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Nút Previous
            _PaginationButton(
              icon: Icons.chevron_left,
              enabled: hasPrev,
              onTap: () => onPageChanged(currentPage - 1),
              tooltip: 'Trang trước',
            ),
            const SizedBox(width: 4),
            // Các trang
            ..._buildPageWidgets(pageList),
            const SizedBox(width: 4),
            // Nút Next
            _PaginationButton(
              icon: Icons.chevron_right,
              enabled: hasNext,
              onTap: () => onPageChanged(currentPage + 1),
              tooltip: 'Trang sau',
            ),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildPageWidgets(List<int> pages) {
    final widgets = <Widget>[];
    for (var i = 0; i < pages.length; i++) {
      final page = pages[i];
      // Thêm dấu "..." nếu trang trước không liền kề.
      if (i > 0 && pages[i - 1] != page - 1) {
        widgets.add(const Padding(
          padding: EdgeInsets.symmetric(horizontal: 4),
          child: Text(
            '...',
            style: TextStyle(
              color: AppColors.textMuted,
              fontSize: 13,
              fontWeight: FontWeight.w800,
            ),
          ),
        ));
      }
      widgets.add(_PageNumber(
        page: page,
        isSelected: page == currentPage,
        onTap: () => onPageChanged(page),
      ));
    }
    return widgets;
  }
}

class _PageNumber extends StatelessWidget {
  const _PageNumber({
    required this.page,
    required this.isSelected,
    required this.onTap,
  });

  final int page;
  final bool isSelected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 2),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Container(
          width: 34,
          height: 34,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: isSelected ? AppColors.primaryStrong : Colors.transparent,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            '$page',
            style: TextStyle(
              color: isSelected ? Colors.white : AppColors.textPrimary,
              fontSize: 13,
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
      ),
    );
  }
}

class _PaginationButton extends StatelessWidget {
  const _PaginationButton({
    required this.icon,
    required this.enabled,
    required this.onTap,
    required this.tooltip,
  });

  final IconData icon;
  final bool enabled;
  final VoidCallback onTap;
  final String tooltip;

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip,
      child: InkWell(
        onTap: enabled ? onTap : null,
        borderRadius: BorderRadius.circular(8),
        child: Container(
          width: 34,
          height: 34,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: AppColors.primaryStrong.withValues(alpha: enabled ? 1 : 0.3),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(
            icon,
            size: 18,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}
