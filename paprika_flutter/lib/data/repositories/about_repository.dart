import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/about_model.dart';

/// Repository cho About — GET /api/v1/about
///
/// PHP trả: { success, data: { title, content, image } }
/// — đúng 3 trường tồn tại trong bảng `pages`.
class AboutRepository {
  AboutRepository(this._api);

  final ApiService _api;

  /// Lấy data trang Giới thiệu.
  Future<AboutData> getAbout() async {
    final json = await _api.get(ApiConstants.about) as Map<String, dynamic>;
    return AboutData.fromJson(json);
  }
}
