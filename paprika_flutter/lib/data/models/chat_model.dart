import 'package:equatable/equatable.dart';

class StartChatRequest extends Equatable {
  const StartChatRequest({
    required this.message,
    this.visitorName,
    this.phone,
    this.email,
    this.branchId,
  });

  final String message;
  final String? visitorName;
  final String? phone;
  final String? email;
  final int? branchId;

  Map<String, dynamic> toJson() {
    final out = <String, dynamic>{'message': message.trim()};
    if (visitorName?.trim().isNotEmpty == true) {
      out['visitor_name'] = visitorName!.trim();
    }
    if (phone?.trim().isNotEmpty == true) out['phone'] = phone!.trim();
    if (email?.trim().isNotEmpty == true) out['email'] = email!.trim();
    if (branchId != null) out['branch_id'] = branchId;
    return out;
  }

  Map<String, String> validate() {
    final errors = <String, String>{};
    if (message.trim().isEmpty) {
      errors['message'] = 'Vui lòng nhập nội dung cần hỗ trợ.';
    }
    if (message.trim().length > 1200) {
      errors['message'] = 'Tin nhắn tối đa 1200 ký tự.';
    }
    final typedPhone = phone?.trim() ?? '';
    if (typedPhone.isNotEmpty &&
        !RegExp(r'^[0-9+\-\s().]{8,20}$').hasMatch(typedPhone)) {
      errors['phone'] = 'Số điện thoại chưa đúng định dạng.';
    }
    final typedEmail = email?.trim() ?? '';
    if (typedEmail.isNotEmpty &&
        !RegExp(r'^[\w.\-+]+@[\w\-]+\.[\w\-.]+$').hasMatch(typedEmail)) {
      errors['email'] = 'Email chưa đúng định dạng.';
    }
    return errors;
  }

  @override
  List<Object?> get props => [message, visitorName, phone, email, branchId];
}

class ChatSessionResponse extends Equatable {
  const ChatSessionResponse({
    required this.sessionId,
    required this.messages,
    this.branchId,
    this.status,
  });

  final String sessionId;
  final int? branchId;
  final String? status;
  final List<ChatMessage> messages;

  factory ChatSessionResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? json;
    final rawMessages = data['messages'];
    return ChatSessionResponse(
      sessionId: data['session_id']?.toString() ?? '',
      branchId: data['branch_id'] is int
          ? data['branch_id'] as int
          : int.tryParse(data['branch_id']?.toString() ?? ''),
      status: data['status'] as String?,
      messages: rawMessages is List
          ? rawMessages
              .whereType<Map>()
              .map((item) => ChatMessage.fromJson(
                    Map<String, dynamic>.from(item),
                  ))
              .toList()
          : const [],
    );
  }

  @override
  List<Object?> get props => [sessionId, branchId, status, messages];
}

class ChatMessage extends Equatable {
  const ChatMessage({
    required this.id,
    required this.sender,
    required this.message,
    this.senderName,
    this.createdAt,
  });

  final int id;
  final String sender;
  final String? senderName;
  final String message;
  final String? createdAt;

  bool get isVisitor => sender == 'visitor';

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    return ChatMessage(
      id: json['id'] is int
          ? json['id'] as int
          : int.tryParse('${json['id']}') ?? 0,
      sender: json['sender'] as String? ?? '',
      senderName: json['sender_name'] as String?,
      message: json['message'] as String? ?? '',
      createdAt: json['created_at'] as String?,
    );
  }

  @override
  List<Object?> get props => [id, sender, senderName, message, createdAt];
}
