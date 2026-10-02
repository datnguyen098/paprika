import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../models/gallery_model.dart';

class GalleryRepository {
  GalleryRepository(this._api);

  final ApiService _api;

  Future<GalleryData> getGallery() async {
    final json = await _api.get(ApiConstants.gallery) as Map<String, dynamic>;
    return GalleryData.fromJson(json);
  }
}
