import 'package:equatable/equatable.dart';

/// Payload gửi lên POST /api/v1/contact.
///
/// Validation dùng `StoreContactRequest` phía BE:
///   - name: required, string, max 120
///   - email: required, email:rfc
///   - phone: nullable, regex /^[0-9+\-\s().]{8,20}$/
///   - subject: nullable, max 200
///   - message: required, string, min 5
///   - branch_id: nullable, exists(branches, id)
class ContactRequest extends Equatable {
  const ContactRequest({
    required this.name,
    required this.email,
    required this.message,
    this.phone,
    this.subject,
    this.branchId,
  });

  final String name;
  final String email;
  final String message;
  final String? phone;
  final String? subject;
  final int? branchId;

  /// Convert sang JSON map để gửi qua Dio.
  /// Chỉ gửi field có giá trị (bỏ null/empty optional) để tránh validate
  /// fail ở BE khi field optional có rule "nullable" mà Laravel tự convert
  /// empty string sang null.
  Map<String, dynamic> toJson() {
    final out = <String, dynamic>{
      'name': name,
      'email': email,
      'message': message,
    };
    if (phone != null && phone!.isNotEmpty) out['phone'] = phone;
    if (subject != null && subject!.isNotEmpty) out['subject'] = subject;
    if (branchId != null) out['branch_id'] = branchId;
    return out;
  }

  /// Client-side validation trước khi submit — khớp với `StoreContactRequest`
  /// để UX tốt (báo lỗi ngay, không phải đợi round-trip).
  ///
  /// Trả về Map<field, errorMessage> cho các field invalid (rỗng = OK).
  Map<String, String> validate() {
    final errors = <String, String>{};

    if (name.trim().length < 2) {
      errors['name'] = 'Vui lòng nhập họ tên (ít nhất 2 ký tự).';
    } else if (name.length > 120) {
      errors['name'] = 'Họ tên tối đa 120 ký tự.';
    }

    final emailRegex = RegExp(r'^[\w.\-+]+@[\w\-]+\.[\w\-.]+$');
    if (email.isEmpty) {
      errors['email'] = 'Vui lòng nhập email.';
    } else if (!emailRegex.hasMatch(email)) {
      errors['email'] = 'Email chưa đúng định dạng.';
    }

    if (message.trim().length < 5) {
      errors['message'] = 'Nội dung cần ít nhất 5 ký tự.';
    } else if (message.length > 2000) {
      errors['message'] = 'Nội dung tối đa 2000 ký tự.';
    }

    if (phone != null && phone!.isNotEmpty) {
      final phoneRegex = RegExp(r'^[0-9+\-\s().]{8,20}$');
      if (!phoneRegex.hasMatch(phone!)) {
        errors['phone'] = 'Số điện thoại chưa đúng định dạng.';
      }
    }

    if (subject != null && subject!.length > 200) {
      errors['subject'] = 'Tiêu đề tối đa 200 ký tự.';
    }

    return errors;
  }

  bool get isValid => validate().isEmpty;

  @override
  List<Object?> get props => [name, email, phone, subject, message, branchId];
}

/// Response từ POST /api/v1/contact.
/// BE trả: { success, message, data: { id (uuid string), created_at (ISO) } }
class ContactResponse extends Equatable {
  const ContactResponse({
    required this.id,
    required this.createdAt,
  });

  final String id; // UUID string
  final DateTime createdAt;

  factory ContactResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};
    final rawCreated = data['created_at'];
    DateTime parseCreated(Object? raw) {
      if (raw is String && raw.isNotEmpty) {
        return DateTime.tryParse(raw) ?? DateTime.now();
      }
      return DateTime.now();
    }

    return ContactResponse(
      id: data['id']?.toString() ?? '',
      createdAt: parseCreated(rawCreated),
    );
  }

  @override
  List<Object?> get props => [id];
}
