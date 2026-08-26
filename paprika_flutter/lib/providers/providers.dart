import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../data/models/category_model.dart';
import '../data/models/dish_model.dart';
import '../data/models/menu_response.dart';
import '../data/repositories/category_repository.dart';
import '../data/repositories/dish_repository.dart';
import '../services/api_service.dart';
import '../services/storage_service.dart';

// ============================================================
// CORE PROVIDERS - Override trong main.dart bằng overrideWithValue
// ============================================================

/// SharedPreferences instance - phải override trong main.dart sau khi init.
final sharedPrefsProvider = Provider<SharedPreferences>(
  (ref) => throw UnimplementedError('Override sharedPrefsProvider in main.dart'),
);

/// StorageService - singleton wrapper cho SharedPreferences
final storageServiceProvider = Provider<StorageService>((ref) {
  return StorageService(ref.watch(sharedPrefsProvider));
});

/// ApiService - wrapper cho Dio
final apiServiceProvider = Provider<ApiService>((ref) {
  return ApiService(ref.watch(storageServiceProvider));
});

// ============================================================
// REPOSITORY PROVIDERS
// ============================================================

final categoryRepositoryProvider = Provider<CategoryRepository>((ref) {
  return CategoryRepository(ref.watch(apiServiceProvider));
});

final dishRepositoryProvider = Provider<DishRepository>((ref) {
  return DishRepository(ref.watch(apiServiceProvider));
});

// ============================================================
// DATA PROVIDERS - dùng cho UI
// ============================================================

/// Load categories - dùng cho menu screen, filter,...
final categoriesProvider = FutureProvider.autoDispose<List<Category>>((ref) async {
  return ref.watch(categoryRepositoryProvider).getCategories();
});

/// Món nổi bật - dùng cho home screen
final featuredDishesProvider = FutureProvider.autoDispose<List<Dish>>((ref) async {
  return ref.watch(dishRepositoryProvider).getFeaturedDishes(limit: 10);
});

/// Filter cho menu screen
class MenuFilter {
  const MenuFilter({
    this.categoryId,
    this.categorySlug,
    this.search,
    this.featured,
    this.page = 1,
    this.perPage = 20,
  });

  final int? categoryId;
  final String? categorySlug;
  final String? search;
  final bool? featured;
  final int page;
  final int perPage;

  MenuFilter copyWith({
    int? categoryId,
    String? categorySlug,
    String? search,
    bool? featured,
    int? page,
    int? perPage,
    bool clearCategory = false,
    bool clearSearch = false,
  }) {
    return MenuFilter(
      categoryId: clearCategory ? null : (categoryId ?? this.categoryId),
      categorySlug: clearCategory ? null : (categorySlug ?? this.categorySlug),
      search: clearSearch ? null : (search ?? this.search),
      featured: featured ?? this.featured,
      page: page ?? this.page,
      perPage: perPage ?? this.perPage,
    );
  }

  @override
  bool operator ==(Object other) =>
      other is MenuFilter &&
      other.categoryId == categoryId &&
      other.categorySlug == categorySlug &&
      other.search == search &&
      other.featured == featured &&
      other.page == page &&
      other.perPage == perPage;

  @override
  int get hashCode => Object.hash(
        categoryId,
        categorySlug,
        search,
        featured,
        page,
        perPage,
      );
}

/// Load menu theo filter - dùng cho menu screen
final menuProvider = FutureProvider.autoDispose
    .family<PagedResponse<Dish>, MenuFilter>((ref, filter) async {
  return ref.watch(dishRepositoryProvider).getMenu(
        categoryId: filter.categoryId,
        categorySlug: filter.categorySlug,
        search: filter.search,
        featured: filter.featured,
        page: filter.page,
        perPage: filter.perPage,
      );
});

/// Search results
final searchDishesProvider =
    FutureProvider.autoDispose.family<List<Dish>, String>((ref, keyword) async {
  if (keyword.trim().length < 2) return [];
  return ref.watch(dishRepositoryProvider).searchDishes(keyword);
});

/// Chi tiết món
final dishDetailProvider =
    FutureProvider.autoDispose.family<DishDetail, int>((ref, id) async {
  return ref.watch(dishRepositoryProvider).getDishDetail(id);
});

// ============================================================
// AUTH PROVIDERS (placeholder - dev sẽ implement)
// ============================================================

/// Trạng thái đăng nhập - lấy từ token trong StorageService
final isAuthenticatedProvider = Provider<bool>((ref) {
  final storage = ref.watch(storageServiceProvider);
  final token = storage.getToken();
  return token != null && token.isNotEmpty;
});