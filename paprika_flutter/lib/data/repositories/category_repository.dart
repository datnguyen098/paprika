import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/category_model.dart';
import '../models/menu_response.dart';

/// Repository cho Category - GET /api/v1/categories
class CategoryRepository {
  CategoryRepository(this._api);

  final ApiService _api;

  /// Lấy toàn bộ danh mục active
  Future<List<Category>> getCategories() async {
    final json = await _api.get(ApiConstants.categories) as Map<String, dynamic>;
    final list = ListResponse.fromJson(json, Category.fromJson);
    return list.items;
  }
}