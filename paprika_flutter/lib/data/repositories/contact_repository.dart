import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/contact_model.dart';

/// Repository cho Contact — POST /api/v1/contact
///
/// Validation: dùng [ContactRequest.validate()] phía client TRƯỚC khi gọi
/// repository để UX tốt (báo lỗi inline không cần round-trip).
///
/// Trả về [ContactResponse] (chứa UUID + createdAt) nếu thành công.
/// Ném [ApiException] (statusCode=422 với errors{} cho lỗi validation)
/// hoặc (statusCode=null cho network error).
class ContactRepository {
  ContactRepository(this._api);

  final ApiService _api;

  /// Gửi form liên hệ.
  ///
  /// Ném [ApiException] nếu:
  ///   - 422: lỗi validation BE (sẽ có errors{} map)
  ///   - null statusCode: network error
  ///   - 500: lỗi server
  Future<ContactResponse> submit(ContactRequest request) async {
    final json = await _api.post(
      ApiConstants.contact,
      data: request.toJson(),
    ) as Map<String, dynamic>;

    return ContactResponse.fromJson(json);
  }
}
