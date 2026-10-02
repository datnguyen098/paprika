import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/voucher_model.dart';

class VoucherRepository {
  VoucherRepository(this._api);

  final ApiService _api;

  Future<List<PublicVoucher>> getPublicVouchers({int? branchId}) async {
    final json = await _api.get(
      ApiConstants.vouchers,
      queryParameters: {
        if (branchId != null) 'branch_id': branchId,
      },
    ) as Map<String, dynamic>;

    final data = json['data'] as List? ?? const [];
    return data
        .whereType<Map>()
        .map((item) => PublicVoucher.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }
}
