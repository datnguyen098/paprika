import 'dart:io' show Platform;

import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/foundation.dart';

/// Cấu hình runtime cho baseUrl / webOrigin.
///
/// Tự động chọn host phù hợp với thiết bị đang chạy để dev
/// không phải đổi code mỗi lần đổi giữa emulator ↔ máy thật ↔ web:
///
///   - Android Emulator  → `http://10.0.2.2:{port}`    (host loopback của emulator)
///   - Android thiết bị thật → `http://{lanDevIp}:{port}` (cùng Wi-Fi với máy dev)
///   - iOS Simulator     → `http://127.0.0.1:{port}`
///   - Web (Chrome)      → `http://localhost:{port}`
///
/// Production / staging: truyền URL qua `--dart-define` để override hoàn toàn:
///
///   flutter build apk --release \
///     --dart-define=API_BASE_URL=https://api.paprikapatras.gr/api/v1 \
///     --dart-define=WEB_ORIGIN=https://api.paprikapatras.gr
///
/// Khi đã truyền `--dart-define`, [init] sẽ ưu tiên dùng giá trị này
/// và KHÔNG detect thiết bị (tránh sai khi build release).
///
/// ## Khởi tạo
///
/// Gọi `await Env.init()` trong `main()` trước `runApp()`. Lần đầu chạy
/// detect đầy đủ (cả emulator vs device).
///
/// ## Hot reload safety
///
/// Hot reload CÓ THỂ reset static state của class này (Dart bảo toàn
/// statics trong hầu hết trường hợp, nhưng không tuyệt đối — đặc biệt khi
/// reload graph đụng tới file này). Vì vậy các getter ([apiBaseUrl],
/// [webOrigin]) có cơ chế **fallback sync**: nếu [init] chưa chạy xong
/// (hoặc đã bị reset), getter tự suy ra URL theo platform NGAY LẬP TỨC
/// mà không throw. Hot reload sẽ chỉ sai URL khi bạn vừa đổi thiết bị
/// (emulator → máy thật) — bấm **R** (hot restart) để detect lại.
///
/// Lưu ý: fallback sync dùng `lanDevIp` cho Android (mặc định "máy thật"),
/// vì đó là case phổ biến nhất của dev. Nếu bạn đang ở emulator và hot
/// reload liên tục, hãy bấm R để re-detect.
class Env {
  Env._();

  // ============================================================
  // Cấu hình tập trung - chỉnh 1 chỗ khi đổi mạng / đổi port
  // ============================================================

  /// IP LAN của máy dev (chạy Laravel).
  /// Khi chuyển Wi-Fi, chạy `ipconfig` trên máy dev rồi cập nhật chỗ này.
  static const String lanDevIp = '192.168.1.53';

  /// Port Laravel dev (mặc định `php artisan serve` là 8000).
  static const String devPort = '8000';

  // ============================================================
  // Override từ --dart-define (ưu tiên cao nhất, dùng cho prod/staging)
  // ============================================================

  static const String _overrideApiBaseUrl =
      String.fromEnvironment('API_BASE_URL');

  static const String _overrideWebOrigin =
      String.fromEnvironment('WEB_ORIGIN');

  // ============================================================
  // Runtime state - set bởi init() hoặc _fallbackSync()
  // ============================================================

  static String _apiBaseUrl = '';
  static String _webOrigin = '';
  static bool _initialized = false;
  static String _resolvedBy = ''; // 'dart-define' | 'device-detect' | 'platform-fallback'

  // ============================================================
  // Public getters - KHÔNG BAO GIỜ throw, luôn trả về URL hợp lệ
  // ============================================================

  /// Base URL cho API (`{origin}/api/v1`).
  ///
  /// Nếu [init] chưa chạy (hiếm — chỉ xảy ra khi truy cập trước main()
  /// hoặc sau hot reload reset static), tự fallback theo platform.
  static String get apiBaseUrl {
    _ensureResolved();
    return _apiBaseUrl;
  }

  /// Origin thuần (không kèm `/api/v1`) — dùng để build URL ảnh.
  ///
  /// Cùng cơ chế fallback với [apiBaseUrl].
  static String get webOrigin {
    _ensureResolved();
    return _webOrigin;
  }

  /// Cho biết URL hiện tại đến từ đâu (debug).
  static String get resolvedBy => _resolvedBy;

  /// Đã khởi tạo xong (chưa bị reset) chưa.
  static bool get isInitialized => _initialized;

  // ============================================================
  // init() - gọi trong main() trước runApp()
  // ============================================================

  /// Phát hiện thiết bị và set baseUrl/webOrigin.
  ///
  /// Idempotent — gọi nhiều lần chỉ resolve 1 lần trừ khi [force] = true.
  /// Set [force] = true nếu bạn muốn re-detect (vd sau khi đổi thiết bị).
  static Future<void> init({bool force = false}) async {
    if (_initialized && !force) return;

    // Ưu tiên 1: --dart-define (cho production/staging)
    if (_overrideApiBaseUrl.isNotEmpty && _overrideWebOrigin.isNotEmpty) {
      _apiBaseUrl = _overrideApiBaseUrl;
      _webOrigin = _overrideWebOrigin;
      _resolvedBy = 'dart-define';
    } else {
      // Ưu tiên 2: detect đầy đủ bằng device_info_plus
      final detected = await _detectByDevice();
      _apiBaseUrl = detected.$1;
      _webOrigin = detected.$2;
      _resolvedBy = 'device-detect';
    }

    _initialized = true;
    _logResolved();
  }

  /// Reset state (dùng cho test, hoặc khi muốn force re-init).
  /// KHÔNG gọi trong code production.
  static void reset() {
    _apiBaseUrl = '';
    _webOrigin = '';
    _initialized = false;
    _resolvedBy = '';
  }

  // ============================================================
  // Internal - resolution
  // ============================================================

  /// Đảm bảo đã có URL — gọi trước mỗi lần đọc getter.
  /// Nếu [init] chưa chạy (hot reload reset), dùng fallback sync theo
  /// platform. KHÔNG BAO GIỜ throw để app không crash.
  static void _ensureResolved() {
    if (_initialized) return;
    _fallbackSync();
  }

  /// Fallback đồng bộ theo platform — dùng khi [init] chưa chạy.
  ///
  /// Lưu ý: trên Android, không phân biệt được emulator vs device mà
  /// không cần `device_info_plus` (async). Mặc định chọn IP LAN (giả định
  /// thiết bị thật — case phổ biến nhất của dev). Nếu bạn đang ở
  /// emulator, bấm R (hot restart) để detect lại.
  static void _fallbackSync() {
    if (_overrideApiBaseUrl.isNotEmpty && _overrideWebOrigin.isNotEmpty) {
      _apiBaseUrl = _overrideApiBaseUrl;
      _webOrigin = _overrideWebOrigin;
      _resolvedBy = 'dart-define';
    } else if (kIsWeb) {
      const origin = 'http://localhost:$devPort';
      _apiBaseUrl = '$origin/api/v1';
      _webOrigin = origin;
      _resolvedBy = 'platform-fallback';
    } else if (Platform.isAndroid) {
      const host = lanDevIp;
      const origin = 'http://$host:$devPort';
      _apiBaseUrl = '$origin/api/v1';
      _webOrigin = origin;
      _resolvedBy = 'platform-fallback';
    } else if (Platform.isIOS) {
      const origin = 'http://127.0.0.1:$devPort';
      _apiBaseUrl = '$origin/api/v1';
      _webOrigin = origin;
      _resolvedBy = 'platform-fallback';
    } else {
      const origin = 'http://localhost:$devPort';
      _apiBaseUrl = '$origin/api/v1';
      _webOrigin = origin;
      _resolvedBy = 'platform-fallback';
    }

    _initialized = true;
    _logResolved();
  }

  /// Detect đầy đủ (async, có device_info_plus).
  static Future<(String, String)> _detectByDevice() async {
    // Web (Chrome / Safari / ...) → localhost
    if (kIsWeb) {
      const origin = 'http://localhost:$devPort';
      return ('$origin/api/v1', origin);
    }

    // Android: cần phân biệt emulator vs thiết bị thật
    // vì host của emulator là 10.0.2.2, còn máy thật dùng IP LAN.
    if (Platform.isAndroid) {
      final info = await DeviceInfoPlugin().androidInfo;
      final isEmulator = !info.isPhysicalDevice;
      final host = isEmulator ? '10.0.2.2' : lanDevIp;
      final origin = 'http://$host:$devPort';
      return ('$origin/api/v1', origin);
    }

    // iOS Simulator có thể dùng 127.0.0.1
    if (Platform.isIOS) {
      const origin = 'http://127.0.0.1:$devPort';
      return ('$origin/api/v1', origin);
    }

    // Desktop / khác
    const origin = 'http://localhost:$devPort';
    return ('$origin/api/v1', origin);
  }

  static void _logResolved() {
    if (kDebugMode) {
      debugPrint('📡 Env[$_resolvedBy]');
      debugPrint('   apiBaseUrl = $_apiBaseUrl');
      debugPrint('   webOrigin  = $_webOrigin');
    }
  }
}
