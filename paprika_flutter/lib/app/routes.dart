import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../screens/about_screen.dart';
import '../screens/branch_detail_screen.dart';
import '../screens/branches_screen.dart';
import '../screens/contact_screen.dart';
import '../screens/home_screen.dart';
import '../screens/reservation_screen.dart';

/// Dia nghĩa tat ca route name & path cua app.
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
  static const String about = '/about';
  static const String branches = '/branches';
  static const String branchDetail = '/branches/:id';
  static const String reservation = '/reservation';
  static const String reservations = '/reservations';
  static const String notifications = '/notifications';
  static const String contact = '/contact';

  // ==================== Helper builders ====================
  static String dishDetailPath(int id) => '/dish/$id';
  static String orderDetailPath(int id) => '/orders/$id';
  static String orderTrackingPath(int id) => '/orders/$id/track';
  static String branchDetailPath(int id) => '/branches/$id';
}

/// AppRouter — tat ca route da co cua app.
class AppRouter {
  AppRouter._();

  static final GoRouter router = GoRouter(
    initialLocation: AppRoutes.home,
    debugLogDiagnostics: false,
    errorBuilder: (context, state) =>
        _ErrorScreen(error: state.error?.toString()),
    routes: [
      // Trang chu
      GoRoute(
        path: AppRoutes.home,
        builder: (context, state) => const HomeScreen(),
      ),
      GoRoute(
        path: AppRoutes.splash,
        builder: (context, state) => const HomeScreen(),
      ),

      // Dat ban
      GoRoute(
        path: AppRoutes.reservation,
        builder: (context, state) => const ReservationScreen(),
      ),

      // Gioi thieu
      GoRoute(
        path: AppRoutes.about,
        builder: (context, state) => const AboutScreen(),
      ),

      // Chi nhanh
      GoRoute(
        path: AppRoutes.branches,
        builder: (context, state) => const BranchesScreen(),
      ),

      // Chi tiet chi nhanh
      GoRoute(
        path: AppRoutes.branchDetail,
        builder: (context, state) {
          final idStr = state.pathParameters['id'];
          final id = int.tryParse(idStr ?? '');
          if (id == null) {
            return const _ErrorScreen(error: 'ID chi nhanh khong hop le');
          }
          return BranchDetailScreen(branchId: id);
        },
      ),

      // Lien he
      GoRoute(
        path: AppRoutes.contact,
        builder: (context, state) => const ContactScreen(),
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
      appBar: AppBar(title: const Text('Loi')),
      body: Center(child: Text(error ?? 'Da co loi xay ra')),
    );
  }
}
