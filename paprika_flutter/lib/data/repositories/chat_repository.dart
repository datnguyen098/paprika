import '../../core/constants/api_constants.dart';
import '../../services/api_service.dart';
import '../../services/storage_service.dart';
import '../models/chat_model.dart';

class ChatRepository {
  ChatRepository(this._api, this._storage);

  final ApiService _api;
  final StorageService _storage;

  String? get savedSessionId => _storage.getChatSessionId();

  Future<ChatSessionResponse> start(StartChatRequest request) async {
    final json = await _api.post(
      ApiConstants.chatStart,
      data: request.toJson(),
    ) as Map<String, dynamic>;

    final session = ChatSessionResponse.fromJson(json);
    if (session.sessionId.isNotEmpty) {
      await _storage.setChatSessionId(session.sessionId);
    }
    return session;
  }

  Future<ChatSessionResponse> messages(String sessionId) async {
    final json = await _api.get(
      ApiConstants.chatMessages(sessionId),
    ) as Map<String, dynamic>;
    return ChatSessionResponse.fromJson(json);
  }

  Future<ChatSessionResponse> send({
    required String sessionId,
    required String message,
  }) async {
    final json = await _api.post(
      ApiConstants.chatMessages(sessionId),
      data: {'message': message.trim()},
    ) as Map<String, dynamic>;
    return ChatSessionResponse.fromJson(json);
  }
}
