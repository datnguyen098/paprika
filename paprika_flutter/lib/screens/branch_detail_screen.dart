import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/utils/image_helper.dart';
import '../data/models/branch_model.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';

/// Branch detail screen — chi tiết 1 chi nhánh.
///
/// Dùng [branchDetailProvider(id)] để fetch data. Hiển thị:
///   - Header ảnh
///   - Thông tin cơ bản (địa chỉ, hotline, giờ mở cửa)
///   - Nút gọi / chỉ đường
///   - Google Maps iframe (nếu có)
///   - Các loại đơn được phép (online/pickup/delivery)
///   - Delivery info (min order, free delivery threshold)
class BranchDetailScreen extends ConsumerWidget {
  const BranchDetailScreen({super.key, required this.branchId});

  final int branchId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final branchAsync = ref.watch(branchDetailProvider(branchId));

    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          // Custom app bar với nút back
          _DetailAppBar(branchId: branchId),

          Expanded(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  branchAsync.when(
                    data: (branch) => _BranchContent(branch: branch),
                    loading: () => const _LoadingContent(),
                    error: (error, _) => _ErrorContent(
                      message: error.toString(),
                      onRetry: () =>
                          ref.invalidate(branchDetailProvider(branchId)),
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
}

class _DetailAppBar extends ConsumerWidget {
  const _DetailAppBar({required this.branchId});
  final int branchId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final branchAsync = ref.watch(branchDetailProvider(branchId));
    final name = branchAsync.maybeWhen(
      data: (b) => b.name,
      orElse: () => 'Chi nhánh',
    );

    return Container(
      color: AppColors.primary,
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.symmetric(
            horizontal: AppConstants.spaceSm,
            vertical: AppConstants.spaceXs,
          ),
          child: Row(
            children: [
              IconButton(
                icon: const Icon(Icons.arrow_back, color: Colors.white),
                onPressed: () => context.go(AppRoutes.branches),
              ),
              Expanded(
                child: Text(
                  name,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _BranchContent extends StatelessWidget {
  const _BranchContent({required this.branch});
  final Branch branch;

  @override
  Widget build(BuildContext context) {
    final b = branch;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Hero image
        if ((b.image ?? '').isNotEmpty)
          AspectRatio(
            aspectRatio: 16 / 7,
            child: Image.network(
              ImageHelper.url(b.image) ?? '',
              fit: BoxFit.cover,
              errorBuilder: (_, __, ___) => Container(
                color: AppColors.warm,
                child: const Center(
                  child: Icon(Icons.storefront,
                      size: 64, color: AppColors.primary),
                ),
              ),
            ),
          ),

        Padding(
          padding: const EdgeInsets.all(AppConstants.spaceMd),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Name
              Text(
                b.name,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                  color: AppColors.textPrimary,
                ),
              ),

              // Address
              if ((b.address).isNotEmpty) ...[
                const SizedBox(height: AppConstants.spaceSm),
                _DetailRow(
                  icon: Icons.location_on_outlined,
                  text: b.address,
                ),
              ],

              // Phone / Hotline
              const SizedBox(height: AppConstants.spaceSm),
              _DetailRow(
                icon: Icons.phone_outlined,
                text: b.displayHotline,
              ),

              // Email
              if ((b.email ?? '').isNotEmpty) ...[
                const SizedBox(height: AppConstants.spaceSm),
                _DetailRow(
                  icon: Icons.email_outlined,
                  text: b.email!,
                ),
              ],

              // Opening hours
              if ((b.openingHours ?? '').isNotEmpty) ...[
                const SizedBox(height: AppConstants.spaceSm),
                _DetailRow(
                  icon: Icons.schedule_outlined,
                  text: b.openingHours!,
                ),
              ],

              // Description
              if ((b.description ?? '').isNotEmpty) ...[
                const SizedBox(height: AppConstants.spaceMd),
                Text(
                  b.description!,
                  style: const TextStyle(
                    fontSize: 13,
                    color: AppColors.textMuted,
                    height: 1.6,
                  ),
                ),
              ],

              const SizedBox(height: AppConstants.spaceLg),

              // Action buttons
              Row(
                children: [
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () {},
                      icon: const Icon(Icons.phone, size: 18),
                      label: const Text('Gọi ngay'),
                    ),
                  ),
                  const SizedBox(width: AppConstants.spaceSm),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {},
                      icon: const Icon(Icons.directions, size: 18),
                      label: const Text('Chỉ đường'),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: AppConstants.spaceLg),

              // Order modes
              if (_hasOrderModes(b)) ...[
                _SectionHeader(title: 'Hình thức phục vụ'),
                const SizedBox(height: AppConstants.spaceSm),
                Wrap(
                  spacing: AppConstants.spaceSm,
                  runSpacing: AppConstants.spaceSm,
                  children: [
                    if (b.acceptsOnlineOrders == true)
                      _ModeChip(
                        icon: Icons.shopping_bag_outlined,
                        label: 'Đặt online',
                        available: true,
                      ),
                    if (b.acceptsPickupOrders == true)
                      _ModeChip(
                        icon: Icons.takeout_dining_outlined,
                        label: 'Mang đi',
                        available: true,
                      ),
                    if (b.acceptsDeliveryOrders == true)
                      _ModeChip(
                        icon: Icons.delivery_dining_outlined,
                        label: 'Giao hàng',
                        available: true,
                      ),
                  ],
                ),
              ],

              // Delivery info
              if (_hasDeliveryInfo(b)) ...[
                const SizedBox(height: AppConstants.spaceLg),
                _SectionHeader(title: 'Thông tin giao hàng'),
                const SizedBox(height: AppConstants.spaceSm),
                Container(
                  padding: const EdgeInsets.all(AppConstants.spaceMd),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(AppConstants.radius),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Column(
                    children: [
                      if (b.deliveryMinOrderAmount != null)
                        _DeliveryRow(
                          label: 'Đơn tối thiểu',
                          value:
                              '€${(b.deliveryMinOrderAmount! / 100).toStringAsFixed(2)}',
                        ),
                      if (b.deliveryFreeOrderAmount != null) ...[
                        const SizedBox(height: 8),
                        _DeliveryRow(
                          label: 'Miễn phí giao hàng',
                          value:
                              'từ €${(b.deliveryFreeOrderAmount! / 100).toStringAsFixed(2)}',
                        ),
                      ],
                      if (b.deliveryMaxDistanceKm != null) ...[
                        const SizedBox(height: 8),
                        _DeliveryRow(
                          label: 'Bán kính giao hàng',
                          value: '${b.deliveryMaxDistanceKm} km',
                        ),
                      ],
                    ],
                  ),
                ),
              ],

              // Map
              if ((b.googleMapIframe ?? '').isNotEmpty) ...[
                const SizedBox(height: AppConstants.spaceLg),
                _SectionHeader(title: 'Bản đồ'),
                const SizedBox(height: AppConstants.spaceSm),
                Container(
                  height: 200,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(AppConstants.radius),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(AppConstants.radius),
                    child: _MapWidget(url: b.mapEmbedSrc ?? ''),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  bool _hasOrderModes(Branch b) =>
      b.acceptsOnlineOrders == true ||
      b.acceptsPickupOrders == true ||
      b.acceptsDeliveryOrders == true;

  bool _hasDeliveryInfo(Branch b) =>
      b.deliveryMinOrderAmount != null ||
      b.deliveryFreeOrderAmount != null ||
      b.deliveryMaxDistanceKm != null;
}

/// Map widget đơn giản — hiển thị Google Maps URL từ BE.
/// Hiện tại dùng button "Mở bản đồ" để launch URL trong browser.
/// Thay bằng url_launcher hoặc webview_flutter khi cần.
class _MapWidget extends StatelessWidget {
  const _MapWidget({required this.url});
  final String url;

  @override
  Widget build(BuildContext context) {
    if (url.isEmpty) {
      return Container(
        color: AppColors.warm,
        child: const Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.map_outlined, size: 40, color: AppColors.textMuted),
              SizedBox(height: 8),
              Text(
                'Bản đồ không khả dụng',
                style: TextStyle(color: AppColors.textMuted, fontSize: 13),
              ),
            ],
          ),
        ),
      );
    }

    // Nếu url bắt đầu bằng iframe src URL thực sự
    final decoded = Uri.decodeComponent(url);
    return Stack(
      children: [
        // Placeholder — thay bằng webview_flutter khi cần
        Container(
          color: AppColors.warm,
          child: Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.map, size: 40, color: AppColors.primary),
                const SizedBox(height: 8),
                const Text(
                  'Bản đồ',
                  style: TextStyle(
                    color: AppColors.primary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  decoded,
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.textMuted,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ),
        Positioned(
          right: 8,
          bottom: 8,
          child: ElevatedButton.icon(
            onPressed: () {
              // TODO: dùng url_launcher để mở map
              // import 'package:url_launcher/url_launcher.dart';
              // if (await canLaunchUrl(Uri.parse(decoded))) launchUrl(Uri.parse(decoded));
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Mở bản đồ trong trình duyệt...'),
                  behavior: SnackBarBehavior.floating,
                ),
              );
            },
            icon: const Icon(Icons.open_in_new, size: 14),
            label: const Text('Mở bản đồ'),
            style: ElevatedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              minimumSize: Size.zero,
              textStyle: const TextStyle(fontSize: 12),
            ),
          ),
        ),
      ],
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.icon, required this.text});
  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: AppColors.primary),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: const TextStyle(
              fontSize: 14,
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ],
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title});
  final String title;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 4,
          height: 18,
          decoration: BoxDecoration(
            color: AppColors.accent,
            borderRadius: BorderRadius.circular(2),
          ),
        ),
        const SizedBox(width: 8),
        Text(
          title.toUpperCase(),
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w900,
            color: AppColors.primaryStrong,
            letterSpacing: 0.1,
          ),
        ),
      ],
    );
  }
}

class _ModeChip extends StatelessWidget {
  const _ModeChip({
    required this.icon,
    required this.label,
    required this.available,
  });
  final IconData icon;
  final String label;
  final bool available;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: available ? AppColors.successBg : AppColors.errorBg,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(
          color: available ? AppColors.successBorder : AppColors.errorBorder,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 16,
            color: available ? AppColors.successText : AppColors.errorText,
          ),
          const SizedBox(width: 6),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: available ? AppColors.successText : AppColors.errorText,
            ),
          ),
        ],
      ),
    );
  }
}

class _DeliveryRow extends StatelessWidget {
  const _DeliveryRow({required this.label, required this.value});
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 13,
            color: AppColors.textMuted,
          ),
        ),
        Text(
          value,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w900,
            color: AppColors.textPrimary,
          ),
        ),
      ],
    );
  }
}

class _LoadingContent extends StatelessWidget {
  const _LoadingContent();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(AppConstants.spaceMd),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            height: 200,
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(AppConstants.radius),
            ),
          ),
          const SizedBox(height: AppConstants.spaceMd),
          ...List.generate(
            4,
            (i) => Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Container(
                height: 16,
                width: [200.0, 280.0, 160.0, 220.0][i],
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorContent extends StatelessWidget {
  const _ErrorContent({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(AppConstants.spaceLg),
      child: Column(
        children: [
          const Icon(Icons.error_outline, size: 48, color: AppColors.accent),
          const SizedBox(height: 12),
          const Text(
            'Không tải được thông tin chi nhánh',
            style: TextStyle(
              fontWeight: FontWeight.w700,
              fontSize: 16,
              color: AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            message,
            style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh, size: 16),
            label: const Text('Thử lại'),
          ),
        ],
      ),
    );
  }
}
