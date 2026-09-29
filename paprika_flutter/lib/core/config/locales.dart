// Flutter port 1:1 của Paprika-main/config/locales.php (Laravel).
//
// File này là SINGLE SOURCE OF TRUTH cho:
//   1. Danh sách locale mà app hỗ trợ (vi, en, el)
//   2. Metadata (name, native name, flag, ogLocale) cho mỗi locale
//   3. Default locale fallback
//   4. Helper kiểm tra locale có hỗ trợ không
//
// Khi Laravel thêm/xoá locale → cập nhật file này để đồng bộ.
//
// Tham chiếu Laravel gốc:
//   Paprika-main/config/locales.php  →  'supported' => ['vi' => [...], 'en' => [...], 'el' => [...]]

import 'dart:ui' show Locale;

/// Metadata cho 1 locale. Mirror mảng config ở Laravel.
class LocaleMeta {
  const LocaleMeta({
    required this.code,
    required this.name,
    required this.native,
    required this.flag,
    required this.ogLocale,
    required this.direction,
  });

  /// Locale code ngắn (vi, en, el). Trùng key của Laravel.
  final String code;

  /// Tên tiếng Anh (English name of language) - mirror Laravel 'name'.
  /// VD: 'Vietnamese', 'English', 'Greek'.
  final String name;

  /// Tên bản địa (Endonym) - mirror Laravel 'native'.
  /// VD: 'Tiếng Việt', 'English', 'Ελληνικά'.
  final String native;

  /// Emoji flag hiển thị trên UI switcher.
  /// Lưu ý: `Locale` của Flutter không hỗ trợ country code native,
  /// nên dùng emoji riêng. Mirror Laravel 'flag'.
  final String flag;

  /// Open Graph locale theo chuẩn Facebook/Twitter (vd: 'vi_VN').
  /// Mirror Laravel 'og_locale'.
  final String ogLocale;

  /// Text direction: 'ltr' hoặc 'rtl'. Hiện tại chỉ 'ltr'.
  /// Mirror Laravel 'direction'.
  final String direction;

  /// Convert sang Flutter Locale để pass cho MaterialApp.
  Locale toLocale() => Locale(code);
}

/// Config locale toàn cục. Mirror `config/locales.php` của Laravel.
///
/// Cách dùng:
/// ```dart
/// // Lấy danh sách
/// AppLocales.supported                       // {vi, en, el}
/// AppLocales.supported['en']                 // LocaleMeta cho en
/// AppLocales.supportedList                    // List<LocaleMeta>
///
/// // Helper
/// AppLocales.isSupported('en')               // true
/// AppLocales.defaultLocale                    // 'vi'
/// ```
class AppLocales {
  AppLocales._();

  /// Source locale - locale gốc để fallback khi translation thiếu.
  /// Mirror Laravel 'source_locale'. Flutter dùng 'vi' vì content DB gốc là VI.
  static const String sourceLocale = 'vi';

  /// Default locale khi không có preference và device locale không support.
  /// Mirror Laravel 'default'.
  static const String defaultLocale = 'vi';

  /// Map các locale được hỗ trợ. Key = locale code ngắn (vi/en/el).
  /// Value = metadata. Mirror `config/locales.php` array `supported`.
  static const Map<String, LocaleMeta> supported = {
    'vi': LocaleMeta(
      code: 'vi',
      name: 'Vietnamese',
      native: 'Tiếng Việt',
      flag: '🇻🇳',
      ogLocale: 'vi_VN',
      direction: 'ltr',
    ),
    'en': LocaleMeta(
      code: 'en',
      name: 'English',
      native: 'English',
      flag: '🇬🇧',
      ogLocale: 'en_US',
      direction: 'ltr',
    ),
    'el': LocaleMeta(
      code: 'el',
      name: 'Greek',
      native: 'Ελληνικά',
      flag: '🇬🇷',
      ogLocale: 'el_GR',
      direction: 'ltr',
    ),
  };
  /// List các LocaleMeta, thường dùng cho dropdown/popupmenu.
  static final List<LocaleMeta> supportedList = [
    supported['vi']!,
    supported['en']!,
    supported['el']!,
  ];

  /// Check 1 locale code có được support không.
  /// Dùng khi user đổi locale từ URL/header/storage để validate trước khi apply.
  static bool isSupported(String? code) {
    if (code == null || code.isEmpty) return false;
    return supported.containsKey(code);
  }

  /// Lấy LocaleMeta an toàn, fallback về default locale.
  static LocaleMeta metaOf(String? code) {
    if (isSupported(code)) return supported[code!]!;
    return supported[defaultLocale]!;
  }

  /// Locale Flutter đầy đủ cho MaterialApp.supportedLocales.
  static List<Locale> get flutterSupported =>
      supportedList.map((m) => m.toLocale()).toList(growable: false);
}
