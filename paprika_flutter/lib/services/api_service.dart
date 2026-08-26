import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../core/constants/api_constants.dart';
import 'storage_service.dart';

/// Exception chuẩn cho toàn bộ app - Repository sẽ ném loại này
class ApiException implements Exception {
  ApiException({
    required this.message,
    this.statusCode,
    this.errors,
  });

  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  @override
  String toString() => 'ApiException($statusCode): $message';
}

/// Service gọi API tập trung - mọi Repository phải dùng qua đây.
/// Tự động gắn Authorization header + locale + logging.
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
          ApiConstants.headerLocale: _storage.getLocale() ?? 'vi',
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
          // Auto attach token
          final token = _storage.getToken();
          if (token != null && token.isNotEmpty) {
            options.headers[ApiConstants.headerAuthorization] =
                '${ApiConstants.bearerPrefix} $token';
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