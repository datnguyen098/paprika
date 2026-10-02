import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/menu_response.dart';
import '../models/post_model.dart';

class PostRepository {
  PostRepository(this._api);

  final ApiService _api;

  Future<PagedResponse<PostSummary>> getPosts({
    int page = 1,
    int perPage = 10,
  }) async {
    final json = await _api.get(
      ApiConstants.posts,
      queryParameters: {
        'page': page,
        'per_page': perPage,
      },
    ) as Map<String, dynamic>;

    return PagedResponse.fromJson(json, PostSummary.fromJson);
  }

  Future<PostDetail> getPost(String slug) async {
    final json = await _api.get(
      ApiConstants.postDetail(Uri.encodeComponent(slug)),
    ) as Map<String, dynamic>;
    final data = json['data'] as Map<String, dynamic>?;
    if (data == null) {
      throw ApiException(
        message: json['message'] as String? ?? 'Không tìm thấy bài viết',
      );
    }
    return PostDetail.fromJson(data);
  }
}
