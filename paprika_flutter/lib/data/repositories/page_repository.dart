import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/menu_response.dart';
import '../models/page_model.dart';

class PageRepository {
  PageRepository(this._api);

  final ApiService _api;

  Future<PagedResponse<CmsPageSummary>> getPages({
    int page = 1,
    int perPage = 20,
  }) async {
    final json = await _api.get(
      ApiConstants.pages,
      queryParameters: {
        'page': page,
        'per_page': perPage,
      },
    ) as Map<String, dynamic>;

    return PagedResponse.fromJson(json, CmsPageSummary.fromJson);
  }

  Future<CmsPageDetail> getPage(String slug) async {
    final json = await _api.get(
      ApiConstants.pageDetail(slug),
    ) as Map<String, dynamic>;
    final data = json['data'] as Map<String, dynamic>?;
    if (data == null) {
      throw ApiException(
        message: json['message'] as String? ?? 'Không tìm thấy trang',
      );
    }
    return CmsPageDetail.fromJson(data);
  }
}
