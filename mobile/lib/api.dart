import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class AuthResponse {
  AuthResponse({
    required this.token,
    required this.type,
    required this.volunteer,
  });

  final String token;
  final String type;
  final Map<String, dynamic> volunteer;

  factory AuthResponse.fromJson(Map<String, dynamic> json) {
    return AuthResponse(
      token: json['token']?.toString() ?? '',
      type: json['type']?.toString() ?? 'volunteer',
      volunteer: Map<String, dynamic>.from(json['volunteer'] as Map),
    );
  }
}

class MobileApi {
  MobileApi(this.baseUrl);

  final String baseUrl;

  Uri _uri(String path, [Map<String, String>? queryParameters]) {
    final root = Uri.parse(baseUrl.endsWith('/') ? baseUrl : '$baseUrl/');
    final resolved = root.resolve(path.startsWith('/') ? path.substring(1) : path);
    return queryParameters == null ? resolved : resolved.replace(queryParameters: queryParameters);
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

    late final http.StreamedResponse streamed;
    try {
      streamed = await request.send();
    } on SocketException {
      throw ApiException('Unable to reach the backend at $baseUrl. Check the API URL and your network connection.');
    } on HandshakeException {
      throw ApiException('TLS handshake failed for $baseUrl. Check the HTTPS certificate or try the HTTP backend URL.');
    }

    final raw = await streamed.stream.bytesToString();
    Map<String, dynamic>? data;
    if (raw.isNotEmpty) {
      final decoded = jsonDecode(raw);
      if (decoded is Map<String, dynamic>) {
        data = decoded;
      }
    }

    if (streamed.statusCode >= 400) {
      throw ApiException(
        data?['message']?.toString() ?? 'Request failed with status ${streamed.statusCode}.',
        statusCode: streamed.statusCode,
      );
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
      body: {
        'identifier': identifier,
        'username': identifier,
        'password': password,
      },
    );
    return AuthResponse.fromJson(Map<String, dynamic>.from(data['data'] as Map));
  }

  Future<AuthResponse> refresh(String token) async {
    final data = await _request('/mobile/refresh', method: 'POST', token: token);
    return AuthResponse.fromJson(Map<String, dynamic>.from(data['data'] as Map));
  }

  Future<void> logout(String token) async {
    await _request('/mobile/logout', method: 'POST', token: token);
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

  Future<List<Map<String, dynamic>>> adminActivities(String token) async {
    final data = await _request('/mobile/admin/activities', token: token);
    return (data['data'] as List<dynamic>).map((item) => Map<String, dynamic>.from(item as Map)).toList();
  }

  Future<void> deleteAdminActivity({required String token, required int id}) async {
    await _request('/mobile/admin/activities/$id', method: 'DELETE', token: token);
  }

  Future<List<Map<String, dynamic>>> adminVolunteers(String token) async {
    final data = await _request('/mobile/admin/volunteers', token: token);
    return (data['data'] as List<dynamic>).map((item) => Map<String, dynamic>.from(item as Map)).toList();
  }

  Future<void> deleteAdminVolunteer({required String token, required int id}) async {
    await _request('/mobile/admin/volunteers/$id', method: 'DELETE', token: token);
  }

  Future<List<Map<String, dynamic>>> adminRequests(String token) async {
    final data = await _request('/mobile/admin/requests', token: token);
    return (data['data'] as List<dynamic>).map((item) => Map<String, dynamic>.from(item as Map)).toList();
  }

  Future<List<Map<String, dynamic>>> notifications(String token) async {
    final data = await _request('/mobile/notifications', token: token);
    return (data['data'] as List<dynamic>)
        .map((item) => Map<String, dynamic>.from(item as Map))
        .toList();
  }

  Future<List<Map<String, dynamic>>> news(String token) async {
    final data = await _request('/mobile/news', token: token);
    return (data['data'] as List<dynamic>).map((item) => Map<String, dynamic>.from(item as Map)).toList();
  }

  Future<void> createNews({
    required String token,
    required String name,
    required String postDate,
    required String content,
    int? activityId,
  }) async {
    await _request(
      '/mobile/admin/news',
      method: 'POST',
      token: token,
      body: {
        'name': name,
        'post_date': postDate,
        'post_content': content,
        'activity_id': activityId ?? 0,
      },
    );
  }

  Future<void> updateRequestStatus({
    required String token,
    required int id,
    required int status,
  }) async {
    await _request(
      '/mobile/admin/requests/status',
      method: 'POST',
      token: token,
      body: {'id': id, 'status': status},
    );
  }

  Future<void> updateCertificate({
    required String token,
    required int id,
    required String type,
    required bool enabled,
  }) async {
    await _request(
      '/mobile/admin/certificates',
      method: 'POST',
      token: token,
      body: {'id': id, 'type': type, 'enabled': enabled},
    );
  }

  String certificateUrl({required String token, required int id, required String type}) {
    return _uri('/mobile/certificates/$id/$type', {'token': token}).toString();
  }

  Future<Map<String, dynamic>> activity({
    required String token,
    required int id,
  }) async {
    final data = await _request('/mobile/activities/$id', token: token);
    return Map<String, dynamic>.from(data['data'] as Map);
  }

  Future<List<Map<String, dynamic>>> myActivities(String token) async {
    final data = await _request('/mobile/my-activities', token: token);
    return (data['data'] as List<dynamic>).map((item) => Map<String, dynamic>.from(item as Map)).toList();
  }

  Future<void> enroll({
    required String token,
    required int activityId,
  }) async {
    await _request(
      '/mobile/activities/enroll',
      method: 'POST',
      token: token,
      body: {'activity_id': activityId},
    );
  }

  Future<void> unenroll({
    required String token,
    required int activityId,
  }) async {
    await _request(
      '/mobile/activities/unenroll',
      method: 'POST',
      token: token,
      body: {'activity_id': activityId},
    );
  }
}
