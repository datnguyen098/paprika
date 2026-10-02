import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/i18n/ui_text.dart';
import '../core/utils/image_helper.dart';
import '../data/models/branch_model.dart';
import '../data/models/dish_model.dart';
import '../data/models/voucher_model.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';
import '../widgets/page_transition_loader.dart';

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key, this.initialQuery = ''});

  final String initialQuery;

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  late final TextEditingController _controller;
  late String _query;

  @override
  void initState() {
    super.initState();
    _query = widget.initialQuery.trim();
    _controller = TextEditingController(text: _query);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final keyword = _query.trim();
    final dishes = ref.watch(searchDishesProvider(keyword));
    final branches = ref.watch(branchesProvider);
    final vouchers = ref.watch(publicVouchersProvider);
    final t = UiText.of(context);

    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(activeRoute: AppRoutes.search),
          Expanded(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _SearchHero(
                    controller: _controller,
                    onChanged: (value) => setState(() => _query = value),
                    onClear: () {
                      _controller.clear();
                      setState(() => _query = '');
                    },
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
                    child: Center(
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(
                          maxWidth: AppConstants.maxContentWidth,
                        ),
                        child: keyword.length < 2
                            ? const _SearchPrompt()
                            : Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  dishes.when(
                                    data: (items) => _DishResults(items: items),
                                    loading: () => _LoadingSection(
                                      title: t.dishes,
                                    ),
                                    error: (error, _) => _ErrorSection(
                                      title: t.dishes,
                                      message: '${t.dishSearchError} $error',
                                      onRetry: () => ref.invalidate(
                                        searchDishesProvider(keyword),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(height: 16),
                                  branches.when(
                                    data: (items) => _BranchResults(
                                      items: _filterBranches(items, keyword),
                                    ),
                                    loading: () => _LoadingSection(
                                      title: t.branches,
                                    ),
                                    error: (error, _) => _ErrorSection(
                                      title: t.branches,
                                      message:
                                          '${t.branchLoadError} $error',
                                      onRetry: () =>
                                          ref.invalidate(branchesProvider),
                                    ),
                                  ),
                                  const SizedBox(height: 16),
                                  vouchers.when(
                                    data: (items) => _VoucherResults(
                                      items: _filterVouchers(items, keyword),
                                    ),
                                    loading: () => _LoadingSection(
                                      title: t.vouchers,
                                    ),
                                    error: (error, _) => _ErrorSection(
                                      title: t.vouchers,
                                      message: '${t.offerLoadError} $error',
                                      onRetry: () => ref.invalidate(
                                        publicVouchersProvider,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                      ),
                    ),
                  ),
                  const PaprikaFooter(),
                ],
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }

  List<Branch> _filterBranches(List<Branch> items, String keyword) {
    final query = keyword.toLowerCase();
    return items.where((branch) {
      return branch.name.toLowerCase().contains(query) ||
          branch.address.toLowerCase().contains(query) ||
          branch.displayHotline.toLowerCase().contains(query);
    }).toList();
  }

  List<PublicVoucher> _filterVouchers(List<PublicVoucher> items, String keyword) {
    final query = keyword.toLowerCase();
    return items.where((voucher) {
      return voucher.code.toLowerCase().contains(query) ||
          voucher.name.toLowerCase().contains(query) ||
          (voucher.description ?? '').toLowerCase().contains(query) ||
          (voucher.branch?.name ?? '').toLowerCase().contains(query);
    }).toList();
  }
}

class _SearchHero extends StatelessWidget {
  const _SearchHero({
    required this.controller,
    required this.onChanged,
    required this.onClear,
  });

  final TextEditingController controller;
  final ValueChanged<String> onChanged;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    final t = UiText.of(context);
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.primaryStrong, AppColors.primary],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      padding: const EdgeInsets.fromLTRB(16, 26, 16, 28),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(
            maxWidth: AppConstants.maxContentWidth,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                t.searchTitle,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 34,
                  height: 1.02,
                  fontWeight: FontWeight.w900,
                  fontStyle: FontStyle.italic,
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: controller,
                onChanged: onChanged,
                textInputAction: TextInputAction.search,
                style: const TextStyle(
                  color: AppColors.textPrimary,
                  fontWeight: FontWeight.w800,
                ),
                decoration: InputDecoration(
                  hintText: t.searchHint,
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: controller.text.isEmpty
                      ? null
                      : IconButton(
                          tooltip: t.close,
                          onPressed: onClear,
                          icon: const Icon(Icons.close),
                        ),
                  filled: true,
                  fillColor: AppColors.surface,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SearchPrompt extends StatelessWidget {
  const _SearchPrompt();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: _panelDecoration(),
      child: Row(
        children: [
          const Icon(Icons.manage_search_outlined, color: AppColors.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              UiText.of(context).searchPrompt,
              style: const TextStyle(
                color: AppColors.textPrimary,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _DishResults extends StatelessWidget {
  const _DishResults({required this.items});

  final List<Dish> items;

  @override
  Widget build(BuildContext context) {
    return _ResultSection(
      title: UiText.of(context).dishes,
      icon: Icons.restaurant_menu_outlined,
      emptyText: UiText.of(context).noMatchingDishes,
      count: items.length,
      children: [
        for (final dish in items.take(8)) _DishResultTile(dish: dish),
      ],
    );
  }
}

class _DishResultTile extends StatelessWidget {
  const _DishResultTile({required this.dish});

  final Dish dish;

  @override
  Widget build(BuildContext context) {
    final imageUrl = ImageHelper.url(dish.image);
    return _ResultTile(
      leading: _Thumb(
        imageUrl: imageUrl,
        icon: Icons.restaurant,
      ),
      title: dish.name,
      subtitle: [
        if (dish.category?.name.isNotEmpty == true) dish.category!.name,
        _formatPrice(dish.currentPrice),
      ].join(' · '),
      trailing: Icons.chevron_right,
      onTap: () {
        context.showPageLoader();
        context.go(AppRoutes.dishDetailPath(dish.id));
      },
    );
  }
}

class _BranchResults extends StatelessWidget {
  const _BranchResults({required this.items});

  final List<Branch> items;

  @override
  Widget build(BuildContext context) {
    return _ResultSection(
      title: UiText.of(context).branches,
      icon: Icons.storefront_outlined,
      emptyText: UiText.of(context).noMatchingBranches,
      count: items.length,
      children: [
        for (final branch in items.take(6)) _BranchResultTile(branch: branch),
      ],
    );
  }
}

class _BranchResultTile extends StatelessWidget {
  const _BranchResultTile({required this.branch});

  final Branch branch;

  @override
  Widget build(BuildContext context) {
    return _ResultTile(
      leading: _Thumb(
        imageUrl: ImageHelper.url(branch.image),
        icon: Icons.storefront,
      ),
      title: branch.name,
      subtitle: branch.address,
      trailing: Icons.chevron_right,
      onTap: () {
        context.showPageLoader();
        context.go(AppRoutes.branchDetailPath(branch.id));
      },
    );
  }
}

class _VoucherResults extends StatelessWidget {
  const _VoucherResults({required this.items});

  final List<PublicVoucher> items;

  @override
  Widget build(BuildContext context) {
    return _ResultSection(
      title: UiText.of(context).vouchers,
      icon: Icons.local_offer_outlined,
      emptyText: UiText.of(context).noMatchingOffers,
      count: items.length,
      children: [
        for (final voucher in items.take(6)) _VoucherResultTile(voucher: voucher),
      ],
    );
  }
}

class _VoucherResultTile extends StatelessWidget {
  const _VoucherResultTile({required this.voucher});

  final PublicVoucher voucher;

  @override
  Widget build(BuildContext context) {
    return _ResultTile(
      leading: const _Thumb(icon: Icons.local_offer),
      title: voucher.code,
      subtitle: '${voucher.name} · ${voucher.valueLabel}',
      action: OutlinedButton.icon(
        onPressed: () async {
          await Clipboard.setData(ClipboardData(text: voucher.code));
          if (!context.mounted) return;
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(UiText.of(context).copiedCode(voucher.code))),
          );
        },
        icon: const Icon(Icons.copy, size: 16),
        label: Text(UiText.of(context).copy),
      ),
      onTap: () {
        context.showPageLoader();
        context.go(
          Uri(
            path: AppRoutes.checkout,
            queryParameters: {'voucher': voucher.code},
          ).toString(),
        );
      },
    );
  }
}

class _ResultSection extends StatelessWidget {
  const _ResultSection({
    required this.title,
    required this.icon,
    required this.emptyText,
    required this.count,
    required this.children,
  });

  final String title;
  final IconData icon;
  final String emptyText;
  final int count;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: _panelDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 8),
            child: Row(
              children: [
                Icon(icon, color: AppColors.primary, size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    title,
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
                Text(
                  '$count',
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
          ),
          if (children.isEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 4, 14, 14),
              child: Text(
                emptyText,
                style: const TextStyle(
                  color: AppColors.textMuted,
                  fontWeight: FontWeight.w700,
                ),
              ),
            )
          else
            ...children,
        ],
      ),
    );
  }
}

class _ResultTile extends StatelessWidget {
  const _ResultTile({
    required this.leading,
    required this.title,
    required this.subtitle,
    this.trailing,
    this.action,
    this.onTap,
  });

  final Widget leading;
  final String title;
  final String subtitle;
  final IconData? trailing;
  final Widget? action;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 8, 14, 12),
        child: Row(
          children: [
            leading,
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
            if (action != null) ...[
              const SizedBox(width: 8),
              action!,
            ] else if (trailing != null)
              Icon(trailing, color: AppColors.textMuted),
          ],
        ),
      ),
    );
  }
}

class _Thumb extends StatelessWidget {
  const _Thumb({this.imageUrl, required this.icon});

  final String? imageUrl;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: Container(
        width: 54,
        height: 54,
        color: AppColors.cream,
        child: imageUrl == null || imageUrl!.isEmpty
            ? Icon(icon, color: AppColors.primary)
            : Image.network(
                imageUrl!,
                fit: BoxFit.cover,
                errorBuilder: (_, __, ___) =>
                    Icon(icon, color: AppColors.primary),
              ),
      ),
    );
  }
}

class _LoadingSection extends StatelessWidget {
  const _LoadingSection({required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: _panelDecoration(),
      child: Row(
        children: [
          const SizedBox(
            width: 18,
            height: 18,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
          const SizedBox(width: 10),
          Text(
            UiText.of(context).searching(title),
            style: const TextStyle(fontWeight: FontWeight.w800),
          ),
        ],
      ),
    );
  }
}

class _ErrorSection extends StatelessWidget {
  const _ErrorSection({
    required this.title,
    required this.message,
    required this.onRetry,
  });

  final String title;
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: _panelDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            message,
            style: const TextStyle(color: AppColors.errorText),
          ),
          const SizedBox(height: 8),
          Align(
            alignment: Alignment.centerLeft,
            child: OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: Text(UiText.of(context).retry),
            ),
          ),
        ],
      ),
    );
  }
}

BoxDecoration _panelDecoration() {
  return BoxDecoration(
    color: AppColors.surface,
    borderRadius: BorderRadius.circular(AppConstants.radius),
    border: Border.all(color: AppColors.border),
  );
}

String _formatPrice(int cents) {
  final euros = cents / 100;
  return '€ ${euros.toStringAsFixed(2).replaceAll('.', ',')}';
}
