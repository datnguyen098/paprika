import 'package:shared_preferences/shared_preferences.dart';

/// Wrapper cho SharedPreferences - dùng để lưu token, user info, locale, ...
/// Repository/Service khác dùng qua DI (providers).
class StorageService {
  StorageService(this._prefs);

  final SharedPreferences _prefs;

  // ==================== Keys ====================
  static const String _kToken = 'auth_token';
  static const String _kUserId = 'user_id';
  static const String _kUserName = 'user_name';
  static const String _kUserEmail = 'user_email';
  static const String _kUserPhone = 'user_phone';
  static const String _kLocale = 'app_locale';
  static const String _kActiveBranchId = 'active_branch_id';
  static const String _kCartSessionId = 'cart_session_id';
  static const String _kOnboardingDone = 'onboarding_done';

  // ==================== Auth Token ====================
  Future<void> setToken(String? token) async {
    if (token == null || token.isEmpty) {
      await _prefs.remove(_kToken);
      return;
    }
    await _prefs.setString(_kToken, token);
  }

  String? getToken() => _prefs.getString(_kToken);

  Future<void> clearAuth() async {
    await _prefs.remove(_kToken);
    await _prefs.remove(_kUserId);
    await _prefs.remove(_kUserName);
    await _prefs.remove(_kUserEmail);
    await _prefs.remove(_kUserPhone);
  }

  // ==================== User ====================
  Future<void> setUserId(int? id) async {
    if (id == null) {
      await _prefs.remove(_kUserId);
      return;
    }
    await _prefs.setInt(_kUserId, id);
  }

  int? getUserId() => _prefs.getInt(_kUserId);

  Future<void> setUserName(String? name) async {
    if (name == null || name.isEmpty) {
      await _prefs.remove(_kUserName);
      return;
    }
    await _prefs.setString(_kUserName, name);
  }

  String? getUserName() => _prefs.getString(_kUserName);

  Future<void> setUserEmail(String? email) async {
    if (email == null || email.isEmpty) {
      await _prefs.remove(_kUserEmail);
      return;
    }
    await _prefs.setString(_kUserEmail, email);
  }

  String? getUserEmail() => _prefs.getString(_kUserEmail);

  Future<void> setUserPhone(String? phone) async {
    if (phone == null || phone.isEmpty) {
      await _prefs.remove(_kUserPhone);
      return;
    }
    await _prefs.setString(_kUserPhone, phone);
  }

  String? getUserPhone() => _prefs.getString(_kUserPhone);

  // ==================== Locale ====================
  Future<void> setLocale(String? locale) async {
    if (locale == null || locale.isEmpty) {
      await _prefs.remove(_kLocale);
      return;
    }
    await _prefs.setString(_kLocale, locale);
  }

  String? getLocale() => _prefs.getString(_kLocale);

  // ==================== Active Branch ====================
  Future<void> setActiveBranchId(int? id) async {
    if (id == null) {
      await _prefs.remove(_kActiveBranchId);
      return;
    }
    await _prefs.setInt(_kActiveBranchId, id);
  }

  int? getActiveBranchId() => _prefs.getInt(_kActiveBranchId);

  // ==================== Cart Session ====================
  String? getCartSessionId() => _prefs.getString(_kCartSessionId);

  Future<void> setCartSessionId(String? id) async {
    if (id == null || id.isEmpty) {
      await _prefs.remove(_kCartSessionId);
      return;
    }
    await _prefs.setString(_kCartSessionId, id);
  }

  // ==================== Onboarding ====================
  bool getOnboardingDone() => _prefs.getBool(_kOnboardingDone) ?? false;

  Future<void> setOnboardingDone(bool value) async {
    await _prefs.setBool(_kOnboardingDone, value);
  }

  // ==================== Reset ====================
  Future<void> clearAll() async {
    await _prefs.clear();
  }
}