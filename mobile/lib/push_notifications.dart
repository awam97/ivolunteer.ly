import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

import 'api.dart';
import 'firebase_options.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  } catch (_) {
    // Firebase may already be initialized by the host process.
  }
}

class PushNotifications {
  PushNotifications._();

  static FirebaseMessaging? _messaging;
  static String? _token;
  static Future<void>? _initialization;

  static String? get token => _token;

  static Future<void> initialize() {
    return _initialization ??= _initializeSafely();
  }

  static Future<void> _initializeSafely() async {
    try {
      if (kIsWeb) return;
      await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
      _messaging = FirebaseMessaging.instance;
      await _messaging!.requestPermission(alert: true, badge: true, sound: true);
      _token = await _messaging!.getToken();
      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
      FirebaseMessaging.onMessage.listen((_) {});
      _messaging!.onTokenRefresh.listen((value) => _token = value);
    } catch (error) {
      debugPrint('Push notification setup skipped: $error');
    }
  }

  static Future<void> registerForUser(MobileApi api, String authToken) async {
    try {
      await initialize();
      final deviceToken = _token ?? await _messaging?.getToken();
      if (deviceToken == null || deviceToken.isEmpty) return;
      _token = deviceToken;
      await api.registerDeviceToken(authToken: authToken, deviceToken: deviceToken);
    } catch (error) {
      debugPrint('Device token registration skipped: $error');
    }
  }

  static Future<void> unregisterForUser(MobileApi api, String authToken) async {
    try {
      final deviceToken = _token;
      if (deviceToken != null && deviceToken.isNotEmpty) {
        await api.unregisterDeviceToken(authToken: authToken, deviceToken: deviceToken);
      }
    } catch (error) {
      debugPrint('Device token removal skipped: $error');
    }
  }
}
