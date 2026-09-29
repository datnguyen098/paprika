import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../providers/providers.dart';
import '../../services/storage_service.dart';
import '../config/locales.dart';

/// State controller cho ngôn ngữ hiện tại của app.
///
/// MIRROR chính xác logic của Laravel:
///   Paprika-main/app/Http/Middleware/SetLocale.php
///   + Paprika-main/bootstrap/app.php (appendToGroup('web', [SetLocale::class]))
///
/// Laravel priority chain (web):
///   1. URL prefix (/en/...) hoặc session/cookie
///   2. Cookie 'locale'
///   3. Accept-Language header
///   4. Default
///
/// Flutter priority chain (vì không có URL prefix):
///   1. Storage 'app_locale' (user đã chọn trước đó)
///   2. Device locale (nếu supported)
///   3. AppLocales.defaultLocale ('vi')
///
/// Khi user tap "VI/EN/EL" trên header → gọi [setLocale] → state update
/// toàn app rebuild + Accept-Language header của next API request update.
class LocaleController extends StateNotifier<Locale> {
  LocaleController(this._storage) : super(_resolveInitial(_storage));

  final StorageService _storage;

  /// Xác định locale khởi đầu theo priority chain (mirror SetLocale middleware).
  static Locale _resolveInitial(StorageService s) {
    // 1. User preference đã lưu
    final stored = s.getLocale();
    if (AppLocales.isSupported(stored)) {
      return Locale(stored!);
    }

    // 2. Device locale
    final deviceCode = WidgetsBinding.instance.platformDispatcher.locale.languageCode;
    if (AppLocales.isSupported(deviceCode)) {
      return Locale(deviceCode);
    }

    // 3. Default
    return const Locale(AppLocales.defaultLocale);
  }

  /// User chọn locale mới qua UI switcher.
  /// Persist vào storage + emit state mới → toàn app rebuild.
  Future<void> setLocale(String code) async {
    if (!AppLocales.isSupported(code)) return;
    if (state.languageCode == code) return; // No-op
    await _storage.setLocale(code);
    state = Locale(code);
  }

  /// Reset về device locale (tiện cho "auto" mode nếu user muốn).
  Future<void> resetToDevice() async {
    final deviceCode = WidgetsBinding.instance.platformDispatcher.locale.languageCode;
    final target = AppLocales.isSupported(deviceCode)
        ? deviceCode
        : AppLocales.defaultLocale;
    await _storage.setLocale(null);
    state = Locale(target);
  }
}

/// Riverpod provider. Init ngay khi app khởi động (override lại trong ProviderScope nếu cần).
final localeProvider = StateNotifierProvider<LocaleController, Locale>((ref) {
  return LocaleController(ref.read(storageServiceProvider));
});
