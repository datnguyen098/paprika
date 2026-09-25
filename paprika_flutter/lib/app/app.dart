import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/config/locales.dart';
import '../core/i18n/locale_controller.dart';
import '../l10n/generated/app_localizations.dart';
import '../widgets/page_transition_loader.dart';
import 'routes.dart';
import 'theme/app_theme.dart';

/// Root widget của Paprika Mobile App.
/// Mount trong main.dart bằng ProviderScope.
///
/// Wrap [MaterialApp.router] để:
/// 1. Khi route thay đổi → show page-loader (giống PHP)
/// 2. Khi route mới build xong (post-frame) → hide loader
class PaprikaApp extends ConsumerStatefulWidget {
  const PaprikaApp({super.key});

  @override
  ConsumerState<PaprikaApp> createState() => _PaprikaAppState();
}

class _PaprikaAppState extends ConsumerState<PaprikaApp> {
  @override
  Widget build(BuildContext context) {
    // Locale hiện tại từ LocaleController (mirror SetLocale middleware).
    final currentLocale = ref.watch(localeProvider);

    return MaterialApp.router(
      // App title lấy từ AppLocalizations để khớp ngôn ngữ hiện tại.
      onGenerateTitle: (context) => AppLocalizations.of(context).appTitle,
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      routerConfig: AppRouter.router,
      // Wrap builder để bắt sự kiện route build xong và ẩn loader.
      builder: (context, child) {
        return _RouteLoaderListener(child: child ?? const SizedBox.shrink());
      },
      // Locale - mirror Laravel config/locales.php + SetLocale middleware.
      // Khi user đổi qua header switcher → state update → toàn app rebuild.
      locale: currentLocale,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ],
      supportedLocales: AppLocales.flutterSupported,
    );
  }
}

/// Lắng nghe mỗi lần route thay đổi (qua `routeChangeNotifier`), show
/// loader ngay khi bắt đầu và hide loader sau khi route mới build xong
/// (đợi 2 post-frame + ~450ms để spinner hiện rõ trên màn hình giống
/// page-transition-loader của PHP).
class _RouteLoaderListener extends StatefulWidget {
  const _RouteLoaderListener({required this.child});
  final Widget child;

  @override
  State<_RouteLoaderListener> createState() => _RouteLoaderListenerState();
}

class _RouteLoaderListenerState extends State<_RouteLoaderListener> {
  Timer? _hideTimer;
  int _lastSeenTick = 0;

  @override
  void initState() {
    super.initState();
    _lastSeenTick = routeChangeNotifier.value;
    // Hide loader ngay khi frame đầu build xong (cho initial route).
    SchedulerBinding.instance.addPostFrameCallback((_) {
      _safeHide();
    });
    // Listen global routeChangeNotifier — mỗi lần route đổi → show loader.
    routeChangeNotifier.addListener(_onRouteTick);
  }

  void _onRouteTick() {
    if (!mounted) return;
    // Tránh xử lý cùng 1 tick 2 lần.
    if (routeChangeNotifier.value == _lastSeenTick) return;
    _lastSeenTick = routeChangeNotifier.value;

    _hideTimer?.cancel();

    // Show loader ngay khi route bắt đầu thay đổi.
    PageLoaderController.instance.show(context);

    // Đợi frame MỚI build xong + thêm 1 chút cho mắt thấy được spinner.
    // Frame đầu sau route change vẫn có thể là frame của route CŨ
    // (MaterialApp chưa swap page), nên phải đợi ít nhất 2 frame.
    SchedulerBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      SchedulerBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        // Delay ~450ms để spinner hiện rõ trên màn hình giống
        // page-transition-loader của PHP (~600-800ms).
        _hideTimer = Timer(const Duration(milliseconds: 450), _safeHide);
      });
    });
  }

  void _safeHide() {
    if (!mounted) return;
    PageLoaderController.instance.hide();
  }

  @override
  void dispose() {
    _hideTimer?.cancel();
    routeChangeNotifier.removeListener(_onRouteTick);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
