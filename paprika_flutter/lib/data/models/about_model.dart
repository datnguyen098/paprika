import 'package:equatable/equatable.dart';

/// Response cho GET /api/v1/about
/// BE trả: { success, message, data: { id, title, subtitle, story, mission,
///   vision, cover_image, team_members[], stats[] } }
class AboutData extends Equatable {
  const AboutData({
    required this.id,
    required this.title,
    required this.subtitle,
    required this.story,
    required this.mission,
    required this.vision,
    required this.coverImage,
    required this.teamMembers,
    required this.stats,
  });

  final int id;
  final String title;
  final String subtitle;
  final String story;
  final String mission;
  final String vision;
  final String coverImage;
  final List<TeamMember> teamMembers;
  final List<AboutStat> stats;

  factory AboutData.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>? ?? {};

    List<T> parseList<T>(
      String key,
      T Function(Map<String, dynamic>) parse,
    ) {
      final raw = data[key];
      if (raw is! List) return const [];
      return raw
          .whereType<Map<String, dynamic>>()
          .map(parse)
          .toList(growable: false);
    }

    return AboutData(
      id: (data['id'] as num?)?.toInt() ?? 0,
      title: data['title'] as String? ?? '',
      subtitle: data['subtitle'] as String? ?? '',
      story: data['story'] as String? ?? '',
      mission: data['mission'] as String? ?? '',
      vision: data['vision'] as String? ?? '',
      coverImage: data['cover_image'] as String? ?? '',
      teamMembers: parseList('team_members', TeamMember.fromJson),
      stats: parseList('stats', AboutStat.fromJson),
    );
  }

  @override
  List<Object?> get props => [id, title, stats.length];
}

/// Thành viên đội ngũ hiển thị trên trang About.
/// BE trả: { name, role, avatar }.
class TeamMember extends Equatable {
  const TeamMember({
    required this.name,
    required this.role,
    required this.avatar,
  });

  final String name;
  final String role;
  final String avatar;

  factory TeamMember.fromJson(Map<String, dynamic> json) => TeamMember(
        name: json['name'] as String? ?? '',
        role: json['role'] as String? ?? '',
        avatar: json['avatar'] as String? ?? '',
      );

  @override
  List<Object?> get props => [name, role];
}

/// Stat dạng (label, value) hiển thị trên About (vd "Chi nhánh", "4").
/// `value` có thể là int (4) hoặc string ("120k+") → để String.
class AboutStat extends Equatable {
  const AboutStat({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  factory AboutStat.fromJson(Map<String, dynamic> json) {
    final raw = json['value'];
    return AboutStat(
      label: json['label'] as String? ?? '',
      value: raw == null ? '' : raw.toString(),
    );
  }

  @override
  List<Object?> get props => [label, value];
}
