import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/newsletter_model.dart';

class NewsletterRepository {
  NewsletterRepository(this._api);

  final ApiService _api;

  Future<NewsletterResponse> subscribe(NewsletterRequest request) async {
    final json = await _api.post(
      ApiConstants.newsletter,
      data: request.toJson(),
    ) as Map<String, dynamic>;

    return NewsletterResponse.fromJson(json);
  }
}
