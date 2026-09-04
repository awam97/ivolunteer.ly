import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

void main() => runApp(const DiagnosticApp());

class DiagnosticApp extends StatelessWidget {
  const DiagnosticApp({super.key});

  @override
  Widget build(BuildContext context) => MaterialApp(
        debugShowCheckedModeBanner: false,
        home: Scaffold(
          body: Center(
            child: FilledButton(
              onPressed: () => launchUrl(Uri.parse('https://portal.i-volunteer.ly')),
              child: const Text('فتح الرابط'),
            ),
          ),
        ),
      );
}
