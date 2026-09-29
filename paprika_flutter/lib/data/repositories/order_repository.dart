import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/order_model.dart';

class OrderRepository {
  OrderRepository(this._api);

  final ApiService _api;

  Future<OrderResponse> createOrder(CreateOrderRequest request) async {
    final json = await _api.post(
      ApiConstants.orders,
      data: request.toJson(),
    ) as Map<String, dynamic>;

    final data = json['data'] as Map<String, dynamic>?;
    if (data == null) {
      throw ApiException(
        message: json['message'] as String? ?? 'Không tạo được đơn hàng',
      );
    }

    return OrderResponse.fromJson(data);
  }
}
