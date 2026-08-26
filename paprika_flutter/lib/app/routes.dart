import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../screens/home_screen.dart';

/// Định nghĩa tất cả route name & path của app.
/// Dev FE mỗi người tự thêm route của mình vào AppRouter.
class AppRoutes {
  AppRoutes._();

  // ==================== Path constants ====================
  static const String splash = '/';
  static const String onboarding = '/onboarding';
  static const String home = '/home';
  static const String menu = '/menu';
  static const String dishDetail = '/dish/:id';
  static const String search = '/search';
  static const String cart = '/cart';
  static const String checkout = '/checkout';
  static const String orderSuccess = '/order/success';
  static const String orders = '/orders';
  static const String orderDetail = '/orders/:id';
  static const String orderTracking = '/orders/:id/track';
  static const String login = '/login';
  static const String register = '/register';
  static const String forgotPassword = '/forgot-password';
  static const String profile = '/profile';
  static const String editProfile = '/profile/edit';
  static const String addresses = '/profile/addresses';
  static const String branches = '/branches';
  static const String reservation = '/reservation';
  static const String reservations = '/reservations';
  static const String notifications = '/notifications';
  static const String contact = '/contact';

  // ==================== Helper builders ====================
  static String dishDetailPath(int id) => '/dish/$id';
  static String orderDetailPath(int id) => '/orders/$id';
  static String orderTrackingPath(int id) => '/orders/$id/track';
}

/// AppRouter — placeholder cho dev FE thêm screen.
/// Mount header/footer bằng cách import widgets từ lib/widgets/.
class AppRouter {
  AppRouter._();

  static final GoRouter router = GoRouter(
    initialLocation: AppRoutes.home,
    debugLogDiagnostics: false,
    errorBuilder: (context, state) => _ErrorScreen(error: state.error?.toString()),
    routes: [
      // TODO Dev FE: Thêm các route screen của bạn vào đây.
      // Ví dụ:
      //   GoRoute(
      //     path: AppRoutes.cart,
      //     builder: (context, state) => const CartScreen(),
      //   ),
      //
      // Hiện tại chỉ giữ placeholder để app build được. Lead sẽ merge
      // các route từ từng dev sau khi screen được review.

      // Home — mount PaprikaHeader + body placeholder + PaprikaFooter
      GoRoute(
        path: AppRoutes.home,
        builder: (context, state) => const HomeScreen(),
      ),
      GoRoute(
        path: AppRoutes.splash,
        builder: (context, state) => const HomeScreen(),
      ),
    ],
  );
}

class _ErrorScreen extends StatelessWidget {
  const _ErrorScreen({this.error});
  final String? error;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lỗi')),
      body: Center(child: Text(error ?? 'Đã có lỗi xảy ra')),
    );
  }
}