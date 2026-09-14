import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../core/constants/api_constants.dart';
import 'storage_service.dart';

/// Exception chuẩn cho toàn bộ app - Repository sẽ ném loại này.
///
/// Có sẵn helper:
///   - [errorsFor(field)] → List<String> lỗi validation cho 1 field
///     (vd để bind vào TextFormField trong form Contact).
///   - [isNotFound] / [isValidationError] / [isUnauthorized] để UI
///     render state phù hợp.
class ApiException implements Exception {
  ApiException({
    required this.message,
    this.statusCode,
    this.errors,
  });

  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  // Convenient flags
  bool get isNotFound => statusCode == 404;
  bool get isValidationError => statusCode == 422;
  bool get isUnauthorized => statusCode == 401;
  bool get isServerError => statusCode != null && statusCode! >= 500;
  bool get isNetworkError => statusCode == null;

  /// Trả về danh sách message lỗi validation cho 1 field cụ thể.
  ///
  /// Laravel trả errors dạng `{field_name: ["msg1", "msg2"]}`.
  /// Một số field trong FE là nested (vd `customer.email`) — trong trường
  /// hợp đó Laravel trả `customer.email` như 1 key, còn FE thường dùng
  /// key phẳng (vd `email`). Hàm này check cả 2 dạng.
  List<String> errorsFor(String field) {
    final raw = errors;
    if (raw == null || raw.isEmpty) return const [];

    final out = <String>[];
    final exact = raw[field];
    if (exact is List) {
      out.addAll(exact.map((e) => e.toString()));
    }
    // Check nested (vd `customer.email` khi FE dùng `email`).
    for (final entry in raw.entries) {
      final key = entry.key;
      if (key == field) continue;
      if (key.endsWith('.$field') && entry.value is List) {
        out.addAll((entry.value as List).map((e) => e.toString()));
      }
    }
    return out;
  }

  /// Lấy message lỗi đầu tiên cho field (tiện cho TextFormField.errorText).
  String? firstErrorFor(String field) {
    final list = errorsFor(field);
    return list.isEmpty ? null : list.first;
  }

  @override
  String toString() => 'ApiException($statusCode): $message';
}

/// Service gọi API tập trung - mọi Repository phải dùng qua đây.
///
/// Interceptor tự động gắn:
///   - Authorization: Bearer {token}      (nếu đã login)
///   - Accept-Language: {locale}          (đọc động mỗi request từ storage)
///   - X-Branch-Id: {active_branch_id}    (đọc động mỗi request từ storage)
///
/// Nhờ đọc storage mỗi request (không cache header lúc init), khi user
/// đổi ngôn ngữ / chọn branch khác trong runtime, request tiếp theo sẽ
/// tự động dùng giá trị mới — không cần recreate ApiService.
class ApiService {
  ApiService(this._storage) {
    _dio = Dio(
      BaseOptions(
        baseUrl: ApiConstants.baseUrl,
        connectTimeout: ApiConstants.connectTimeout,
        receiveTimeout: ApiConstants.receiveTimeout,
        headers: {
          ApiConstants.headerAccept: 'application/json',
          ApiConstants.headerContentType: 'application/json',
        },
        responseType: ResponseType.json,
      ),
    );
    _setupInterceptors();
  }

  final StorageService _storage;
  late final Dio _dio;

  void _setupInterceptors() {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          // Authorization
          final token = _storage.getToken();
          if (token != null && token.isNotEmpty) {
            options.headers[ApiConstants.headerAuthorization] =
                '${ApiConstants.bearerPrefix} $token';
          }

          // Accept-Language (đọc động mỗi request)
          final locale = _storage.getLocale();
          options.headers[ApiConstants.headerLocale] =
              (locale != null && locale.isNotEmpty) ? locale : 'vi';

          // X-Branch-Id (đọc động mỗi request)
          final branchId = _storage.getActiveBranchId();
          if (branchId != null) {
            options.headers[ApiConstants.headerBranch] = branchId.toString();
          }

          if (kDebugMode) {
            debugPrint(
              '➡️ ${options.method} ${options.uri}'
              '${options.queryParameters.isEmpty ? '' : ' ?${options.queryParameters}'}',
            );
          }
          handler.next(options);
        },
        onResponse: (response, handler) {
          if (kDebugMode) {
            debugPrint('✅ ${response.statusCode} ${response.requestOptions.uri}');
          }
          handler.next(response);
        },
        onError: (error, handler) {
          if (kDebugMode) {
            debugPrint(
              '❌ ${error.requestOptions.uri} - '
              '${error.response?.statusCode ?? ''} ${error.message}',
            );
          }
          handler.next(error);
        },
      ),
    );
  }

  /// GET
  Future<dynamic> get(
    String path, {
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final res = await _dio.get(path, queryParameters: queryParameters);
      return res.data;
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  /// POST
  Future<dynamic> post(
    String path, {
    Object? data,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final res = await _dio.post(path, data: data, queryParameters: queryParameters);
      return res.data;
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  /// PUT
  Future<dynamic> put(String path, {Object? data}) async {
    try {
      final res = await _dio.put(path, data: data);
      return res.data;
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  /// PATCH
  Future<dynamic> patch(String path, {Object? data}) async {
    try {
      final res = await _dio.patch(path, data: data);
      return res.data;
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  /// DELETE
  Future<dynamic> delete(String path, {Object? data}) async {
    try {
      final res = await _dio.delete(path, data: data);
      return res.data;
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  /// Upload file (multipart)
  Future<dynamic> upload(
    String path, {
    required FormData formData,
  }) async {
    try {
      final res = await _dio.post(
        path,
        data: formData,
        options: Options(
          headers: {'Content-Type': 'multipart/form-data'},
        ),
      );
      return res.data;
    } on DioException catch (e) {
      throw _toApiException(e);
    }
  }

  ApiException _toApiException(DioException e) {
    final res = e.response;
    final data = res?.data;
    String message = 'Có lỗi xảy ra, vui lòng thử lại';
    Map<String, dynamic>? errors;

    if (data is Map<String, dynamic>) {
      message = data['message'] as String? ?? message;
      if (data['errors'] is Map) {
        errors = Map<String, dynamic>.from(data['errors'] as Map);
      }
    } else if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout) {
      message = 'Kết nối quá chậm, vui lòng thử lại';
    } else if (e.type == DioExceptionType.connectionError) {
      message = 'Không thể kết nối máy chủ';
    } else if (e.type == DioExceptionType.cancel) {
      message = 'Yêu cầu đã bị hủy';
    }

    return ApiException(
      message: message,
      statusCode: res?.statusCode,
      errors: errors,
    );
  }
}
