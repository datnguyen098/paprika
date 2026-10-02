import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../screens/about_screen.dart';
import '../screens/allergen_settings_screen.dart';
import '../screens/branch_detail_screen.dart';
import '../screens/branches_screen.dart';
import '../screens/blog_screen.dart';
import '../screens/cart_screen.dart';
import '../screens/checkout_screen.dart';
import '../screens/contact_screen.dart';
import '../screens/dish_detail_screen.dart';
import '../screens/gallery_screen.dart';
import '../screens/home_screen.dart';
import '../screens/menu_screen.dart';
import '../screens/order_detail_screen.dart';
import '../screens/order_success_screen.dart';
import '../screens/orders_screen.dart';
import '../screens/page_detail_screen.dart';
import '../screens/pages_screen.dart';
import '../screens/post_detail_screen.dart';
import '../screens/reservation_screen.dart';
import '../screens/reservations_screen.dart';
import '../screens/search_screen.dart';
import '../screens/vouchers_screen.dart';

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
  static const String blog = '/blog';
  static const String blogDetail = '/blog/:slug';
  static const String gallery = '/gallery';
  static const String pages = '/pages';
  static const String pageDetail = '/pages/:slug';
  static const String cart = '/cart';
  static const String checkout = '/checkout';
  static const String vouchers = '/vouchers';
  static const String orderSuccess = '/order/success/:code';
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
  static const String allergenSettings = '/profile/allergens';

  // ==================== Helper builders ====================
  static String dishDetailPath(int id) => '/dish/$id';
  static String blogDetailPath(String slug) =>
      '/blog/${Uri.encodeComponent(slug)}';
  static String pageDetailPath(String slug) =>
      '/pages/${Uri.encodeComponent(slug)}';
  static String orderSuccessPath(String code) => '/order/success/$code';
  static String orderDetailPath(String code) => '/orders/$code';
  static String orderTrackingPath(String code) => '/orders/$code/track';
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
    // Tick `routeChangeNotifier` mỗi lần navigate để
    // `_RouteLoaderListener` (trong app.dart) show page loader.
    observers: [_RouteTickObserver()],
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
      // Menu — danh sách món, filter theo category, lấy từ API
      GoRoute(
        path: AppRoutes.menu,
        builder: (context, state) => const MenuScreen(),
      ),
      // Chi tiết món ăn - mirror với storefront/menu/show.blade.php
      GoRoute(
        path: AppRoutes.dishDetail,
        builder: (context, state) {
          final idStr = state.pathParameters['id'];
          final id = int.tryParse(idStr ?? '');
          if (id == null) {
            return const _ErrorScreen(error: 'ID món không hợp lệ');
          }
          return DishDetailScreen(dishId: id);
        },
      ),
      GoRoute(
        path: AppRoutes.cart,
        builder: (context, state) => const CartScreen(),
      ),
      GoRoute(
        path: AppRoutes.checkout,
        builder: (context, state) => const CheckoutScreen(),
      ),
      GoRoute(
        path: AppRoutes.vouchers,
        builder: (context, state) => const VouchersScreen(),
      ),
      GoRoute(
        path: AppRoutes.search,
        builder: (context, state) => SearchScreen(
          initialQuery: state.uri.queryParameters['q'] ?? '',
        ),
      ),
      GoRoute(
        path: AppRoutes.blog,
        builder: (context, state) => const BlogScreen(),
      ),
      GoRoute(
        path: AppRoutes.blogDetail,
        builder: (context, state) {
          final slug = state.pathParameters['slug'] ?? '';
          if (slug.isEmpty) {
            return const _ErrorScreen(error: 'Slug bài viết không hợp lệ');
          }
          return PostDetailScreen(slug: slug);
        },
      ),
      GoRoute(
        path: AppRoutes.gallery,
        builder: (context, state) => const GalleryScreen(),
      ),
      GoRoute(
        path: AppRoutes.pages,
        builder: (context, state) => const PagesScreen(),
      ),
      GoRoute(
        path: AppRoutes.pageDetail,
        builder: (context, state) {
          final slug = state.pathParameters['slug'] ?? '';
          if (slug.isEmpty) {
            return const _ErrorScreen(error: 'Slug trang không hợp lệ');
          }
          return PageDetailScreen(slug: slug);
        },
      ),
      GoRoute(
        path: AppRoutes.orderSuccess,
        builder: (context, state) {
          final code = state.pathParameters['code'] ?? '';
          return OrderSuccessScreen(
            code: code,
            invoiceNumber: state.uri.queryParameters['invoice'],
            total: int.tryParse(state.uri.queryParameters['total'] ?? ''),
          );
        },
      ),
      GoRoute(
        path: AppRoutes.orders,
        builder: (context, state) => const OrdersScreen(),
      ),
      GoRoute(
        path: AppRoutes.orderDetail,
        builder: (context, state) {
          final code = state.pathParameters['id'] ?? '';
          if (code.isEmpty) {
            return const _ErrorScreen(error: 'Mã đơn không hợp lệ');
          }
          return OrderDetailScreen(code: code);
        },
      ),
      GoRoute(
        path: AppRoutes.orderTracking,
        builder: (context, state) {
          final code = state.pathParameters['id'] ?? '';
          if (code.isEmpty) {
            return const _ErrorScreen(error: 'Mã đơn không hợp lệ');
          }
          return OrderDetailScreen(code: code, trackingOnly: true);
        },
      ),
      // Reservation — đặt bàn, có form + quick actions
      GoRoute(
        path: AppRoutes.reservation,
        builder: (context, state) => const ReservationScreen(),
      ),
      GoRoute(
        path: AppRoutes.reservations,
        builder: (context, state) => const ReservationsScreen(),
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

      // Quan ly di ung
      GoRoute(
        path: AppRoutes.allergenSettings,
        builder: (context, state) => const AllergenSettingsScreen(),
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

/// Global notifier — mỗi lần route thay đổi sẽ được `GoRouter`
/// (qua [_RouteTickObserver]) tick lên 1. `_RouteLoaderListener`
/// trong `app.dart` lắng nghe notifier này để show page loader.
final ValueNotifier<int> routeChangeNotifier = ValueNotifier<int>(0);

/// GoRouter [NavigatorObserver] — tick [routeChangeNotifier] khi
/// route push/replace hoặc pop. Listener ở `app.dart` sẽ show loader.
class _RouteTickObserver extends NavigatorObserver {
  void _tick() {
    // ValueNotifier tự notify listener; bump value để so sánh !=.
    routeChangeNotifier.value = routeChangeNotifier.value + 1;
  }

  @override
  void didPush(Route route, Route? previousRoute) {
    super.didPush(route, previousRoute);
    _tick();
  }

  @override
  void didReplace({Route? newRoute, Route? oldRoute}) {
    super.didReplace(newRoute: newRoute, oldRoute: oldRoute);
    _tick();
  }

  @override
  void didPop(Route route, Route? previousRoute) {
    super.didPop(route, previousRoute);
    _tick();
  }
}
