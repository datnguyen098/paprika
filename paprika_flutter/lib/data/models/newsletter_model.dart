class NewsletterRequest {
  const NewsletterRequest({required this.email});

  final String email;

  Map<String, dynamic> toJson() => {'email': email.trim()};
}

class NewsletterResponse {
  const NewsletterResponse({
    required this.success,
    required this.message,
  });

  final bool success;
  final String message;

  factory NewsletterResponse.fromJson(Map<String, dynamic> json) {
    return NewsletterResponse(
      success: json['success'] as bool? ?? false,
      message: json['message'] as String? ?? 'Đăng ký nhận tin thành công.',
    );
  }
}
