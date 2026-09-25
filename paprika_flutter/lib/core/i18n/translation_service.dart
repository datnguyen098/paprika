import 'package:flutter/widgets.dart';

import '../config/locales.dart';

/// Service dịch nội dung API/data theo locale hiện tại.
///
/// MIRROR chính xác logic của Laravel trait:
///   Paprika-main/app/Models/Concerns/HasLocalizedContent.php
///
/// Logic:
///   1. Thử locale hiện tại (vi/en/el)
///   2. Nếu trống → fallback chain (mặc định: [AppLocales.sourceLocale])
///   3. Cuối cùng về source (vi) - không bao giờ trả về rỗng
///
/// Phase 1: Tạo file skeleton + helper cơ bản.
/// Phase 2: Wire vào API models (Dish, Category, Branch, Page).
/// Phase 3: Tích hợp mapping table (Phase 3 - chưa triển khai).
class TranslationService {
  TranslationService._();

  /// Source locale - fallback chain cuối cùng.
  /// Mirror Laravel 'source_locale' = 'vi'.
  static const String sourceLocale = AppLocales.sourceLocale;

  /// Chọn giá trị theo locale hiện tại với fallback chain.
  ///
  /// Ví dụ (locale = 'el', nhưng description_el rỗng):
  ///   TranslationService.pickLocalized(
  ///     baseValue: dish.description,        // VI (luôn có)
  ///     localizedValues: {
  ///       'en': dish.descriptionEn,        // null hoặc ''
  ///       'el': dish.descriptionEl,        // null hoặc ''
  ///     },
  ///     currentLocale: 'el',
  ///   )
  ///   → fallback chain: ['el', 'vi']
  ///     • 'el' rỗng → skip
  ///     • 'vi' (source) → trả về baseValue
  ///
  /// Trường hợp đặc biệt: nếu current locale là source ('vi') thì trả base.
  static String pickLocalized({
    required String baseValue,
    required Map<String, String?> localizedValues,
    required String currentLocale,
    List<String>? fallbackChain,
  }) {
    final chain = fallbackChain ?? <String>[currentLocale, sourceLocale];

    for (final code in chain) {
      String? candidate;
      if (code == sourceLocale) {
        candidate = baseValue;
      } else {
        candidate = localizedValues[code];
      }

      if (candidate != null && candidate.trim().isNotEmpty) {
        return candidate;
      }
    }

    // Fallback cuối cùng: source locale (mirror Laravel's 'never-return-null')
    return baseValue;
  }

  /// Helper gọn cho trường hợp phổ biến: lấy locale từ BuildContext.
  static String localeOf(BuildContext context) {
    return Localizations.localeOf(context).languageCode;
  }
}
