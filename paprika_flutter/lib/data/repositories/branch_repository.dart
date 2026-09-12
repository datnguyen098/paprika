import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/branch_model.dart';
import '../models/menu_response.dart';

/// Repository cho Branch (chi nhánh) — GET /api/v1/branches,
/// GET /api/v1/branches/{id}
///
/// Mock data 4 chi nhánh. Sau nối DB bảng branches + delivery_zones.
class BranchRepository {
  BranchRepository(this._api);

  final ApiService _api;

  /// Lấy danh sách tất cả chi nhánh active.
  Future<List<Branch>> getBranches() async {
    final json = await _api.get(ApiConstants.branches) as Map<String, dynamic>;
    final list = ListResponse.fromJson(json, Branch.fromJson);
    return list.items;
  }

  /// Lấy chi tiết 1 chi nhánh theo id.
  /// Ném [ApiException] với statusCode=404 nếu không tìm thấy.
  Future<Branch> getBranch(int id) async {
    final json = await _api.get(ApiConstants.branchDetail(id)) as Map<String, dynamic>;
    // Phải parse thủ công vì BE trả {success, message, data} không dùng
    // ListResponse (là single object).
    if (json['success'] == false) {
      throw ApiException(
        message: (json['message'] as String?) ?? 'Không tìm thấy chi nhánh',
        statusCode: 404,
      );
    }
    final data = json['data'] as Map<String, dynamic>?;
    if (data == null) {
      throw ApiException(
        message: 'Không tìm thấy chi nhánh với id = $id',
        statusCode: 404,
      );
    }
    return Branch.fromJson(data);
  }
}
