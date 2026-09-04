import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  String status = 'تم تشغيل Flutter';
  try {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('diagnostic', 'ok');
    status = 'تم تشغيل التخزين بنجاح';
  } catch (error) {
    status = 'فشل التخزين: $error';
  }
  runApp(DiagnosticApp(status: status));
}

class DiagnosticApp extends StatelessWidget {
  const DiagnosticApp({super.key, required this.status});
  final String status;

  @override
  Widget build(BuildContext context) => MaterialApp(
        debugShowCheckedModeBanner: false,
        home: Scaffold(body: Center(child: Text(status, textDirection: TextDirection.rtl))),
      );
}
