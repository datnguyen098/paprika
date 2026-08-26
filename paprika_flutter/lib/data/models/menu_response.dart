import 'dish_model.dart';

/// Response chuẩn từ BE API (mọi endpoint đều trả về cấu trúc này).
class ApiResponse<T> {
  ApiResponse({
    required this.success,
    required this.message,
    this.data,
  });

  final bool success;
  final String message;
  final T? data;

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic) parseData,
  ) {
    return ApiResponse(
      success: json['success'] as bool? ?? false,
      message: json['message'] as String? ?? '',
      data: json['data'] != null ? parseData(json['data']) : null,
    );
  }
}

/// Response phân trang (dùng cho menu).
class PagedResponse<T> {
  PagedResponse({
    required this.items,
    required this.meta,
  });

  final List<T> items;
  final PaginationMeta meta;

  factory PagedResponse.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) parseItem,
  ) {
    return PagedResponse(
      items: (json['data'] as List<dynamic>? ?? [])
          .map((e) => parseItem(e as Map<String, dynamic>))
          .toList(),
      meta: PaginationMeta.fromJson(json['meta'] as Map<String, dynamic>? ?? {}),
    );
  }

  bool get hasNextPage => meta.currentPage < meta.lastPage;
}

class PaginationMeta {
  PaginationMeta({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  factory PaginationMeta.fromJson(Map<String, dynamic> json) => PaginationMeta(
        currentPage: (json['current_page'] as num?)?.toInt() ?? 1,
        lastPage: (json['last_page'] as num?)?.toInt() ?? 1,
        perPage: (json['per_page'] as num?)?.toInt() ?? 20,
        total: (json['total'] as num?)?.toInt() ?? 0,
      );
}

/// Wrapper cho menu list response - team dùng để parse menu/search.
class MenuResponse {
  PagedResponse<Dish> paged;

  MenuResponse({required this.paged});

  factory MenuResponse.fromJson(Map<String, dynamic> json) {
    return MenuResponse(
      paged: PagedResponse.fromJson(json, Dish.fromJson),
    );
  }
}

/// Wrapper cho list response đơn giản (categories, featured).
class ListResponse<T> {
  final List<T> items;

  ListResponse({required this.items});

  factory ListResponse.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) parseItem,
  ) {
    return ListResponse(
      items: (json['data'] as List<dynamic>? ?? [])
          .map((e) => parseItem(e as Map<String, dynamic>))
          .toList(),
    );
  }
}