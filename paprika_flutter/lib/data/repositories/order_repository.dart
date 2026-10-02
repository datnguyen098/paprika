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

  Future<DeliveryQuoteResponse> previewDeliveryQuote(
    DeliveryQuoteRequest request,
  ) async {
    final json = await _api.post(
      ApiConstants.orderDeliveryQuote,
      data: request.toJson(),
    ) as Map<String, dynamic>;

    return DeliveryQuoteResponse.fromJson(json);
  }

  Future<List<AddressSuggestion>> suggestAddresses({
    required String query,
    int? branchId,
  }) async {
    final json = await _api.get(
      ApiConstants.orderAddressSuggest,
      queryParameters: {
        'q': query.trim(),
        if (branchId != null) 'branch_id': branchId,
      },
    ) as Map<String, dynamic>;

    final raw = json['suggestions'] as List? ?? const [];
    return raw
        .whereType<Map>()
        .map((item) => AddressSuggestion.fromJson(
              Map<String, dynamic>.from(item),
            ))
        .where((item) => item.formatted.isNotEmpty)
        .toList(growable: false);
  }

  Future<AddressReverseResponse> reverseAddress({
    required double latitude,
    required double longitude,
  }) async {
    final json = await _api.post(
      ApiConstants.orderAddressReverse,
      data: {
        'latitude': latitude,
        'longitude': longitude,
      },
    ) as Map<String, dynamic>;

    return AddressReverseResponse.fromJson(json);
  }

  Future<OrderAvailabilityResponse> previewAvailability(
    OrderAvailabilityRequest request,
  ) async {
    final json = await _api.post(
      ApiConstants.orderAvailability,
      data: request.toJson(),
    ) as Map<String, dynamic>;

    return OrderAvailabilityResponse.fromJson(json);
  }

  Future<VoucherPreviewResponse> previewVoucher(
    VoucherPreviewRequest request,
  ) async {
    final json = await _api.post(
      ApiConstants.orderVoucher,
      data: request.toJson(),
    ) as Map<String, dynamic>;

    return VoucherPreviewResponse.fromJson(json);
  }

  Future<List<OrderResponse>> lookupOrders(String query) async {
    final json = await _api.get(
      ApiConstants.orderLookup,
      queryParameters: {'query': query.trim()},
    ) as Map<String, dynamic>;

    final data = json['data'] as List? ?? const [];
    return data
        .whereType<Map>()
        .map((item) => OrderResponse.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<OrderResponse> getOrder(String code) async {
    final json = await _api.get(
      ApiConstants.orderDetail(code),
    ) as Map<String, dynamic>;

    final data = json['data'] as Map<String, dynamic>?;
    if (data == null) {
      throw ApiException(
        message: json['message'] as String? ?? 'Không tìm thấy đơn hàng',
      );
    }

    return OrderResponse.fromJson(data);
  }

  Future<OrderResponse> trackOrder(String code) async {
    final json = await _api.get(
      ApiConstants.orderTrack(code),
    ) as Map<String, dynamic>;

    final data = json['data'] as Map<String, dynamic>?;
    final order = data?['order'] as Map<String, dynamic>?;
    if (order == null) {
      throw ApiException(
        message: json['message'] as String? ?? 'Không tìm thấy đơn hàng',
      );
    }

    final timeline = data?['timeline'];
    return OrderResponse.fromJson({
      ...order,
      if (timeline is List) 'timeline': timeline,
    });
  }
}
