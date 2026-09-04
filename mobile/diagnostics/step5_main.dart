import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

void main() => runApp(const DiagnosticApp());

class DiagnosticApp extends StatelessWidget {
  const DiagnosticApp({super.key});

  @override
  Widget build(BuildContext context) => MaterialApp(
        debugShowCheckedModeBanner: false,
        home: Scaffold(
          appBar: AppBar(title: const Text('اختبار الكاميرا')),
          body: MobileScanner(onDetect: (_, __) {}),
        ),
      );
}
