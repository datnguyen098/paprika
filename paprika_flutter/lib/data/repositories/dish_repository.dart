import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/dish_model.dart';
import '../models/menu_response.dart';

/// Repository cho Dish - tất cả endpoint liên quan đến món ăn.
class DishRepository {
  DishRepository(this._api);

  final ApiService _api;

  /// Lấy danh sách món (có filter + pagination).
  /// Tương ứng GET /api/v1/menu
  Future<PagedResponse<Dish>> getMenu({
    int? categoryId,
    String? categorySlug,
    String? search,
    bool? featured,
    int page = 1,
    int perPage = 20,
    String? sort,
    String? dir,
  }) async {
    final query = <String, dynamic>{
      'page': page,
      'per_page': perPage,
      if (categoryId != null) 'category_id': categoryId,
      if (categorySlug != null && categorySlug.isNotEmpty) 'category': categorySlug,
      if (search != null && search.isNotEmpty) 'q': search,
      if (featured == true) 'featured': 1,
      if (sort != null) 'sort': sort,
      if (dir != null) 'dir': dir,
    };

    final json = await _api.get(ApiConstants.menu, queryParameters: query)
        as Map<String, dynamic>;
    return PagedResponse.fromJson(json, Dish.fromJson);
  }

  /// Món nổi bật - GET /api/v1/dishes/featured
  Future<List<Dish>> getFeaturedDishes({int limit = 10}) async {
    final json = await _api.get(
      ApiConstants.featuredDishes,
      queryParameters: {'limit': limit},
    ) as Map<String, dynamic>;
    final list = ListResponse.fromJson(json, Dish.fromJson);
    return list.items;
  }

  /// Chi tiết món - GET /api/v1/dishes/{id}
  Future<DishDetail> getDishDetail(int id) async {
    final json = await _api.get(ApiConstants.dishDetail(id)) as Map<String, dynamic>;
    final res = ApiResponse.fromJson(
      json,
      (data) => DishDetail.fromJson(data as Map<String, dynamic>),
    );
    if (res.data == null) {
      throw Exception('Dish not found');
    }
    return res.data!;
  }

  /// Tìm kiếm - GET /api/v1/dishes/search?q=
  Future<List<Dish>> searchDishes(String keyword) async {
    if (keyword.trim().length < 2) return [];
    final json = await _api.get(
      ApiConstants.dishSearch,
      queryParameters: {'q': keyword},
    ) as Map<String, dynamic>;
    final list = ListResponse.fromJson(json, Dish.fromJson);
    return list.items;
  }
}