import '../config/env.dart';

/// API endpoints - tất cả route từ Paprika-main/routes/api.php
class ApiConstants {
  ApiConstants._();

  // ==================== Base URLs ====================
  /// Base URL cho Laravel API — lấy tự động từ [Env] (auto-detect thiết bị).
  ///
  /// Mapping thiết bị → host (xem chi tiết tại lib/core/config/env.dart):
  ///   - Android emulator      → http://10.0.2.2:8000/api/v1
  ///   - Android thiết bị thật → http://192.168.1.53:8000/api/v1
  ///   - iOS Simulator         → http://127.0.0.1:8000/api/v1
  ///   - Web                   → http://localhost:8000/api/v1
  ///   - Production (override) → truyền --dart-define=API_BASE_URL=...
  static String get baseUrl => Env.apiBaseUrl;

  /// Origin của web Laravel (KHÔNG kèm /api/v1).
  /// Dùng để build absolute URL cho ảnh trả về relative path từ BE
  /// (vd `image: "paprika/menu/pho-bo.jpg"` → http://{host}/paprika/menu/pho-bo.jpg).
  static String get webOrigin => Env.webOrigin;

  // ==================== Timeouts ====================
  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 15);

  // ==================== Headers ====================
  static const String headerAccept = 'Accept';
  static const String headerContentType = 'Content-Type';
  static const String headerAuthorization = 'Authorization';
  static const String headerLocale = 'Accept-Language';
  static const String headerBranch = 'X-Branch-Id';
  static const String valueJson = 'application/json';
  static const String bearerPrefix = 'Bearer';

  // ==================== Endpoints - Home ====================
  /// GET /api/v1/home — trả về banners, categories, featured, testimonials,
  /// latest_posts, promo_popup.
  static const String home = '/home';

  // ==================== Endpoints - Categories ====================
  static const String categories = '/categories';

  // ==================== Endpoints - Dishes / Menu ====================
  static const String menu = '/menu';
  static const String featuredDishes = '/dishes/featured';
  static const String dishSearch = '/dishes/search';
  static String dishDetail(int id) => '/dishes/$id';

  // ==================== Endpoints - About ====================
  /// GET /api/v1/about — trả về story, mission, vision, team_members, stats.
  static const String about = '/about';

  // ==================== Endpoints - Branches ====================
  /// GET /api/v1/branches — danh sách chi nhánh active.
  /// GET /api/v1/branches/{id} — chi tiết 1 chi nhánh.
  static const String branches = '/branches';
  static String branchDetail(int id) => '/branches/$id';

  // ==================== Endpoints - Contact ====================
  /// POST /api/v1/contact — gửi form liên hệ.
  static const String contact = '/contact';

  // ==================== Endpoints - Auth (định nghĩa sẵn, BE chưa có) ====================
  static const String authRegister = '/auth/register';
  static const String authLogin = '/auth/login';
  static const String authLogout = '/auth/logout';
  static const String authProfile = '/auth/profile';
  static const String authForgotPassword = '/auth/forgot-password';

  // ==================== Endpoints - Banners ====================
  static const String banners = '/banners/active';

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
