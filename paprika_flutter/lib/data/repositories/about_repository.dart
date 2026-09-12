import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/about_model.dart';

/// Repository cho About — GET /api/v1/about
///
/// Trả về story, mission, vision, team_members, stats.
/// Mock data — sau nối DB bảng pages (slug=gioi-thieu) có translations.
class AboutRepository {
  AboutRepository(this._api);

  final ApiService _api;

  /// Lấy data trang Giới thiệu.
  Future<AboutData> getAbout() async {
    final json = await _api.get(ApiConstants.about) as Map<String, dynamic>;
    return AboutData.fromJson(json);
  }
}
