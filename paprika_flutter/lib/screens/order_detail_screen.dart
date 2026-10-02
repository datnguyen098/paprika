import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/i18n/ui_text.dart';
import '../data/models/order_model.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';
import '../widgets/page_transition_loader.dart';

class OrderDetailScreen extends ConsumerWidget {
  const OrderDetailScreen({
    super.key,
    required this.code,
    this.trackingOnly = false,
  });

  final String code;
  final bool trackingOnly;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final future = trackingOnly
        ? ref.read(orderRepositoryProvider).trackOrder(code)
        : ref.read(orderRepositoryProvider).getOrder(code);

    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(activeRoute: AppRoutes.orders),
          Expanded(
            child: FutureBuilder<OrderResponse>(
              future: future,
              builder: (context, snapshot) {
                if (snapshot.connectionState != ConnectionState.done) {
                  return const Center(child: CircularProgressIndicator());
                }
                if (snapshot.hasError || !snapshot.hasData) {
                  return _ErrorState(
                    message: snapshot.error?.toString() ??
                        UiText.of(context).orderDetailLoadError,
                  );
                }
                final order = snapshot.data!;
                return SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _DetailHero(order: order),
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
                                _TrackingCard(order: order),
                                if (!trackingOnly) ...[
                                  const SizedBox(height: 12),
                                  _ItemsCard(order: order),
                                  const SizedBox(height: 12),
                                  _SummaryCard(order: order),
                                ],
                              ],
                            ),
                          ),
                        ),
                      ),
                      const PaprikaFooter(),
                    ],
                  ),
                );
              },
            ),
          ),
        ],
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

class _DetailHero extends StatelessWidget {
  const _DetailHero({required this.order});

  final OrderResponse order;

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
                order.code,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 30,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                '${order.statusLabel} · ${order.fulfillmentLabel}',
                style: const TextStyle(
                  color: Color(0xFFD6E5DB),
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 14),
              OutlinedButton.icon(
                onPressed: () {
                  context.showPageLoader();
                  context.go(AppRoutes.orders);
                },
                icon: const Icon(Icons.arrow_back),
                label: Text(UiText.of(context).lookupAnotherOrder),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _TrackingCard extends StatelessWidget {
  const _TrackingCard({required this.order});

  final OrderResponse order;

  @override
  Widget build(BuildContext context) {
    return _Panel(
      title: UiText.of(context).orderTracking,
      child: Column(
        children: [
          for (final step in order.timeline) _TimelineRow(step: step),
        ],
      ),
    );
  }
}

class _TimelineRow extends StatelessWidget {
  const _TimelineRow({required this.step});

  final OrderTimelineStep step;

  @override
  Widget build(BuildContext context) {
    final color = step.completed || step.current
        ? AppColors.primary
        : AppColors.textMuted;
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Icon(
            step.current ? Icons.radio_button_checked : Icons.check_circle,
            color: color,
            size: 22,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              step.label,
              style: TextStyle(
                color: color,
                fontWeight: step.current ? FontWeight.w900 : FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ItemsCard extends StatelessWidget {
  const _ItemsCard({required this.order});

  final OrderResponse order;

  @override
  Widget build(BuildContext context) {
    return _Panel(
      title: UiText.of(context).orderedItems,
      child: Column(
        children: [
          for (final item in order.items) ...[
            Row(
              children: [
                Expanded(
                  child: Text(
                    '${item.quantity}x ${item.dishName}',
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                Text(
                  _formatPrice(item.lineTotal),
                  style: const TextStyle(
                    color: AppColors.primaryStrong,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
            const Divider(color: AppColors.border),
          ],
        ],
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({required this.order});

  final OrderResponse order;

  @override
  Widget build(BuildContext context) {
    return _Panel(
      title: UiText.of(context).orderTotal,
      child: Column(
        children: [
          _AmountLine(label: UiText.of(context).subtotal, value: order.subtotal),
          _AmountLine(label: UiText.of(context).shippingFee, value: order.shippingFee),
          _AmountLine(label: UiText.of(context).discount, value: -order.discountTotal),
          const Divider(color: AppColors.border),
          _AmountLine(label: UiText.of(context).grandTotal, value: order.total, strong: true),
          if (order.branch != null) ...[
            const SizedBox(height: 10),
            Text(
              order.branch!.name,
              style: const TextStyle(
                color: AppColors.textMuted,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _AmountLine extends StatelessWidget {
  const _AmountLine({
    required this.label,
    required this.value,
    this.strong = false,
  });

  final String label;
  final int value;
  final bool strong;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Text(
            label,
            style: TextStyle(
              color: strong ? AppColors.textPrimary : AppColors.textMuted,
              fontWeight: strong ? FontWeight.w900 : FontWeight.w700,
            ),
          ),
          const Spacer(),
          Text(
            _formatPrice(value),
            style: TextStyle(
              color: strong ? AppColors.primaryStrong : AppColors.textPrimary,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _Panel extends StatelessWidget {
  const _Panel({
    required this.title,
    required this.child,
  });

  final String title;
  final Widget child;

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
          Text(
            title,
            style: const TextStyle(
              color: AppColors.primaryStrong,
              fontSize: 16,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Text(
          message,
          textAlign: TextAlign.center,
          style: const TextStyle(
            color: AppColors.textMuted,
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
    );
  }
}

String _formatPrice(int cents) {
  final euros = cents / 100;
  return '€ ${euros.toStringAsFixed(2).replaceAll('.', ',')}';
}
