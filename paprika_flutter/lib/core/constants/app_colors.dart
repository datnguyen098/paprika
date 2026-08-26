import 'package:flutter/material.dart';

/// Bảng màu chính thức của Paprika Patras - trích từ Paprika-main/public/storefront/storefront.css
///
/// Mapping từ CSS variables:
///   --sf-primary → primary
///   --sf-accent  → accent
///   --sf-cream   → cream
///   --sf-gold    → gold
class AppColors {
  AppColors._();

  // Brand - Xanh lá đậm (chủ đạo - nhà hàng)
  static const Color primary = Color(0xFF064E3B);
  static const Color primaryStrong = Color(0xFF043427);
  static const Color primarySoft = Color(0xFF0F5A3D);
  static const Color primaryMuted = Color(0xFF1A6B4A);

  // Accent - Đỏ ớt (Paprika = tên nhà hàng)
  static const Color accent = Color(0xFFB91C1C);
  static const Color accentStrong = Color(0xFF991B1B);
  static const Color accentSoft = Color(0xFFFFF0EF);

  // Background - Kem ấm
  static const Color cream = Color(0xFFFDFBF7);
  static const Color warm = Color(0xFFFFF9EF);
  static const Color surfaceMuted = Color(0xFFF9F7F2);

  // Gold - Điểm nhấn
  static const Color gold = Color(0xFFFFD700);
  static const Color goldDark = Color(0xFFB5853D);

  // Surface & Border
  static const Color surface = Color(0xFFFFFFFF);
  static const Color border = Color(0xFFE2D8C8);

  // Text
  static const Color textPrimary = Color(0xFF172018);
  static const Color textMuted = Color(0xFF687064);

  // Service card phụ
  static const Color brownDeep = Color(0xFF92400E);
  static const Color brownDark = Color(0xFF78350F);

  // Status
  static const Color successBg = Color(0xFFE8F5E9);
  static const Color successText = Color(0xFF043427);
  static const Color successBorder = Color(0xFFB8DEC6);
  static const Color errorBg = Color(0xFFFFF0F0);
  static const Color errorBorder = Color(0xFFFECACA);
  static const Color errorText = Color(0xFF8F1F1B);
}