import 'package:flutter/material.dart';

import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import 'coming_soon.dart';

/// Footer 4 cột theo Laravel Blade storefront (`footer.blade.php`).
///
/// Một StatelessWidget thuần — dữ liệu static placeholder.
/// Team BE sẽ thay bằng data thật từ API sau.
class PaprikaFooter extends StatelessWidget {
  const PaprikaFooter({super.key});

  static const _brandName = 'PAPRIKA PATRAS';
  static const _brandDesc =
      'Nhà hàng ẩm thực Việt Nam & món nướng Hy Lạp tại Patras. '
      'Phục vụ phở, bánh mì, nem nướng và các món Hy Lạp truyền thống '
      'trong không gian ấm cúng, nhanh gọn và chỉn chu.';

  static const _hotlineLabel = 'Hotline';
  static const _hotline = '+30 2610 123 456';

  static const _tagline =
      'Hương vị Việt Nam - Tinh hoa Hy Lạp. Đặt bàn hoặc giao tận nơi.';

  static const _exploreTitle = 'Khám phá';
  static const _serviceTitle = 'Dịch vụ';
  static const _newsletterTitle = 'Bản tin';

  static const _openingHours = 'T2-T6: 11:30 - 23:00\nT7-CN: 11:00 - 01:00';
  static const _address = 'Patras, Greece';

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: AppColors.footerBg,
        border: Border(
          top: BorderSide(color: AppColors.accent, width: 8),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _HotlineBand(),
          _MainGrid(),
          const _CopyrightBar(),
        ],
      ),
    );
  }
}

class _HotlineBand extends StatelessWidget {
  const _HotlineBand();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFF042C21),
      padding: const EdgeInsets.symmetric(
        horizontal: AppConstants.spaceMd,
        vertical: AppConstants.spaceMd,
      ),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final isNarrow = constraints.maxWidth < 480;
          return Flex(
            direction: isNarrow ? Axis.vertical : Axis.horizontal,
            crossAxisAlignment:
                isNarrow ? CrossAxisAlignment.center : CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: AppColors.accent.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(
                      Icons.phone,
                      color: AppColors.accent,
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: AppConstants.spaceSm),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'Hotline',
                        style: TextStyle(
                          color: AppColors.sage,
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          letterSpacing: 0.1,
                        ),
                      ),
                      Text(
                        PaprikaFooter._hotline,
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          fontFamily: 'monospace',
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              if (!isNarrow) const Spacer(),
              Padding(
                padding: EdgeInsets.only(
                  left: isNarrow ? 0 : AppConstants.spaceMd,
                  top: isNarrow ? AppConstants.spaceSm : 0,
                ),
                child: Text(
                  PaprikaFooter._tagline,
                  style: TextStyle(
                    color: AppColors.sage,
                    fontSize: 13,
                  ),
                  textAlign: isNarrow ? TextAlign.center : TextAlign.right,
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _MainGrid extends StatelessWidget {
  const _MainGrid();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(AppConstants.spaceMd),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final width = constraints.maxWidth;
          if (width >= 900) {
            return Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: const [
                Expanded(child: _BrandCol()),
                SizedBox(width: AppConstants.spaceMd),
                Expanded(child: _ExploreCol()),
                SizedBox(width: AppConstants.spaceMd),
                Expanded(child: _ServiceCol()),
                SizedBox(width: AppConstants.spaceMd),
                Expanded(child: _NewsletterCol()),
              ],
            );
          } else if (width >= 600) {
            return Wrap(
              spacing: AppConstants.spaceMd,
              runSpacing: AppConstants.spaceLg,
              children: const [
                SizedBox(
                  width: 250,
                  child: _BrandCol(),
                ),
                SizedBox(
                  width: 250,
                  child: _ExploreCol(),
                ),
                SizedBox(
                  width: 250,
                  child: _ServiceCol(),
                ),
                SizedBox(
                  width: 250,
                  child: _NewsletterCol(),
                ),
              ],
            );
          } else {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: const [
                _BrandCol(),
                SizedBox(height: AppConstants.spaceLg),
                _ExploreCol(),
                SizedBox(height: AppConstants.spaceLg),
                _ServiceCol(),
                SizedBox(height: AppConstants.spaceLg),
                _NewsletterCol(),
              ],
            );
          }
        },
      ),
    );
  }
}

class _BrandCol extends StatelessWidget {
  const _BrandCol();

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: AppColors.accent,
                borderRadius: BorderRadius.circular(10),
              ),
              alignment: Alignment.center,
              child: const Icon(
                Icons.local_fire_department,
                color: Colors.white,
                size: 24,
              ),
            ),
            const SizedBox(width: AppConstants.spaceSm),
            Flexible(
              child: Text(
                PaprikaFooter._brandName,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 0.04,
                  fontStyle: FontStyle.italic,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: AppConstants.spaceMd),
        Text(
          PaprikaFooter._brandDesc,
          style: TextStyle(
            color: AppColors.sageLight,
            fontSize: 12,
            height: 1.6,
          ),
        ),
        const SizedBox(height: AppConstants.spaceMd),
      ],
    );
  }
}

class _ExploreCol extends StatelessWidget {
  const _ExploreCol();

  // Mỗi link có feature name riêng để snackbar "đang phát triển" gợi ý rõ hơn.
  static const _links = [
    ('Trang chủ', '/home', null),           // null = không show snackbar (route đang ở đây)
    ('Thực đơn', '/menu', 'Thực đơn'),
    ('Giới thiệu', '/about', 'Giới thiệu'),
    ('Đặt bàn', '/reservation', 'Đặt bàn'),
  ];

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _SectionTitle(title: PaprikaFooter._exploreTitle),
        const SizedBox(height: AppConstants.spaceMd),
        for (final (label, route, feature) in _links) ...[
          _FooterLink(
            label: label,
            route: route,
            onTap: feature == null
                ? null
                : () => ComingSoon.show(context, feature: feature),
          ),
          const SizedBox(height: AppConstants.spaceSm),
        ],
      ],
    );
  }
}

class _ServiceCol extends StatelessWidget {
  const _ServiceCol();

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _SectionTitle(title: PaprikaFooter._serviceTitle),
        const SizedBox(height: AppConstants.spaceMd),
        _ServiceItem(
          icon: Icons.schedule,
          title: 'Giờ mở cửa',
          content: PaprikaFooter._openingHours,
        ),
        const SizedBox(height: AppConstants.spaceSm),
        _ServiceItem(
          icon: Icons.location_on,
          title: 'Địa chỉ',
          content: PaprikaFooter._address,
        ),
        const SizedBox(height: AppConstants.spaceSm),
        _ServiceItem(
          icon: Icons.phone,
          title: 'Hotline',
          content: PaprikaFooter._hotline,
        ),
      ],
    );
  }
}

class _ServiceItem extends StatelessWidget {
  const _ServiceItem({
    required this.icon,
    required this.title,
    required this.content,
  });

  final IconData icon;
  final String title;
  final String content;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, color: AppColors.gold, size: 18),
        const SizedBox(width: AppConstants.spaceSm),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title.toUpperCase(),
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 0.12,
                  height: 1.2,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                content,
                style: TextStyle(
                  color: AppColors.sageLight,
                  fontSize: 12,
                  height: 1.4,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _NewsletterCol extends StatefulWidget {
  const _NewsletterCol();

  @override
  State<_NewsletterCol> createState() => _NewsletterColState();
}

class _NewsletterColState extends State<_NewsletterCol> {
  final _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _submit() {
    // Newsletter endpoint chưa có trong BE - dùng snackbar "đang phát triển".
    ComingSoon.show(context, feature: 'Đăng ký bản tin');
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _SectionTitle(title: PaprikaFooter._newsletterTitle),
        const SizedBox(height: AppConstants.spaceMd),
        Text(
          'Đăng ký nhận ưu đãi đặc biệt và cập nhật từ Paprika Patras.',
          style: TextStyle(
            color: AppColors.sageLight,
            fontSize: 12,
            height: 1.5,
          ),
        ),
        const SizedBox(height: AppConstants.spaceMd),
        TextField(
          controller: _controller,
          style: const TextStyle(color: Colors.white, fontSize: 13),
          decoration: InputDecoration(
            hintText: 'Email của bạn...',
            hintStyle: TextStyle(
              color: Colors.green.shade900.withValues(alpha: 0.6),
              fontSize: 13,
            ),
            filled: true,
            fillColor: const Color(0xFF042C21),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: AppConstants.spaceMd,
              vertical: AppConstants.spaceMd,
            ),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppConstants.radiusSm),
              borderSide: const BorderSide(color: Colors.white10),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppConstants.radiusSm),
              borderSide: const BorderSide(color: Colors.white10),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppConstants.radiusSm),
              borderSide: const BorderSide(color: AppColors.accent),
            ),
            suffixIcon: IconButton(
              onPressed: _submit,
              icon: const Icon(Icons.mail, color: AppColors.accent, size: 20),
            ),
          ),
          onSubmitted: (_) => _submit(),
        ),
      ],
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title.toUpperCase(),
          style: const TextStyle(
            color: Colors.white,
            fontSize: 11,
            fontWeight: FontWeight.w900,
            letterSpacing: 0.16,
          ),
        ),
        const SizedBox(height: 6),
        Container(
          height: 1,
          color: Colors.white.withValues(alpha: 0.1),
        ),
      ],
    );
  }
}

class _FooterLink extends StatelessWidget {
  const _FooterLink({
    required this.label,
    required this.route,
    this.onTap,
  });

  final String label;
  final String route;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final arrow = const Icon(
      Icons.arrow_right,
      color: AppColors.accent,
      size: 16,
    );
    final text = Text(
      label,
      style: TextStyle(
        color: AppColors.sageLight,
        fontSize: 12,
        fontWeight: FontWeight.w700,
        letterSpacing: 0.08,
      ),
    );

    if (onTap == null) {
      // Trang hiện tại - không cần tap.
      return Row(
        mainAxisSize: MainAxisSize.min,
        children: [arrow, const SizedBox(width: 4), text],
      );
    }

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(4),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [arrow, const SizedBox(width: 4), text],
        ),
      ),
    );
  }
}

class _CopyrightBar extends StatelessWidget {
  const _CopyrightBar();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(AppConstants.spaceMd),
      decoration: BoxDecoration(
        border: Border(
          top: BorderSide(color: Colors.white.withValues(alpha: 0.05)),
        ),
      ),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final isNarrow = constraints.maxWidth < 480;
          if (isNarrow) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Text(
                  '© 2026 Paprika Patras. Mọi quyền được bảo lưu.',
                  style: TextStyle(
                    color: AppColors.sageLight.withValues(alpha: 0.5),
                    fontSize: 11,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: AppConstants.spaceSm),
                const _LegalLinks(),
              ],
            );
          }
          return Row(
            children: [
              Text(
                '© 2026 Paprika Patras. Mọi quyền được bảo lưu.',
                style: TextStyle(
                  color: AppColors.sageLight.withValues(alpha: 0.5),
                  fontSize: 11,
                ),
              ),
              const Spacer(),
              const _LegalLinks(),
            ],
          );
        },
      ),
    );
  }
}

class _LegalLinks extends StatelessWidget {
  const _LegalLinks();

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: AppConstants.spaceMd,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: [
        _LegalLink(
          label: 'Liên hệ',
          onTap: () => ComingSoon.show(context, feature: 'Liên hệ'),
        ),
        _LegalLink(
          label: 'Tra cứu đơn',
          color: AppColors.accent,
          bold: true,
          onTap: () => ComingSoon.show(context, feature: 'Tra cứu đơn'),
        ),
      ],
    );
  }
}

class _LegalLink extends StatelessWidget {
  const _LegalLink({
    required this.label,
    this.color,
    this.bold = false,
    this.onTap,
  });

  final String label;
  final Color? color;
  final bool bold;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final style = TextStyle(
      color: color ?? AppColors.sageLight.withValues(alpha: 0.4),
      fontSize: 11,
      fontWeight: bold ? FontWeight.w800 : FontWeight.w400,
    );

    if (onTap == null) {
      return Text(label, style: style);
    }

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(4),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 2, horizontal: 2),
        child: Text(label, style: style),
      ),
    );
  }
}
