import 'package:flutter/material.dart';

import '../core/constants/app_colors.dart';

/// Helper chung để hiển thị "Tính năng đang được phát triển".
///
/// Mục đích: thay thế việc [context.go] đến route chưa có / chưa build
/// (sẽ ném exception hoặc show error screen của GoRouter) bằng một
/// SnackBar thân thiện.
class ComingSoon {
  ComingSoon._();

  /// Hiển thị snackbar "đang phát triển".
  ///
  /// [feature] là tên tính năng hiển thị trong snackbar, ví dụ:
  /// `'Thực đơn'`, `'Giỏ hàng'`, `'Đặt bàn'`, v.v.
  /// Nếu null sẽ dùng mặc định "Tính năng này".
  static void show(BuildContext context, {String? feature}) {
    final messenger = ScaffoldMessenger.maybeOf(context);
    if (messenger == null) return;

    // Clear snackbar cũ (nếu user click nhiều lần liên tiếp).
    messenger.hideCurrentSnackBar();

    final label = feature == null
        ? 'Tính năng này đang được phát triển'
        : '"$feature" đang được phát triển';

    messenger.showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(
              Icons.construction_rounded,
              color: Colors.white,
              size: 20,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    label,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'Team FE đang hoàn thiện. Vui lòng quay lại sau.',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.85),
                      fontSize: 11,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        backgroundColor: AppColors.primaryStrong,
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 2),
        margin: const EdgeInsets.all(12),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: BorderSide(
            color: AppColors.accent.withValues(alpha: 0.6),
            width: 1.5,
          ),
        ),
      ),
    );
  }

  /// Trả về một `VoidCallback` để gắn trực tiếp vào `onTap` / `onPressed`
  /// của button mà không cần tự build closure mỗi nơi.
  ///
  /// Ví dụ:
  /// ```dart
  /// InkWell(
  ///   onTap: ComingSoon.onTap(context, feature: 'Thực đơn'),
  ///   child: ...,
  /// )
  /// ```
  static VoidCallback onTap(BuildContext context, {String? feature}) {
    return () => show(context, feature: feature);
  }
}
