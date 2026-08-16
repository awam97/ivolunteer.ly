import 'dart:convert';

import 'package:http/http.dart' as http;

class ApiException implements Exception {
  ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}

class AuthResponse {
  AuthResponse({required this.token, required this.volunteer});

  final String token;
  final Map<String, dynamic> volunteer;

  factory AuthResponse.fromJson(Map<String, dynamic> json) {
    return AuthResponse(
      token: json['token']?.toString() ?? '',
      volunteer: Map<String, dynamic>.from(json['volunteer'] as Map),
    );
  }
}

class MobileApi {
  MobileApi(this.baseUrl);

  final String baseUrl;

  Uri _uri(String path, [Map<String, String>? queryParameters]) {
    final base = baseUrl.endsWith('/') ? baseUrl : '$baseUrl/';
    final cleaned = path.startsWith('/') ? path.substring(1) : path;
    final uri = Uri.parse(base).resolve(cleaned);
    return queryParameters == null ? uri : uri.replace(queryParameters: queryParameters);
  }

  Future<Map<String, dynamic>> _request(
    String path, {
    String method = 'GET',
    String? token,
    Map<String, dynamic>? body,
    Map<String, String>? queryParameters,
  }) async {
    final request = http.Request(method, _uri(path, queryParameters));
    request.headers['Accept'] = 'application/json';
    if (token != null && token.isNotEmpty) {
      request.headers['Authorization'] = 'Bearer $token';
    }
    if (body != null) {
      request.headers['Content-Type'] = 'application/json';
      request.body = jsonEncode(body);
    }

    final streamed = await request.send();
    final raw = await streamed.stream.bytesToString();
    Map<String, dynamic>? data;
    if (raw.isNotEmpty) {
      final decoded = jsonDecode(raw);
      if (decoded is Map<String, dynamic>) {
        data = decoded;
      }
    }

    if (streamed.statusCode >= 400) {
      throw ApiException(data?['message']?.toString() ?? 'Request failed with status ${streamed.statusCode}.');
    }
    if (data == null) {
      throw ApiException('The server returned an empty response.');
    }
    if (data['status'] == 'error') {
      throw ApiException(data['message']?.toString() ?? 'The server rejected the request.');
    }
    return data;
  }

  Future<AuthResponse> login(String identifier, String password) async {
    final data = await _request(
      '/mobile/login',
      method: 'POST',
      body: {'identifier': identifier, 'password': password},
    );
    return AuthResponse.fromJson(Map<String, dynamic>.from(data['data'] as Map));
  }

  Future<Map<String, dynamic>> me(String token) async {
    final data = await _request('/mobile/me', token: token);
    return Map<String, dynamic>.from(data['data'] as Map);
  }

  Future<List<Map<String, dynamic>>> cities() async {
    final data = await _request('/mobile/cities');
    return (data['data'] as List<dynamic>).map((item) => Map<String, dynamic>.from(item as Map)).toList();
  }

  Future<List<Map<String, dynamic>>> activities({
    required String token,
    String? cityId,
    String? search,
  }) async {
    final query = <String, String>{};
    if (cityId != null && cityId.isNotEmpty && cityId != 'all') {
      query['city_id'] = cityId;
    }
    if (search != null && search.trim().isNotEmpty) {
      query['search'] = search.trim();
    }
    final data = await _request('/mobile/activities', token: token, queryParameters: query);
    return (data['data'] as List<dynamic>).map((item) => Map<String, dynamic>.from(item as Map)).toList();
  }

  Future<Map<String, dynamic>> activity({required String token, required int id}) async {
    final data = await _request('/mobile/activities/$id', token: token);
    return Map<String, dynamic>.from(data['data'] as Map);
  }

  Future<List<Map<String, dynamic>>> myActivities(String token) async {
    final data = await _request('/mobile/my-activities', token: token);
    return (data['data'] as List<dynamic>).map((item) => Map<String, dynamic>.from(item as Map)).toList();
  }

  Future<void> enroll({required String token, required int activityId}) async {
    await _request(
      '/mobile/activities/enroll',
      method: 'POST',
      token: token,
      body: {'activity_id': activityId},
    );
  }

  Future<void> unenroll({required String token, required int activityId}) async {
    await _request(
      '/mobile/activities/unenroll',
      method: 'POST',
      token: token,
      body: {'activity_id': activityId},
    );
  }
}
