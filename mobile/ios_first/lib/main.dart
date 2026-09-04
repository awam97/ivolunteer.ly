import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:http/http.dart' as http;

const apiBaseUrl = 'https://portal.i-volunteer.ly';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const IVolunteerIosFirstApp());
}

class IVolunteerIosFirstApp extends StatelessWidget {
  const IVolunteerIosFirstApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'أنا متطوع',
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF55751F)),
        scaffoldBackgroundColor: const Color(0xFFF4F6F1),
      ),
      home: const LoginPage(),
    );
  }
}

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final usernameController = TextEditingController();
  final passwordController = TextEditingController();
  bool busy = false;
  bool obscurePassword = true;

  @override
  void dispose() {
    usernameController.dispose();
    passwordController.dispose();
    super.dispose();
  }

  Future<void> login() async {
    FocusScope.of(context).unfocus();
    final username = usernameController.text.trim();
    final password = passwordController.text;
    if (username.isEmpty || password.isEmpty) {
      _showMessage('أدخل اسم المستخدم وكلمة المرور.');
      return;
    }

    setState(() => busy = true);
    try {
      final response = await http.post(
        Uri.parse('$apiBaseUrl/mobile/login'),
        headers: const {'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: jsonEncode({'identifier': username, 'username': username, 'password': password}),
      );
      final data = response.body.isEmpty ? <String, dynamic>{} : jsonDecode(response.body);
      if (response.statusCode >= 400 || data['status'] == 'error') {
        throw Exception(data['message']?.toString() ?? 'تعذر تسجيل الدخول.');
      }
      final payload = Map<String, dynamic>.from(data['data'] as Map? ?? {});
      if ((payload['token']?.toString() ?? '').isEmpty) {
        throw Exception('استجابة تسجيل الدخول غير صالحة.');
      }
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => HomePage(username: username, accountType: payload['type']?.toString() ?? 'volunteer')),
      );
    } catch (error) {
      if (mounted) _showMessage(error.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  void _showMessage(String message) {
    showDialog<void>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('تنبيه'),
        content: Text(message, textDirection: TextDirection.rtl),
        actions: [TextButton(onPressed: () => Navigator.pop(context), child: const Text('حسنًا'))],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        body: SafeArea(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(22, 48, 22, 28),
            children: [
              const Icon(Icons.favorite_rounded, size: 62, color: Color(0xFF55751F)),
              const SizedBox(height: 18),
              const Text('أنا متطوع', textAlign: TextAlign.center, style: TextStyle(fontSize: 32, fontWeight: FontWeight.w900, color: Color(0xFF304300))),
              const SizedBox(height: 8),
              const Text('سجّل دخولك للوصول إلى فرص التطوع', textAlign: TextAlign.center, style: TextStyle(color: Color(0xFF687366))),
              const SizedBox(height: 34),
              Card(
                elevation: 0,
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    children: [
                      TextField(controller: usernameController, textDirection: TextDirection.ltr, decoration: const InputDecoration(labelText: 'اسم المستخدم', prefixIcon: Icon(Icons.person_outline))),
                      const SizedBox(height: 16),
                      TextField(controller: passwordController, obscureText: obscurePassword, textDirection: TextDirection.ltr, decoration: InputDecoration(labelText: 'كلمة المرور', prefixIcon: const Icon(Icons.lock_outline), suffixIcon: IconButton(onPressed: () => setState(() => obscurePassword = !obscurePassword), icon: Icon(obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined)))),
                      const SizedBox(height: 24),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton(onPressed: busy ? null : login, child: busy ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2)) : const Text('تسجيل الدخول', style: TextStyle(fontWeight: FontWeight.w800))),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class HomePage extends StatelessWidget {
  const HomePage({super.key, required this.username, required this.accountType});
  final String username;
  final String accountType;

  @override
  Widget build(BuildContext context) {
    final isAdmin = accountType == 'admin';
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        appBar: AppBar(title: const Text('الرئيسية'), centerTitle: false),
        body: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text('مرحبًا، $username', style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w900, color: Color(0xFF304300))),
            const SizedBox(height: 8),
            Text(isAdmin ? 'لوحة الإدارة جاهزة للتوسع.' : 'اكتشف فرص التطوع وشارك في مجتمعك.', style: const TextStyle(color: Color(0xFF687366))),
            const SizedBox(height: 26),
            _HomeCard(icon: Icons.event_available_rounded, title: 'النشاطات', subtitle: 'سيتم إضافة قائمة النشاطات في المرحلة التالية.'),
            _HomeCard(icon: Icons.notifications_none_rounded, title: 'الإشعارات', subtitle: 'سيتم إضافة الإشعارات بعد تثبيت أساس التطبيق.'),
            _HomeCard(icon: Icons.person_outline_rounded, title: 'الملف الشخصي', subtitle: 'سيتم إضافة الملف الشخصي في مرحلة لاحقة.'),
          ],
        ),
      ),
    );
  }
}

class _HomeCard extends StatelessWidget {
  const _HomeCard({required this.icon, required this.title, required this.subtitle});
  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) => Card(
        elevation: 0,
        child: ListTile(
          contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 8),
          leading: CircleAvatar(backgroundColor: const Color(0xFFE4EDD4), child: Icon(icon, color: const Color(0xFF55751F))),
          title: Text(title, style: const TextStyle(fontWeight: FontWeight.w800)),
          subtitle: Text(subtitle),
        ),
      );
}
