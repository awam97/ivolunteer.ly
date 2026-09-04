import 'package:flutter/material.dart';

void main() => runApp(const Step2App());

class Step2App extends StatelessWidget {
  const Step2App({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      locale: const Locale('ar'),
      home: Directionality(
        textDirection: TextDirection.rtl,
        child: Scaffold(
          backgroundColor: const Color(0xFFF4F6F1),
          appBar: AppBar(title: const Text('أنا متطوع')),
          body: ListView(
            padding: const EdgeInsets.all(24),
            children: [
              const Text('مرحبًا بك', style: TextStyle(fontSize: 28, fontWeight: FontWeight.w900)),
              const SizedBox(height: 16),
              Card(child: ListTile(leading: const Icon(Icons.event), title: const Text('النشاطات'), subtitle: const Text('استكشف فرص التطوع'))),
              Card(child: ListTile(leading: const Icon(Icons.person), title: const Text('الملف الشخصي'), subtitle: const Text('بيانات حسابك'))),
            ],
          ),
        ),
      ),
    );
  }
}
