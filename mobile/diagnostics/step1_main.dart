import 'package:flutter/material.dart';

void main() => runApp(const DiagnosticApp(title: 'مرحلة 1: Flutter فقط'));

class DiagnosticApp extends StatelessWidget {
  const DiagnosticApp({super.key, required this.title});
  final String title;

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      home: Scaffold(
        backgroundColor: const Color(0xFFF4F6F1),
        body: Center(
          child: Text(title, textDirection: TextDirection.rtl, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
        ),
      ),
    );
  }
}
