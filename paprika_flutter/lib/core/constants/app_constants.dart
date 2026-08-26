/// Constants UI chung cho toàn dự án - trích từ CSS variables trong Paprika-main
class AppConstants {
  AppConstants._();

  // ==================== Geometry ====================
  // --sf-radius-xs → radiusXs
  // --sf-radius-sm → radiusSm
  // --sf-radius    → radius
  static const double radiusXs = 8;
  static const double radiusSm = 12;
  static const double radius = 18;
  static const double radiusLg = 24;

  // ==================== Spacing ====================
  static const double spaceXs = 4;
  static const double spaceSm = 8;
  static const double spaceMd = 16;
  static const double spaceLg = 24;
  static const double spaceXl = 32;
  static const double spaceXxl = 48;

  // ==================== Touch ====================
  // --sf-touch → minTouchTarget
  static const double minTouchTarget = 44;

  // ==================== Max width ====================
  // --sf-max → maxContentWidth
  static const double maxContentWidth = 1180;

  // ==================== Asset paths ====================
  static const String logoHeader = 'assets/images/logo-hs.webp';
  static const String wordmark = 'assets/images/wordmark.webp';
  static const String defaultHero = 'assets/images/default-hero.jpg';
  static const String placeholderDish = 'assets/images/placeholders/dish.png';
  static const String placeholderCategory = 'assets/images/placeholders/category.png';

  // ==================== Locales (BE hỗ trợ 3 ngôn ngữ) ====================
  static const String localeVi = 'vi';
  static const String localeEn = 'en';
  static const String localeEl = 'el'; // Hy Lạp - Patras

  static const String defaultLocale = localeVi;
  static const List<String> supportedLocales = [localeVi, localeEn, localeEl];

  // ==================== Currency ====================
  static const String defaultCurrency = '€'; // Euro - Patras, Hy Lạp
}