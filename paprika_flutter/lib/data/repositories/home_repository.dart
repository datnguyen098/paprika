import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/home_model.dart';

/// Repository cho Home — GET /api/v1/home
///
/// Trả về banners, categories, featured dishes, testimonials, latest posts,
/// promo popup. Hiện mock data — sau nối DB bảng banners/posts/promotions.
class HomeRepository {
  HomeRepository(this._api);

  final ApiService _api;

  /// Lấy toàn bộ data cho home page.
  Future<HomeData> getHome() async {
    final json = await _api.get(ApiConstants.home) as Map<String, dynamic>;
    return HomeData.fromJson(json);
  }
}
