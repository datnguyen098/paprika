/// API endpoints - tất cả route từ Paprika-main/routes/api.php
class ApiConstants {
  ApiConstants._();

  /// Base URL cho Laravel API
  /// - Android emulator dùng 10.0.2.2 (đặc biệt, trỏ vào localhost máy host)
  /// - iOS simulator dùng localhost hoặc 127.0.0.1
  /// - Production: thay bằng https://api.paprika.com/api/v1
  ///
  /// Tạm thời dùng 10.0.2.2 để dev Android, sau deploy đổi lại.
  static const String baseUrl = 'http://10.0.2.2:8000/api/v1';

  // ==================== Timeouts ====================
  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 15);

  // ==================== Headers ====================
  static const String headerAccept = 'Accept';
  static const String headerContentType = 'Content-Type';
  static const String headerAuthorization = 'Authorization';
  static const String headerLocale = 'Accept-Language';
  static const String valueJson = 'application/json';
  static const String bearerPrefix = 'Bearer';

  // ==================== Endpoints - Categories ====================
  static const String categories = '/categories';

  // ==================== Endpoints - Dishes / Menu ====================
  static const String menu = '/menu';
  static const String featuredDishes = '/dishes/featured';
  static const String dishSearch = '/dishes/search';
  static String dishDetail(int id) => '/dishes/$id';

  // ==================== Endpoints - Auth (định nghĩa sẵn, BE chưa có) ====================
  static const String authRegister = '/auth/register';
  static const String authLogin = '/auth/login';
  static const String authLogout = '/auth/logout';
  static const String authProfile = '/auth/profile';
  static const String authForgotPassword = '/auth/forgot-password';

  // ==================== Endpoints - Banners ====================
  static const String banners = '/banners/active';

  // ==================== Endpoints - Branches ====================
  static const String branches = '/branches';

  // ==================== Endpoints - Cart ====================
  static const String cart = '/cart';
  static const String cartItems = '/cart/items';
  static const String cartClear = '/cart/clear';
  static String cartItem(int id) => '/cart/items/$id';
  static const String cartVoucher = '/cart/voucher';

  // ==================== Endpoints - Orders ====================
  static const String orders = '/orders';
  static String orderDetail(int id) => '/orders/$id';
  static String orderCancel(int id) => '/orders/$id/cancel';
  static String orderTrack(int id) => '/orders/$id/track';

  // ==================== Endpoints - Reservations ====================
  static const String reservations = '/reservations';

  // ==================== Endpoints - Settings ====================
  static const String settings = '/settings';
}