import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';
import '../widgets/page_transition_loader.dart';

class OrderSuccessScreen extends StatelessWidget {
  const OrderSuccessScreen({
    super.key,
    required this.code,
    this.invoiceNumber,
    this.total,
  });

  final String code;
  final String? invoiceNumber;
  final int? total;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(activeRoute: AppRoutes.cart),
          Expanded(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 34, 16, 34),
                    child: Center(
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 720),
                        child: Container(
                          padding: const EdgeInsets.all(22),
                          decoration: BoxDecoration(
                            color: AppColors.surface,
                            borderRadius: BorderRadius.circular(18),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Column(
                            children: [
                              Container(
                                width: 74,
                                height: 74,
                                decoration: const BoxDecoration(
                                  color: AppColors.successBg,
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.check_circle,
                                  color: AppColors.primary,
                                  size: 42,
                                ),
                              ),
                              const SizedBox(height: 16),
                              const Text(
                                'Đã gửi đơn hàng',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  color: AppColors.textPrimary,
                                  fontSize: 26,
                                  fontWeight: FontWeight.w900,
                                ),
                              ),
                              const SizedBox(height: 8),
                              const Text(
                                'Quán sẽ liên hệ xác nhận đơn. Thanh toán sẽ thực hiện trực tiếp theo hình thức offline.',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  color: AppColors.textMuted,
                                  fontSize: 14,
                                  height: 1.45,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 18),
                              _InfoRow(label: 'Mã đơn', value: code),
                              if (invoiceNumber != null && invoiceNumber!.isNotEmpty)
                                _InfoRow(label: 'Hoá đơn', value: invoiceNumber!),
                              if (total != null)
                                _InfoRow(label: 'Tổng tạm tính', value: _formatPrice(total!)),
                              const SizedBox(height: 18),
                              SizedBox(
                                width: double.infinity,
                                child: FilledButton.icon(
                                  onPressed: () {
                                    context.showPageLoader();
                                    context.go(AppRoutes.orderTrackingPath(code));
                                  },
                                  style: FilledButton.styleFrom(
                                    backgroundColor: AppColors.primary,
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(vertical: 13),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(14),
                                    ),
                                  ),
                                  icon: const Icon(Icons.local_shipping_outlined),
                                  label: const Text(
                                    'Theo dõi đơn',
                                    style: TextStyle(fontWeight: FontWeight.w900),
                                  ),
                                ),
                              ),
                              const SizedBox(height: 10),
                              Row(
                                children: [
                                  Expanded(
                                    child: OutlinedButton(
                                      onPressed: () {
                                        context.showPageLoader();
                                        context.go(AppRoutes.menu);
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
                                        'Đặt thêm',
                                        style: TextStyle(fontWeight: FontWeight.w900),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: FilledButton(
                                      onPressed: () {
                                        context.showPageLoader();
                                        context.go(AppRoutes.home);
                                      },
                                      style: FilledButton.styleFrom(
                                        backgroundColor: AppColors.primary,
                                        foregroundColor: Colors.white,
                                        padding: const EdgeInsets.symmetric(vertical: 13),
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(14),
                                        ),
                                      ),
                                      child: const Text(
                                        'Trang chủ',
                                        style: TextStyle(fontWeight: FontWeight.w900),
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

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: AppColors.cream,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                color: AppColors.textMuted,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          Text(
            value,
            style: const TextStyle(
              color: AppColors.primaryStrong,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

String _formatPrice(int cents) {
  final euros = cents / 100;
  return '€ ${euros.toStringAsFixed(2).replaceAll('.', ',')}';
}
