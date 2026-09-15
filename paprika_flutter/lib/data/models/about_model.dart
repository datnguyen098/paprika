/// Response cho GET /api/v1/about
/// PHP trả: { success, data: { title, content, image } }
/// — đúng 3 trường tồn tại trong bảng `pages` (DB).
class AboutData {
  const AboutData({
    required this.title,
    required this.content,
    required this.image,
  });

  final String title;
  final String content;
  final String image;

  factory AboutData.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>?;
    if (data == null) {
      return const AboutData(title: '', content: '', image: '');
    }
    return AboutData(
      title:   data['title']   as String? ?? '',
      content: data['content'] as String? ?? '',
      image:   data['image']   as String? ?? '',
    );
  }

  bool get hasData => title.isNotEmpty || content.isNotEmpty || image.isNotEmpty;
}
