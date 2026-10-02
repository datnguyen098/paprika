import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/i18n/ui_text.dart';
import '../data/models/order_model.dart';
import '../providers/providers.dart';
import '../services/api_service.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';
import '../widgets/page_transition_loader.dart';

class OrdersScreen extends ConsumerStatefulWidget {
  const OrdersScreen({super.key});

  @override
  ConsumerState<OrdersScreen> createState() => _OrdersScreenState();
}

class _OrdersScreenState extends ConsumerState<OrdersScreen> {
  final _queryCtrl = TextEditingController();
  bool _isLoading = false;
  String? _error;
  List<OrderResponse>? _orders;

  @override
  void dispose() {
    _queryCtrl.dispose();
    super.dispose();
  }

  Future<void> _lookup() async {
    FocusScope.of(context).unfocus();
    final query = _queryCtrl.text.trim();
    if (query.isEmpty) {
      setState(() => _error = UiText.of(context).orderLookupRequired);
      return;
    }

    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final orders = await ref.read(orderRepositoryProvider).lookupOrders(query);
      if (!mounted) return;
      setState(() => _orders = orders);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final orders = _orders;
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: GestureDetector(
        behavior: HitTestBehavior.translucent,
        onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
        child: Column(
          children: [
            const PaprikaHeader(activeRoute: AppRoutes.orders),
            Expanded(
              child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const _OrdersHero(),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
                    child: Center(
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(
                          maxWidth: AppConstants.maxContentWidth,
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            _LookupCard(
                              controller: _queryCtrl,
                              isLoading: _isLoading,
                              error: _error,
                              onSubmit: _lookup,
                            ),
                            const SizedBox(height: 14),
                            if (orders == null)
                              const _EmptyHint()
                            else if (orders.isEmpty)
                              const _NoOrders()
                            else
                              for (final order in orders) ...[
                                _OrderCard(order: order),
                                const SizedBox(height: 10),
                              ],
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
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

class _OrdersHero extends StatelessWidget {
  const _OrdersHero();

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.primaryStrong, AppColors.primary],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      padding: const EdgeInsets.fromLTRB(16, 28, 16, 30),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: AppConstants.maxContentWidth),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                UiText.of(context).orderLookupTitle,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 32,
                  height: 1.05,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                UiText.of(context).orderLookupSubtitle,
                style: const TextStyle(
                  color: Color(0xFFD6E5DB),
                  fontSize: 14,
                  height: 1.45,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _LookupCard extends StatelessWidget {
  const _LookupCard({
    required this.controller,
    required this.isLoading,
    required this.onSubmit,
    this.error,
  });

  final TextEditingController controller;
  final bool isLoading;
  final String? error;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppConstants.radius),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TextField(
            controller: controller,
            textInputAction: TextInputAction.search,
            onSubmitted: (_) => onSubmit(),
            decoration: InputDecoration(
              hintText: UiText.of(context).orderLookupHint,
              errorText: error,
              prefixIcon: const Icon(Icons.search, color: AppColors.primary),
            ),
          ),
          const SizedBox(height: 12),
          FilledButton.icon(
            onPressed: isLoading ? null : onSubmit,
            icon: isLoading
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.manage_search),
            label: Text(isLoading
                ? UiText.of(context).orderLookupLoading
                : UiText.of(context).orderLookupSubmit),
          ),
        ],
      ),
    );
  }
}

class _OrderCard extends StatelessWidget {
  const _OrderCard({required this.order});

  final OrderResponse order;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(AppConstants.radius),
      onTap: () {
        context.showPageLoader();
        context.go(AppRoutes.orderDetailPath(order.code));
      },
      child: Container(
        padding: const EdgeInsets.all(14),
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
                Expanded(
                  child: Text(
                    order.code,
                    style: const TextStyle(
                      color: AppColors.primaryStrong,
                      fontSize: 16,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
                _StatusBadge(label: order.statusLabel),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              '${order.fulfillmentLabel} · ${_formatPrice(order.total)}',
              style: const TextStyle(
                color: AppColors.textPrimary,
                fontWeight: FontWeight.w800,
              ),
            ),
            if (order.branch != null) ...[
              const SizedBox(height: 4),
              Text(
                order.branch!.name,
                style: const TextStyle(color: AppColors.textMuted),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.successBg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        child: Text(
          label,
          style: const TextStyle(
            color: AppColors.primary,
            fontSize: 11,
            fontWeight: FontWeight.w900,
          ),
        ),
      ),
    );
  }
}

class _EmptyHint extends StatelessWidget {
  const _EmptyHint();

  @override
  Widget build(BuildContext context) {
    return Text(
      UiText.of(context).orderLookupHintText,
      textAlign: TextAlign.center,
      style: const TextStyle(color: AppColors.textMuted, fontWeight: FontWeight.w700),
    );
  }
}

class _NoOrders extends StatelessWidget {
  const _NoOrders();

  @override
  Widget build(BuildContext context) {
    return Text(
      UiText.of(context).noOrdersFound,
      textAlign: TextAlign.center,
      style: const TextStyle(color: AppColors.textMuted, fontWeight: FontWeight.w800),
    );
  }
}

String _formatPrice(int cents) {
  final euros = cents / 100;
  return '€ ${euros.toStringAsFixed(2).replaceAll('.', ',')}';
}
