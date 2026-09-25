import 'dart:async';

import 'package:flutter/material.dart';

import '../core/constants/app_colors.dart';

/// Page-transition loader — overlay full-screen hiển thị giữa các lần
/// navigate. Replicate y chang `page-transition-loader` của PHP ở:
///   - View : `Paprika-main/resources/views/storefront/layouts/app.blade.php`
///   - CSS  : `Paprika-main/public/storefront/template.css`
///   - JS   : `Paprika-main/public/storefront/storefront.js`
///
/// Cấu trúc (giống PHP):
///   ┌──────────────────────────────────────────┐
///   │ Background: radial-gradient + dark green │
///   │   ┌────────────────────────────────┐     │
///   │   │ Card (cream + border + shadow) │     │
///   │   │   ┌──────┐                     │     │
///   │   │   │ orbit│ (xoay 0.9s)         │     │
///   │   │   │  🔥  │ logo                │     │
///   │   │   └──────┘                     │     │
///   │   │   <strong>Paprika</strong>     │     │
///   │   │   <span>Đang chuẩn bị...</span>│     │
///   │   │   ▰▰▰▰▱▱▱▱  (bar gradient)    │     │
///   │   └────────────────────────────────┘     │
///   │   glow (radial pulse, 2.4s)             │
///   └──────────────────────────────────────────┘
///
/// Sử dụng qua `PageLoaderController` để show/hide từ nhiều nơi (giống
/// `data-page-loader` + JS trong PHP).
class PageTransitionLoader extends StatefulWidget {
  const PageTransitionLoader({
    super.key,
    this.message,
  });

  final String? message;

  @override
  State<PageTransitionLoader> createState() => _PageTransitionLoaderState();
}

class _PageTransitionLoaderState extends State<PageTransitionLoader>
    with TickerProviderStateMixin {
  // 3 controller riêng cho 3 animation loop:
  // - orbit : xoay vòng logo (0.9s linear infinite)
  // - glow  : pulse glow background (2.4s ease-in-out infinite)
  // - bar   : thanh progress di chuyển (1.2s cubic-bezier infinite)
  late final AnimationController _orbitCtrl;
  late final AnimationController _glowCtrl;
  late final AnimationController _barCtrl;

  // Card-in animation: 280ms cubic-bezier một lần khi mount.
  late final AnimationController _cardCtrl;
  late final Animation<double> _cardOpacity;
  late final Animation<Offset> _cardOffset;
  late final Animation<double> _cardScale;

  // Loader-in animation: 180ms một lần (fade backdrop).
  late final AnimationController _backdropCtrl;
  late final Animation<double> _backdropOpacity;

  @override
  void initState() {
    super.initState();
    _orbitCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat();

    _glowCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2400),
    )..repeat(reverse: true);

    _barCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat();

    _backdropCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 180),
      value: 1,
    );
    _backdropOpacity =
        CurvedAnimation(parent: _backdropCtrl, curve: Curves.easeOut);

    _cardCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 280),
      value: 1,
    );
    final curved = CurvedAnimation(
      parent: _cardCtrl,
      curve: const Cubic(0.16, 1, 0.3, 1),
    );
    _cardOpacity = curved;
    _cardOffset = Tween<Offset>(
      begin: const Offset(0, 0.14),
      end: Offset.zero,
    ).animate(curved);
    _cardScale = Tween<double>(begin: 0.96, end: 1).animate(curved);
  }

  @override
  void dispose() {
    _orbitCtrl.dispose();
    _glowCtrl.dispose();
    _barCtrl.dispose();
    _backdropCtrl.dispose();
    _cardCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Material(
      type: MaterialType.transparency,
      child: FadeTransition(
        opacity: _backdropOpacity,
        child: Stack(
          fit: StackFit.expand,
          children: [
            // ---- Backdrop ----
            // CSS:
            //   background:
            //     radial-gradient(circle at 50% 38%, rgb(255 247 209 / 22%), transparent 22rem),
            //     linear-gradient(135deg, rgb(3 34 25 / 92%), rgb(6 78 59 / 92%) 54%, rgb(47 83 58 / 92%));
            //   backdrop-filter: blur(12px);
            DecoratedBox(
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    Color(0xFF032219), // rgb(3 34 25)
                    Color(0xFF064E3B), // rgb(6 78 59)
                    Color(0xFF2F533A), // rgb(47 83 58)
                  ],
                  stops: [0.0, 0.54, 1.0],
                ),
              ),
              child: Stack(
                children: [
                  // Radial glow phía trên (giống radial-gradient ở 50% 38%)
                  Positioned.fill(
                    child: DecoratedBox(
                      decoration: BoxDecoration(
                        gradient: RadialGradient(
                          center: const Alignment(0, -0.12),
                          radius: 0.65,
                          colors: [
                            const Color(0xFFFFF7D1).withValues(alpha: 0.22),
                            const Color(0xFFFFF7D1).withValues(alpha: 0.0),
                          ],
                        ),
                      ),
                    ),
                  ),
                  // Center content
                  Center(
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 352),
                      child: Padding(
                        padding: const EdgeInsets.all(20),
                        child: _buildCard(),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // ---- Glow dưới đáy (pulse 2.4s) ----
            // CSS: width: min(24rem, 72vw); aspect-ratio: 1;
            //   background: radial-gradient(circle, rgb(213 31 31 / 18%), transparent 68%);
            //   animation: pageLoaderPulse 2.4s ease-in-out infinite;
            Positioned(
              left: 0,
              right: 0,
              bottom: 0,
              child: Align(
                alignment: Alignment.bottomCenter,
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 80),
                  child: SizedBox(
                    width: 280,
                    height: 280,
                    child: AnimatedBuilder(
                      animation: _glowCtrl,
                      builder: (_, __) {
                        final t = _glowCtrl.value;
                        // 0→1: scale 1→1.08, opacity 0.62→0.95
                        final scale = 1.0 + (t * 0.08);
                        final opacity = 0.62 + (t * 0.33);
                        return Transform.scale(
                          scale: scale,
                          child: Opacity(
                            opacity: opacity,
                            child: DecoratedBox(
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                gradient: RadialGradient(
                                  colors: [
                                    const Color(0xFFD51F1F)
                                        .withValues(alpha: 0.18),
                                    const Color(0xFFD51F1F)
                                        .withValues(alpha: 0.0),
                                  ],
                                ),
                                boxShadow: [
                                  BoxShadow(
                                    color: const Color(0xFFD51F1F)
                                        .withValues(alpha: 0.12),
                                    blurRadius: 30,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCard() {
    // CSS:
    //   .page-transition-loader__card
    //     width: min(22rem, calc(100vw - 2rem));
    //     border: 1px solid rgb(255 255 255 / 18%);
    //     border-radius: 1.5rem;
    //     background: linear-gradient(180deg, rgb(255 253 248 / 96%), rgb(250 245 235 / 92%));
    //     padding: 1.65rem 1.45rem 1.35rem;
    //     box-shadow: 0 28px 70px rgb(0 0 0 / 34%), inset 0 1px 0 rgb(255 255 255 / 70%);
    return SlideTransition(
      position: _cardOffset,
      child: ScaleTransition(
        scale: _cardScale,
        child: FadeTransition(
          opacity: _cardOpacity,
          child: Container(
            padding: const EdgeInsets.fromLTRB(23.2, 26.4, 23.2, 21.6),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(24),
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.18),
                width: 1,
              ),
              gradient: const LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Color(0xFFF7F2E8), // rgb(255 253 248) with alpha
                  Color(0xFFFAF5EB), // rgb(250 245 235) with alpha
                ],
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.34),
                  blurRadius: 70,
                  offset: const Offset(0, 28),
                ),
                // Inset top highlight (inset 0 1px 0 white/70%)
                BoxShadow(
                  color: Colors.white.withValues(alpha: 0.7),
                  blurRadius: 0,
                  offset: const Offset(0, -1),
                ),
              ],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                _buildLogoWrap(),
                const SizedBox(height: 16),
                _buildBrand(),
                const SizedBox(height: 20),
                _buildBar(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLogoWrap() {
    // CSS:
    //   .page-transition-loader__logo-wrap
    //     width: 5.4rem; height: 5.4rem;
    //     border-radius: 999px;
    //     background: #ffffff;
    //     box-shadow: 0 16px 38px rgb(6 78 59 / 20%);
    //   .page-transition-loader__orbit
    //     position: absolute; inset: -0.42rem;
    //     border: 2px solid transparent;
    //     border-top-color: #d51f1f;
    //     border-right-color: rgb(245 158 11 / 70%);
    //     animation: pageLoaderOrbit 0.9s linear infinite;
    return SizedBox(
      width: 86.4, // 5.4rem
      height: 86.4,
      child: Stack(
        alignment: Alignment.center,
        children: [
          // Orbit ring (xoay 360°)
          RotationTransition(
            turns: _orbitCtrl,
            child: Container(
              width: 93.84, // 86.4 + 0.42rem * 2 ≈ 86.4 + 6.72 ≈ 93.12
              height: 93.84,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                // Vẽ border-top đỏ + border-right vàng (gradient) bằng
                // cách stack 2 vòng tròn với custom border.
                border: Border(
                  top: BorderSide(
                    color: const Color(0xFFD51F1F), // #d51f1f
                    width: 2,
                  ),
                  right: BorderSide(
                    color: const Color(0xFFF59E0B).withValues(alpha: 0.7),
                    width: 2,
                  ),
                  bottom: BorderSide(
                    color: Colors.transparent,
                    width: 2,
                  ),
                  left: BorderSide(
                    color: Colors.transparent,
                    width: 2,
                  ),
                ),
              ),
            ),
          ),
          // Logo circle trắng
          Container(
            width: 86.4,
            height: 86.4,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: const Color(0xFF064E3B).withValues(alpha: 0.20),
                  blurRadius: 38,
                  offset: const Offset(0, 16),
                ),
              ],
            ),
            padding: const EdgeInsets.all(13),
            child: ClipOval(
              child: Image.asset(
                'assets/images/logo-hs.webp',
                fit: BoxFit.contain,
                // Nếu asset không load được thì fallback icon
                errorBuilder: (_, __, ___) => Icon(
                  Icons.local_fire_department,
                  size: 36,
                  color: AppColors.accent,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBrand() {
    // CSS:
    //   strong: Space Grotesk 800, clamp(1.45rem, 6vw, 2rem), letter-spacing -0.04em
    //   span  : #667132 0.75rem 900 letter-spacing 0.16em uppercase
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(
          'Paprika',
          textAlign: TextAlign.center,
          style: TextStyle(
            fontFamily: 'SpaceGrotesk',
            fontSize: 32, // clamp ~2rem
            fontWeight: FontWeight.w800,
            letterSpacing: -1.28, // -0.04em * 32
            color: const Color(0xFF052E16), // #052e16
            height: 1.1,
          ),
        ),
        const SizedBox(height: 5.6), // 0.35rem
        Text(
          _message.toUpperCase(),
          textAlign: TextAlign.center,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(
            color: Color(0xFF667132),
            fontSize: 12, // 0.75rem
            fontWeight: FontWeight.w900,
            letterSpacing: 2.56, // 0.16em * 16 (CSS rem base) -> scaled
            height: 1.3,
          ),
        ),
      ],
    );
  }

  Widget _buildBar() {
    // CSS:
    //   .bar: width min(14rem, 72vw); height 0.34rem; bg #e7dfcd; border-radius 999
    //   span: width 62%; gradient(90deg, #d51f1f, #f59e0b, #667132);
    //         animation pageLoaderBar 1.2s cubic-bezier(0.65, 0, 0.35, 1) infinite
    //   keyframes:
    //     0%   translateX(-86%) scaleX(0.34)
    //     48%  translateX(-4%)  scaleX(0.82)
    //     100% translateX(112%) scaleX(0.44)
    return ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 224, maxHeight: 5.4),
      child: SizedBox(
        width: 224, // 14rem
        height: 5.44, // 0.34rem
        child: ClipRRect(
          borderRadius: BorderRadius.circular(999),
          child: Container(
            color: const Color(0xFFE7DFCD),
            child: AnimatedBuilder(
              animation: _barCtrl,
              builder: (_, __) {
                final t = _barCtrl.value;
                // Dùng 3-stop piecewise như CSS keyframes
                double translateX;
                double scaleX;
                if (t < 0.48) {
                  final local = t / 0.48;
                  translateX = -0.86 + local * 0.82;
                  scaleX = 0.34 + local * 0.48;
                } else {
                  final local = (t - 0.48) / 0.52;
                  translateX = -0.04 + local * 1.16;
                  scaleX = 0.82 - local * 0.38;
                }
                return Align(
                  alignment: Alignment.centerLeft,
                  child: FractionallySizedBox(
                    widthFactor: 0.62,
                    child: Transform.translate(
                      offset: Offset(translateX * 224, 0),
                      child: Transform.scale(
                        scaleX: scaleX,
                        alignment: Alignment.centerLeft,
                        child: Container(
                          decoration: const BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.centerLeft,
                              end: Alignment.centerRight,
                              colors: [
                                Color(0xFFD51F1F), // #d51f1f
                                Color(0xFFF59E0B), // #f59e0b
                                Color(0xFF667132), // #667132
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
          ),
        ),
      ),
    );
  }

  String get _message =>
      widget.message?.trim().isNotEmpty == true
          ? widget.message!.trim()
          : _defaultMessage();

  String _defaultMessage() {
    switch (Localizations.localeOf(context).languageCode) {
      case 'en':
        return 'Preparing your next page...';
      case 'el':
        return 'Ετοιμάζουμε την επόμενη σελίδα...';
      default:
        return 'Đang chuẩn bị trang tiếp theo...';
    }
  }
}

/// Controller global để show/hide loader từ bất kỳ đâu trong app
/// (giống `data-page-loader` element + JS trong PHP).
///
/// Dùng kiểu Overlay (MaterialApp.router overlay) để KHÔNG cần wrap từng
/// route. Tự tạo `OverlayEntry` show trên cùng Navigator.
class PageLoaderController {
  PageLoaderController._();
  static final PageLoaderController instance = PageLoaderController._();

  OverlayEntry? _entry;
  bool _isVisible = false;
  DateTime? _shownAt;
  Timer? _hideTimer;
  String? _message;

  /// Hiện loader. Nếu đã hiện thì bỏ qua (giống JS: `if (isVisible) return;`).
  void show(BuildContext context, {String? message}) {
    _message = message;
    if (_isVisible) {
      // Cập nhật message nếu có (hiếm khi xảy ra).
      return;
    }
    _isVisible = true;
    _shownAt = DateTime.now();

    final overlay = Overlay.maybeOf(context, rootOverlay: true);
    if (overlay == null) {
      _isVisible = false;
      _shownAt = null;
      _message = null;
      return;
    }

    _entry = OverlayEntry(
      builder: (_) => PageTransitionLoader(
        message: _message,
        // key dùng để rebuild khi message thay đổi
        key: ValueKey('page-loader-${DateTime.now().millisecondsSinceEpoch}'),
      ),
    );
    overlay.insert(_entry!);
  }

  /// Ẩn loader. Đảm bảo loader hiển thị tối thiểu [minShowMs] ms để
  /// tránh flicker (giống UX tốt — không nháy loader).
  Future<void> hide({int minShowMs = 600}) async {
    if (!_isVisible) return;
    _hideTimer?.cancel();

    final shownAt = _shownAt ?? DateTime.now();
    final elapsed = DateTime.now().difference(shownAt).inMilliseconds;
    final wait = (minShowMs - elapsed).clamp(0, minShowMs).toInt();
    if (wait > 0) {
      await Future.delayed(Duration(milliseconds: wait));
    }

    _entry?.remove();
    _entry = null;
    _isVisible = false;
    _shownAt = null;
    _message = null;
  }

  void showForNavigation(BuildContext context, {String? message}) {
    show(context, message: message);
  }

  bool get isVisible => _isVisible;
}

extension PageLoaderNavigation on BuildContext {
  void showPageLoader({String? message}) {
    PageLoaderController.instance.showForNavigation(this, message: message);
  }
}
