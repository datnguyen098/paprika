import '../constants/api_constants.dart';

/// Helper xử lý URL ảnh trả về từ Laravel BE.
///
/// BE trả `image` dạng relative path (vd `paprika/menu/pho-bo.jpg`,
/// `banners/banner-1.jpg`, `branches/q1.jpg`) kèm theo asset() đã prefix
/// domain, NHƯNG mock controllers trả raw path không prefix. Helper này
/// xử lý cả 3 trường hợp:
///   1. Đã là absolute URL (http/https/data:) → trả nguyên.
///   2. Bắt đầu bằng `/` → trả nguyên (đã là absolute path trên host).
///   3. Relative path khác → ghép với [ApiConstants.webOrigin].
///   4. Null/empty → trả null (Caller tự lo fallback ảnh placeholder).
///
/// Lấy từ Paprika-main/app/helpers.php::media_url() để PHP ↔ Flutter
/// parse ảnh giống nhau.
class ImageHelper {
  ImageHelper._();

  /// Web origin để ghép URL — lấy trực tiếp từ [ApiConstants.webOrigin]
  /// để tránh 2 chỗ cấu hình 2 nơi (image_helper vs api_constants).
  ///
  /// Override trong test bằng cách gán [customJoiner] = (path) => "...".
  static String Function(String) customJoiner = _defaultJoiner;

  /// Các prefix BE dùng làm namespace cho ảnh (server Laravel expose
  /// qua route `/api/v1/images/{path}` — xem routes/api.php).
  static const List<String> _serverPrefixes = [
    'paprika/',
    'images/',
    'uploads/',
  ];

  static String _defaultJoiner(String path) {
    // Nếu path thuộc 1 trong các prefix BE serve → ghép qua endpoint images.
    for (final p in _serverPrefixes) {
      if (path.startsWith(p)) {
        return '${ApiConstants.webOrigin}/api/v1/images/$path';
      }
    }

    // Nếu đã là `/storage/...` → ghép thẳng (ảnh public storage).
    if (path.startsWith('/')) {
      return '${ApiConstants.webOrigin}$path';
    }

    // Fallback: relative khác (vd `banners/x.jpg` cũ) → ghép đơn giản.
    return '${ApiConstants.webOrigin}/$path';
  }

  /// Ghép URL đầy đủ từ relative path BE trả về.
  /// Trả về null nếu path null/rỗng.
  static String? url(String? path) {
    if (path == null || path.isEmpty) return null;

    // Đã là URL đầy đủ (http/https) hoặc data URI (svg inline).
    if (path.startsWith('http://') ||
        path.startsWith('https://') ||
        path.startsWith('data:')) {
      return path;
    }

    // Cả 3 dạng (`/foo`, `foo/bar`, `paprika/foo`) đều do
    // [_defaultJoiner] lo.
    return customJoiner(path);
  }

  /// Variant ảnh theo kích thước (vd thumb/card/large) — tương ứng với
  /// helper `media_variant_path` trong PHP. Hiện tại BE chưa generate
  /// variant cho dish image, nên trả về url gốc.
  /// TODO: Khi BE thêm variant thì check `*-{size}.webp` tồn tại.
  static String? variant(String? path, {String size = 'card'}) => url(path);
}
