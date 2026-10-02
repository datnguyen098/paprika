import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/reservation_model.dart';

class ReservationRepository {
  ReservationRepository(this._api);

  final ApiService _api;

  Future<ReservationAvailabilityResponse> checkAvailability(
    ReservationAvailabilityRequest request,
  ) async {
    final json = await _api.get(
      ApiConstants.reservationAvailability,
      queryParameters: request.toQuery(),
    ) as Map<String, dynamic>;

    return ReservationAvailabilityResponse.fromJson(json);
  }

  Future<ReservationResponse> createReservation(
    CreateReservationRequest request,
  ) async {
    final json = await _api.post(
      ApiConstants.reservations,
      data: request.toJson(),
    ) as Map<String, dynamic>;

    return ReservationResponse.fromJson(json);
  }

  Future<List<ReservationResponse>> lookupReservations(String query) async {
    final json = await _api.get(
      ApiConstants.reservationLookup,
      queryParameters: {'query': query.trim()},
    ) as Map<String, dynamic>;

    final data = json['data'] as List? ?? const [];
    return data
        .whereType<Map>()
        .map((item) =>
            ReservationResponse.fromJson(Map<String, dynamic>.from(item)))
        .toList();
  }

  Future<ReservationResponse> getReservation(int id) async {
    final json = await _api.get(
      ApiConstants.reservationDetail(id),
    ) as Map<String, dynamic>;

    return ReservationResponse.fromJson(json);
  }
}
