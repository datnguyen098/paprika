import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/utils/image_helper.dart';
import '../data/models/cart_model.dart';
import '../l10n/generated/app_localizations.dart';
import '../providers/providers.dart';
import 'page_transition_loader.dart';

Future<void> showCartDrawer(BuildContext context) {
  FocusManager.instance.primaryFocus?.unfocus();

  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    backgroundColor: Colors.transparent,
    builder: (_) => const CartDrawer(),
  );
}

class CartDrawer extends ConsumerWidget {
  const CartDrawer({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cart = ref.watch(cartProvider);
    final height = MediaQuery.sizeOf(context).height;

    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
      child: Align(
        alignment: Alignment.bottomCenter,
        child: ConstrainedBox(
          constraints: BoxConstraints(maxHeight: height * 0.9),
          child: Material(
            color: AppColors.cream,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            clipBehavior: Clip.antiAlias,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const _DrawerHandle(),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 4, 10, 12),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.shopping_bag_outlined,
                        color: AppColors.primary,
                        size: 22,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Giỏ hàng (${cart.count})',
                          style: const TextStyle(
                            color: AppColors.textPrimary,
                            fontSize: 20,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                      ),
                      IconButton(
                        tooltip: AppLocalizations.of(context).commonClose,
                        onPressed: () => Navigator.of(context).pop(),
                        icon: const Icon(
                          Icons.close,
                          color: AppColors.textMuted,
                        ),
                      ),
                    ],
                  ),
                ),
                Flexible(
                  child: cart.isEmpty
                      ? const _EmptyCart()
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                          shrinkWrap: true,
                          keyboardDismissBehavior:
                              ScrollViewKeyboardDismissBehavior.onDrag,
                          itemCount: cart.items.length,
                          separatorBuilder: (_, __) =>
                              const SizedBox(height: 10),
                          itemBuilder: (context, index) {
                            return CartLineTile(item: cart.items[index]);
                          },
                        ),
                ),
                if (!cart.isEmpty) _CartDrawerFooter(cart: cart),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _DrawerHandle extends StatelessWidget {
  const _DrawerHandle();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Container(
        width: 46,
        height: 5,
        margin: const EdgeInsets.only(top: 10, bottom: 10),
        decoration: BoxDecoration(
          color: AppColors.border,
          borderRadius: BorderRadius.circular(999),
        ),
      ),
    );
  }
}

class _EmptyCart extends StatelessWidget {
  const _EmptyCart();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(24, 24, 24, 34),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 72,
            height: 72,
            decoration: const BoxDecoration(
              color: AppColors.surface,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.shopping_bag_outlined,
              color: AppColors.primary,
              size: 34,
            ),
          ),
          const SizedBox(height: 14),
          const Text(
            'Giỏ hàng đang trống',
            style: TextStyle(
              color: AppColors.textPrimary,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Chọn món trong thực đơn để bắt đầu đặt hàng.',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: AppColors.textMuted,
              fontSize: 13,
              height: 1.45,
            ),
          ),
          const SizedBox(height: 18),
          FilledButton.icon(
            onPressed: () {
              Navigator.of(context).pop();
              context.showPageLoader();
              context.go(AppRoutes.menu);
            },
            style: FilledButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(999),
              ),
            ),
            icon: const Icon(Icons.restaurant_menu, size: 18),
            label: const Text(
              'Xem thực đơn',
              style: TextStyle(fontWeight: FontWeight.w900),
            ),
          ),
        ],
      ),
    );
  }
}

class CartLineTile extends ConsumerWidget {
  const CartLineTile({
    super.key,
    required this.item,
    this.compact = false,
  });

  final CartItem item;
  final bool compact;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final imageUrl = ImageHelper.variant(item.image, size: 'thumb');
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      padding: const EdgeInsets.all(10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: SizedBox(
              width: compact ? 64 : 74,
              height: compact ? 64 : 74,
              child: imageUrl == null
                  ? Image.asset(AppConstants.placeholderDish, fit: BoxFit.cover)
                  : Image.network(
                      imageUrl,
                      fit: BoxFit.cover,
                      errorBuilder: (_, __, ___) => Image.asset(
                        AppConstants.placeholderDish,
                        fit: BoxFit.cover,
                      ),
                    ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Text(
                        item.name,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.textPrimary,
                          fontSize: 14,
                          fontWeight: FontWeight.w900,
                          height: 1.18,
                        ),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      _formatPrice(item.lineTotal),
                      style: const TextStyle(
                        color: AppColors.primaryStrong,
                        fontSize: 14,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 5),
                Text(
                  item.summary,
                  maxLines: compact ? 1 : 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 11,
                    height: 1.35,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    _QuantityPill(
                      quantity: item.quantity,
                      onDec: () => ref
                          .read(cartProvider.notifier)
                          .updateQuantity(item.lineKey, item.quantity - 1),
                      onInc: () => ref
                          .read(cartProvider.notifier)
                          .updateQuantity(item.lineKey, item.quantity + 1),
                    ),
                    const Spacer(),
                    Text(
                      _formatPrice(item.unitPrice),
                      style: const TextStyle(
                        color: AppColors.textMuted,
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    IconButton(
                      tooltip: 'Xoá',
                      visualDensity: VisualDensity.compact,
                      onPressed: () => ref
                          .read(cartProvider.notifier)
                          .removeItem(item.lineKey),
                      icon: const Icon(
                        Icons.delete_outline,
                        color: AppColors.accent,
                        size: 20,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _QuantityPill extends StatelessWidget {
  const _QuantityPill({
    required this.quantity,
    required this.onDec,
    required this.onInc,
  });

  final int quantity;
  final VoidCallback onDec;
  final VoidCallback onInc;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFFF5F0E6),
        borderRadius: BorderRadius.circular(999),
      ),
      padding: const EdgeInsets.all(3),
      child: Row(
        children: [
          _QtyButton(icon: Icons.remove, onTap: quantity > 1 ? onDec : null),
          SizedBox(
            width: 30,
            child: Text(
              '$quantity',
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: AppColors.textPrimary,
                fontSize: 13,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
          _QtyButton(icon: Icons.add, onTap: quantity < 99 ? onInc : null),
        ],
      ),
    );
  }
}

class _QtyButton extends StatelessWidget {
  const _QtyButton({required this.icon, this.onTap});

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
          width: 28,
          height: 28,
          child: Icon(
            icon,
            color: enabled ? AppColors.primary : AppColors.textMuted,
            size: 15,
          ),
        ),
      ),
    );
  }
}

class _CartDrawerFooter extends StatelessWidget {
  const _CartDrawerFooter({required this.cart});

  final CartData cart;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        16,
        14,
        16,
        MediaQuery.paddingOf(context).bottom + 14,
      ),
      decoration: BoxDecoration(
        color: AppColors.surface,
        border: const Border(top: BorderSide(color: AppColors.border)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 14,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          _SummaryRow(label: 'Tạm tính', value: _formatPrice(cart.subtotal)),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () {
                    Navigator.of(context).pop();
                    context.showPageLoader();
                    context.go(AppRoutes.cart);
                  },
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    side: const BorderSide(color: AppColors.primary),
                    padding: const EdgeInsets.symmetric(vertical: 13),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  child: const Text(
                    'Xem giỏ',
                    style: TextStyle(fontWeight: FontWeight.w900),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: FilledButton(
                  onPressed: () {
                    Navigator.of(context).pop();
                    context.showPageLoader();
                    context.go(AppRoutes.checkout);
                  },
                  style: FilledButton.styleFrom(
                    backgroundColor: AppColors.accent,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 13),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  child: const Text(
                    'Đặt món',
                    style: TextStyle(fontWeight: FontWeight.w900),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _SummaryRow extends StatelessWidget {
  const _SummaryRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(
          label,
          style: const TextStyle(
            color: AppColors.textMuted,
            fontSize: 13,
            fontWeight: FontWeight.w800,
          ),
        ),
        const Spacer(),
        Text(
          value,
          style: const TextStyle(
            color: AppColors.primaryStrong,
            fontSize: 20,
            fontWeight: FontWeight.w900,
          ),
        ),
      ],
    );
  }
}

String _formatPrice(int cents) {
  final euros = cents / 100;
  return '€ ${euros.toStringAsFixed(2).replaceAll('.', ',')}';
}
