import 'dart:convert';
import 'dart:async';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api.dart';
import 'models.dart';

const String defaultApiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'https://portal.i-volunteer.ly',
);

String normalizeApiBaseUrl(String value) {
  final normalized = value.trim().replaceAll(RegExp(r'/+$'), '');
  if (normalized.isEmpty) {
    throw ApiException('لا يمكن أن يكون رابط API فارغًا.');
  }

  final uri = Uri.tryParse(normalized);
  if (uri == null || (uri.scheme != 'http' && uri.scheme != 'https') || uri.host.isEmpty) {
    throw ApiException('أدخل رابط API صحيحًا يبدأ بـ http:// أو https://');
  }

  return normalized;
}

String brandLogoUrl(String baseUrl) {
  final root = Uri.parse(baseUrl.endsWith('/') ? baseUrl : '$baseUrl/');
  return root.resolve('uploads/logo-color-1.png').toString();
}

void _openShellTab(BuildContext context, int index) {
  Navigator.of(context).pushAndRemoveUntil(
    MaterialPageRoute(builder: (_) => AppShell(initialIndex: index)),
    (route) => false,
  );
}

const TextStyle _pageTitleStyle = TextStyle(
  fontSize: 26,
  fontWeight: FontWeight.w900,
  color: Color(0xFF304300),
  height: 1.15,
  letterSpacing: -0.2,
);

class _FooterTabData {
  const _FooterTabData({
    required this.label,
    this.icon,
    this.avatarUrl,
  });

  final String label;
  final IconData? icon;
  final String? avatarUrl;
}

List<_FooterTabData> _footerTabsForState(AppState? state) {
  final isAdmin = state?.isAdmin ?? false;
  final avatarUrl = state?.volunteer?.imageUrl;
  if (isAdmin) {
    return const [
      _FooterTabData(label: 'الرئيسية', icon: Icons.dashboard_rounded),
      _FooterTabData(label: 'النشاطات', icon: Icons.event_available_rounded),
      _FooterTabData(label: 'المتطوعون', icon: Icons.groups_rounded),
      _FooterTabData(label: 'الطلبات', icon: Icons.inbox_rounded),
    ];
  }

  return [
    const _FooterTabData(label: 'الرئيسية', icon: Icons.home_rounded),
    const _FooterTabData(label: 'المفضلة', icon: Icons.bookmark_rounded),
    const _FooterTabData(label: 'تسجيلاتي', icon: Icons.list_alt_rounded),
    const _FooterTabData(label: 'الإشعارات', icon: Icons.notifications_none_rounded),
    _FooterTabData(label: 'الملف', avatarUrl: avatarUrl),
  ];
}

Widget _buildPageTitle(String text, {TextAlign textAlign = TextAlign.start}) {
  return Text(
    text,
    textAlign: textAlign,
    style: _pageTitleStyle,
  );
}

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('ar');
  runApp(const IVolunteerApp());
}

class IVolunteerApp extends StatelessWidget {
  const IVolunteerApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => AppState(apiBaseUrl: defaultApiBaseUrl)..bootstrap(),
      child: MaterialApp(
        debugShowCheckedModeBanner: false,
        title: 'أنا متطوع',
        locale: const Locale('ar'),
        supportedLocales: const [Locale('ar')],
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        builder: (context, child) {
          return Directionality(
            textDirection: ui.TextDirection.rtl,
            child: child ?? const SizedBox.shrink(),
          );
        },
        theme: ThemeData(
          useMaterial3: true,
          colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF304300)),
          scaffoldBackgroundColor: const Color(0xFFF4F6F1),
          appBarTheme: const AppBarTheme(
            centerTitle: false,
            backgroundColor: Colors.transparent,
            elevation: 0,
            foregroundColor: Color(0xFF304300),
            titleTextStyle: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w900,
              color: Color(0xFF304300),
              letterSpacing: -0.2,
            ),
          ),
        ),
        home: const RootGate(),
      ),
    );
  }
}

class RootGate extends StatelessWidget {
  const RootGate({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<AppState>(
      builder: (context, state, _) {
        if (state.loading) {
          return const SplashScreen();
        }
        if (!state.isSignedIn) {
          return const LoginScreen();
        }
        return const AppShell();
      },
    );
  }
}

class AppState extends ChangeNotifier {
  AppState({required this.apiBaseUrl}) : api = MobileApi(apiBaseUrl);

  String apiBaseUrl;
  MobileApi api;

  bool loading = true;
  String? token;
  String? accountType;
  VolunteerProfile? volunteer;
  MobileStats? stats;
  List<Map<String, dynamic>> cities = [];
  List<ActivityItem> cachedActivities = [];
  Set<int> favoriteActivityIds = {};

  bool get isSignedIn => token != null && token!.isNotEmpty;
  bool get isAdmin => accountType == 'admin';
  bool get isVolunteer => accountType != 'admin';

  Future<void> bootstrap() async {
    final prefs = await SharedPreferences.getInstance();
    token = prefs.getString('token');
    favoriteActivityIds = prefs.getStringList('favorite_activity_ids')?.map((value) => int.tryParse(value) ?? 0).where((value) => value > 0).toSet() ?? {};

    if (token != null) {
      try {
        await refreshSession();
      } catch (_) {
        await signOut(remote: false);
      }
    }

    loading = false;
    notifyListeners();
  }

  void setCachedActivities(List<ActivityItem> items) {
    cachedActivities = items;
    notifyListeners();
  }

  Future<void> toggleFavorite(ActivityItem item) async {
    final updated = Set<int>.from(favoriteActivityIds);
    if (updated.contains(item.id)) {
      updated.remove(item.id);
    } else {
      updated.add(item.id);
    }
    favoriteActivityIds = updated;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList('favorite_activity_ids', updated.map((id) => id.toString()).toList());
    notifyListeners();
  }

  bool isFavorite(int activityId) => favoriteActivityIds.contains(activityId);

  Future<void> signIn(String identifier, String password) async {
    final auth = await api.login(identifier, password);
    token = auth.token;
    accountType = auth.type;
    volunteer = VolunteerProfile.fromJson(auth.volunteer);

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', token!);
    try {
      await refreshProfile();
    } catch (_) {}
    notifyListeners();
  }

  Future<void> refreshProfile() async {
    if (!isSignedIn) return;
    final me = await authorized((currentToken) => api.me(currentToken), allowRefresh: false);
    final volunteerJson = me['volunteer'];
    final statsJson = me['stats'];

    if (volunteerJson is! Map || statsJson is! Map) {
      throw ApiException('استجابة الملف الشخصي من الخادم غير صالحة.');
    }

    volunteer = VolunteerProfile.fromJson(Map<String, dynamic>.from(volunteerJson));
    stats = MobileStats.fromJson(Map<String, dynamic>.from(statsJson));
    final typeValue = me['type'];
    if (typeValue is String && typeValue.isNotEmpty) {
      accountType = typeValue;
    }
    notifyListeners();
  }

  Future<void> refreshSession() async {
    if (!isSignedIn) return;
    final auth = await api.refresh(token!);
    token = auth.token;
    accountType = auth.type;
    volunteer = VolunteerProfile.fromJson(auth.volunteer);

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', token!);
    try {
      await refreshProfile();
    } catch (_) {}
    notifyListeners();
  }

  Future<void> loadCities() async {
    cities = await api.cities();
    notifyListeners();
  }

  Future<void> signOut({bool remote = true}) async {
    final currentToken = token;
    if (remote && currentToken != null && currentToken.isNotEmpty) {
      try {
        await api.logout(currentToken);
      } catch (_) {}
    }

    token = null;
    accountType = null;
    volunteer = null;
    stats = null;
    cities = [];
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    notifyListeners();
  }

  Future<T> authorized<T>(
    Future<T> Function(String token) action, {
    bool allowRefresh = true,
  }) async {
    if (!isSignedIn) {
      throw ApiException('أنت لست مسجّل الدخول.');
    }

    try {
      return await action(token!);
    } on ApiException catch (error) {
      final shouldRefresh = allowRefresh && error.statusCode == 401;
      if (!shouldRefresh) {
        rethrow;
      }

      await refreshSession();
      if (!isSignedIn) {
        rethrow;
      }
      return await action(token!);
    }
  }
}

class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: const Center(child: CircularProgressIndicator()),
      bottomNavigationBar: _FooterNavBar(
        tabs: _footerTabsForState(context.read<AppState>()),
        index: 0,
        onChanged: (_) => _showCenteredPopup(context, 'سجّل الدخول أولاً'),
      ),
    );
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _identifierController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _busy = false;
  String? _errorMessage;

  @override
  void dispose() {
    _identifierController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    final identifier = _identifierController.text.trim();
    final password = _passwordController.text;

    setState(() => _busy = true);
    try {
      _errorMessage = null;
      await context.read<AppState>().signIn(identifier, password);
    } catch (error) {
      setState(() {
        _errorMessage = 'بيانات الدخول غير صحيحة';
      });
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _showError(String message) {
    _showCenteredPopup(context, message);
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();

    return Scaffold(
      body: SafeArea(
        child: Stack(
          children: [
            Positioned(
              top: -80,
              right: -60,
              child: Container(
                width: 220,
                height: 220,
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  color: Color(0xFFE2ECD1),
                ),
              ),
            ),
            if (_errorMessage != null)
              Positioned(
                bottom: 0,
                left: 0,
                right: 0,
                child: Container(
                  height: 34,
                  color: const Color(0xFFE40000),
                  alignment: Alignment.center,
                  child: Text(
                    _errorMessage!,
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
                  ),
                ),
              ),
            Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(20, 24, 20, 44),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      width: 150,
                      height: 150,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.white,
                        boxShadow: const [
                          BoxShadow(
                            color: Color(0x141B2B12),
                            blurRadius: 20,
                            offset: Offset(0, 10),
                          ),
                        ],
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(18),
                        child: ClipOval(
                          child: Image.network(
                            brandLogoUrl(state.apiBaseUrl),
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => Container(
                              color: const Color(0xFF88A64A),
                              alignment: Alignment.center,
                              child: const Text(
                                'أنا\nمتطوع',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 26,
                                  fontWeight: FontWeight.w900,
                                  height: 1.0,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 28),
                    _buildPageTitle('تسجيل الدخول'),
                    const SizedBox(height: 22),
                    Card(
                      elevation: 0,
                      color: Colors.transparent,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 6),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            TextField(
                              controller: _identifierController,
                              textInputAction: TextInputAction.next,
                              textAlign: TextAlign.right,
                              decoration: const InputDecoration(
                                filled: true,
                                fillColor: Colors.white,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.all(Radius.circular(14)),
                                  borderSide: BorderSide(color: Color(0xFFD9D9D9)),
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.all(Radius.circular(14)),
                                  borderSide: BorderSide(color: Color(0xFFD9D9D9)),
                                ),
                                contentPadding: EdgeInsets.symmetric(horizontal: 18, vertical: 16),
                                hintText: 'اسم المستخدم',
                              ),
                            ),
                            const SizedBox(height: 10),
                            TextField(
                              controller: _passwordController,
                              obscureText: _obscurePassword,
                              textAlign: TextAlign.right,
                              onSubmitted: (_) => _submit(),
                              decoration: InputDecoration(
                                filled: true,
                                fillColor: Colors.white,
                                border: const OutlineInputBorder(
                                  borderRadius: BorderRadius.all(Radius.circular(14)),
                                  borderSide: BorderSide(color: Color(0xFFD9D9D9)),
                                ),
                                enabledBorder: const OutlineInputBorder(
                                  borderRadius: BorderRadius.all(Radius.circular(14)),
                                  borderSide: BorderSide(color: Color(0xFFD9D9D9)),
                                ),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
                                hintText: 'كلمة المرور',
                                suffixIcon: IconButton(
                                  onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                                  icon: Icon(_obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                                ),
                              ),
                            ),
                            const SizedBox(height: 18),
                            SizedBox(
                              height: 68,
                              child: FilledButton(
                                onPressed: _busy ? null : _submit,
                                style: FilledButton.styleFrom(
                                  backgroundColor: const Color(0xFF8FAA4F),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                ),
                                child: _busy
                                    ? const SizedBox(
                                        width: 18,
                                        height: 18,
                                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                      )
                                    : const Text(
                                        'تسجيل الدخول',
                                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                                      ),
                              ),
                            ),
                            const SizedBox(height: 14),
                            TextButton(
                              onPressed: () {},
                              child: const Text(
                                'هل نسيت كلمة المرور؟',
                                style: TextStyle(color: Color(0xFFC2C2C2)),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: _FooterNavBar(
        tabs: _footerTabsForState(context.read<AppState>()),
        index: 0,
        onChanged: (_) => _showCenteredPopup(context, 'سجّل الدخول أولاً'),
      ),
    );
  }
}

class _BrandHeader extends StatelessWidget {
  const _BrandHeader();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          width: 84,
          height: 84,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: const [
              BoxShadow(
                color: Color(0x141B2B12),
                blurRadius: 20,
                offset: Offset(0, 10),
              ),
            ],
          ),
          child: const Icon(Icons.favorite_rounded, size: 34, color: Color(0xFF304300)),
        ),
        const SizedBox(height: 14),
        Text(
          'أنا متطوع',
          style: _pageTitleStyle.copyWith(fontSize: 34),
        ),
        const SizedBox(height: 8),
        Text(
          'ابحث عن النشاط التطوعي المناسب وقدّم عليه من هاتفك.',
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: const Color(0xFF607062)),
        ),
      ],
    );
  }
}

class AppShell extends StatefulWidget {
  const AppShell({super.key, this.initialIndex = 0});

  final int initialIndex;

  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  int _index = 0;

  int get currentIndex => _index;

  @override
  void initState() {
    super.initState();
    _index = widget.initialIndex;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final state = context.read<AppState>();
      state.loadCities();
      state.refreshProfile();
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final pages = state.isAdmin
        ? [
            const AdminDashboardScreen(),
            const AdminActivitiesScreen(),
            const AdminVolunteersScreen(),
            const AdminRequestsScreen(),
          ]
        : [
            DiscoverScreen(onOpenActivity: _openActivity),
            const FavoritesScreen(),
            const MyActivitiesScreen(),
            const NotificationsScreen(),
            const ProfileScreen(),
          ];
    final tabs = _footerTabsForState(state);

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 18, 16, 18),
          child: pages[_index],
        ),
      ),
      bottomNavigationBar: _FooterNavBar(
        tabs: tabs,
        index: _index,
        onChanged: (value) => setState(() => _index = value),
      ),
    );
  }

  void _openActivity(int id, String title) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ActivityDetailScreen(
          activityId: id,
          title: title,
          footerIndex: _index,
        ),
      ),
    );
  }
}

class DiscoverScreen extends StatefulWidget {
  const DiscoverScreen({super.key, required this.onOpenActivity});

  final void Function(int id, String title) onOpenActivity;

  @override
  State<DiscoverScreen> createState() => _DiscoverScreenState();
}

class _DiscoverScreenState extends State<DiscoverScreen> {
  final _searchController = TextEditingController();
  Timer? _debounce;
  String _cityId = '';
  String _sortMode = 'recent';
  bool _mapMode = false;
  List<ActivityItem> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      context.read<AppState>().loadCities();
      _load();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _debounce?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    final state = context.read<AppState>();
    if (!state.isSignedIn) return;
    setState(() => _loading = true);
    try {
      final raw = await state.authorized(
        (token) => state.api.activities(
          token: token,
          cityId: _cityId,
          search: _searchController.text,
        ),
      );
      _items = raw.map(ActivityItem.fromJson).toList();
      context.read<AppState>().setCachedActivities(_items);
      await state.refreshProfile();
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showError(String message) {
    _showCenteredPopup(context, message);
  }

  void _onSearchChanged(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), _load);
  }

  List<ActivityItem> _sortedItems(List<ActivityItem> items) {
    final sorted = [...items];
    switch (_sortMode) {
      case 'name':
        sorted.sort((a, b) => a.name.compareTo(b.name));
        break;
      case 'city':
        sorted.sort((a, b) => (a.cityName ?? '').compareTo(b.cityName ?? ''));
        break;
      default:
        sorted.sort((a, b) => (a.dateFrom ?? '').compareTo(b.dateFrom ?? ''));
    }
    return sorted;
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final volunteerName = state.volunteer?.name ?? 'رياض';
    final actions = [
      _DashboardAction(
        label: 'طلبات التطوع',
        icon: Icons.file_download_outlined,
        color: const Color(0xFF5AB56B),
        onTap: () {
          Navigator.of(context).push(MaterialPageRoute(builder: (_) => const MyActivitiesScreen()));
        },
      ),
      _DashboardAction(
        label: 'الشهادات',
        icon: Icons.receipt_long_outlined,
        color: const Color(0xFF5E915F),
        onTap: () => _showCenteredPopup(context, 'قسم الشهادات قيد الإعداد.'),
      ),
      _DashboardAction(
        label: 'الأخبار',
        icon: Icons.chrome_reader_mode_outlined,
        color: const Color(0xFF018B00),
        onTap: () => _showCenteredPopup(context, 'قسم الأخبار قيد الإعداد.'),
      ),
      _DashboardAction(
        label: 'النشاطات',
        icon: Icons.emoji_events_outlined,
        color: const Color(0xFFA4D600),
        onTap: () async {
          await _load();
          if (mounted) {
            _showCenteredPopup(context, 'تم تحديث أحدث الأنشطة.');
          }
        },
      ),
    ];

    return SafeArea(
      child: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(16, 24, 16, 22),
          children: [
            Align(
              alignment: Alignment.centerRight,
              child: _buildPageTitle('مرحباً $volunteerName'),
            ),
            const SizedBox(height: 14),
            _StatsCarousel(stats: state.stats, citiesCount: state.cities.length),
            const SizedBox(height: 18),
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: actions.length,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                mainAxisSpacing: 12,
                crossAxisSpacing: 12,
                childAspectRatio: 1.0,
              ),
              itemBuilder: (context, index) {
                final action = actions[index];
                return _DashboardActionCard(action: action);
              },
            ),
            const SizedBox(height: 18),
            _buildDiscoverControls(state),
            const SizedBox(height: 14),
            if (_mapMode)
              _buildCityMap(state)
            else
              ..._sortedItems(_items)
                  .map(
                    (item) => Padding(
                      padding: const EdgeInsets.only(bottom: 14),
                      child: ActivityCard(
                        item: item,
                        isFavorite: state.isFavorite(item.id),
                        onToggleFavorite: () => state.toggleFavorite(item),
                        onTap: () => widget.onOpenActivity(item.id, item.name),
                      ),
                    ),
                  )
                  .toList(),
          ],
        ),
      ),
    );
  }

  Widget _buildDiscoverControls(AppState state) {
    final cities = state.cities;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Expanded(
              child: SegmentedButton<bool>(
                segments: const [
                  ButtonSegment(value: false, label: Text('قائمة'), icon: Icon(Icons.view_list_rounded)),
                  ButtonSegment(value: true, label: Text('خريطة'), icon: Icon(Icons.map_rounded)),
                ],
                selected: {_mapMode},
                onSelectionChanged: (values) => setState(() => _mapMode = values.first),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: DropdownButtonFormField<String>(
                value: _cityId.isEmpty ? 'all' : _cityId,
                decoration: const InputDecoration(border: OutlineInputBorder()),
                items: [
                  const DropdownMenuItem(value: 'all', child: Text('كل المدن')),
                  ...cities.map(
                    (city) => DropdownMenuItem(
                      value: city['id'].toString(),
                      child: Text(city['name']?.toString() ?? ''),
                    ),
                  ),
                ],
                onChanged: (value) {
                  setState(() => _cityId = value == null || value == 'all' ? '' : value);
                  _load();
                },
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: DropdownButtonFormField<String>(
                value: _sortMode,
                decoration: const InputDecoration(border: OutlineInputBorder()),
                items: const [
                  DropdownMenuItem(value: 'recent', child: Text('الأحدث')),
                  DropdownMenuItem(value: 'name', child: Text('الاسم')),
                  DropdownMenuItem(value: 'city', child: Text('المدينة')),
                ],
                onChanged: (value) => setState(() => _sortMode = value ?? 'recent'),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildCityMap(AppState state) {
    final counts = <String, int>{};
    for (final item in _items) {
      final cityName = item.cityName ?? 'مدينة';
      counts[cityName] = (counts[cityName] ?? 0) + 1;
    }
    final cityCards = counts.entries.toList();
    if (cityCards.isEmpty) {
      return const _EmptyStateCard(
        icon: Icons.map_outlined,
        title: 'لا توجد نشاطات على الخريطة',
        subtitle: 'جرّب تغيير الفلاتر أو البحث عن مدينة أخرى.',
      );
    }

    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: cityCards
          .map(
            (entry) => SizedBox(
              width: 160,
              child: Card(
                elevation: 0,
                color: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(18),
                  side: const BorderSide(color: Color(0xFFE8EFE0)),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(entry.key, style: const TextStyle(fontWeight: FontWeight.w800)),
                      const SizedBox(height: 8),
                      Text('${entry.value} نشاط', style: const TextStyle(color: Color(0xFF607062))),
                    ],
                  ),
                ),
              ),
            ),
          )
          .toList(),
    );
  }
}

class ActivityDetailScreen extends StatefulWidget {
  const ActivityDetailScreen({
    super.key,
    required this.activityId,
    required this.title,
    this.footerIndex = 0,
  });

  final int activityId;
  final String title;
  final int footerIndex;

  @override
  State<ActivityDetailScreen> createState() => _ActivityDetailScreenState();
}

class _ActivityDetailScreenState extends State<ActivityDetailScreen> {
  ActivityItem? _item;
  bool _loading = true;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final state = context.read<AppState>();
      final raw = await state.authorized((token) => state.api.activity(token: token, id: widget.activityId));
      setState(() {
        _item = ActivityItem.fromJson(raw);
      });
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _toggleEnrollment() async {
    final item = _item;
    if (item == null) return;
    setState(() => _busy = true);
    try {
      final state = context.read<AppState>();
      if (item.isEnrolled) {
        await state.authorized((token) => state.api.unenroll(token: token, activityId: item.id));
      } else {
        await state.authorized((token) => state.api.enroll(token: token, activityId: item.id));
      }
      await _load();
      await state.refreshProfile();
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _showError(String message) {
    _showCenteredPopup(context, message);
  }

  @override
  Widget build(BuildContext context) {
    final item = _item;
    return Scaffold(
      appBar: AppBar(title: Text(widget.title, style: _pageTitleStyle.copyWith(fontSize: 20))),
      body: _loading || item == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.fromLTRB(16, 18, 16, 22),
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(28),
                  child: item.imageUrl != null && item.imageUrl!.isNotEmpty
                      ? Image.network(
                          item.imageUrl!,
                          height: 240,
                          width: double.infinity,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => _imageFallback(),
                        )
                      : _imageFallback(),
                ),
                const SizedBox(height: 14),
                Card(
                  elevation: 0,
                  color: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(28),
                    side: const BorderSide(color: Color(0xFFE8EFE0)),
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(18),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(item.name, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900)),
                        const SizedBox(height: 6),
                        Text(item.organisation, style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: const Color(0xFF607062))),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            _MetaChip(label: item.cityName ?? 'مدينة'),
                            _MetaChip(label: _formatDateRange(item.dateFrom, item.dateTo)),
                            _MetaChip(label: item.hours != null ? '${item.hours} ساعة' : 'مرن'),
                            _MetaChip(label: _statusLabel(item.enrollmentStatus)),
                          ],
                        ),
                        const SizedBox(height: 16),
                        Text(item.description.isNotEmpty ? item.description : 'لا يوجد وصف تفصيلي.'),
                        const SizedBox(height: 16),
                        Text('المتطلبات', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                        const SizedBox(height: 6),
                        Text(item.requiredFiles.isNotEmpty ? item.requiredFiles : 'لا توجد متطلبات'),
                        const SizedBox(height: 16),
                        FilledButton(
                          onPressed: _busy ? null : _toggleEnrollment,
                          style: FilledButton.styleFrom(
                            backgroundColor: item.isEnrolled ? const Color(0xFFB45309) : const Color(0xFF304300),
                          ),
                          child: _busy
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(item.isEnrolled ? 'إلغاء الطلب' : 'قدّم الآن'),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
      bottomNavigationBar: _FooterNavBar(
        tabs: _footerTabsForState(context.read<AppState>()),
        index: widget.footerIndex,
        onChanged: (value) => _openShellTab(context, value),
      ),
    );
  }

  Widget _imageFallback() {
    return Container(
      height: 240,
      width: double.infinity,
      color: const Color(0xFFDDE8CF),
      alignment: Alignment.center,
      child: const Icon(Icons.image_outlined, size: 42),
    );
  }
}

class MyActivitiesScreen extends StatefulWidget {
  const MyActivitiesScreen({super.key});

  @override
  State<MyActivitiesScreen> createState() => _MyActivitiesScreenState();
}

class _MyActivitiesScreenState extends State<MyActivitiesScreen> {
  bool _loading = true;
  List<ActivityItem> _latestItems = [];
  List<ActivityItem> _myItems = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    final state = context.read<AppState>();
    if (!state.isSignedIn) return;

    setState(() => _loading = true);
    try {
      final latestRaw = await state.authorized((token) => state.api.activities(token: token));
      final myRaw = await state.authorized((token) => state.api.myActivities(token));
      setState(() {
        _latestItems = latestRaw.map(ActivityItem.fromJson).toList();
        _myItems = myRaw.map(ActivityItem.fromJson).toList();
      });
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showError(String message) {
    _showCenteredPopup(context, message);
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 22),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            _buildPageTitle('الأنشطة'),
            const SizedBox(height: 12),
            Expanded(
              child: DefaultTabController(
                length: 3,
                child: Column(
                  children: [
                    Container(
                      decoration: BoxDecoration(
                        color: const Color(0xFFF0F4E5),
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: const Color(0xFFE2E8D3)),
                      ),
                      child: TabBar(
                        indicatorSize: TabBarIndicatorSize.tab,
                        dividerColor: Colors.transparent,
                        labelColor: Colors.white,
                        unselectedLabelColor: const Color(0xFF5F6B4C),
                        indicator: BoxDecoration(
                          color: const Color(0xFF5B7523),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        tabs: const [
                          Tab(text: 'الأنشطة'),
                          Tab(text: 'تسجيلاتي'),
                          Tab(text: 'شهاداتي'),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),
                    Expanded(
                      child: TabBarView(
                        children: [
                          _buildActivitiesTab(
                            title: 'أحدث الأنشطة',
                            items: _latestItems,
                            emptyTitle: 'لا توجد أنشطة',
                            emptySubtitle: 'جرّب تحديث الصفحة لاحقًا.',
                          ),
                          _buildActivitiesTab(
                            title: 'تسجيلاتي',
                            items: _myItems,
                            emptyTitle: 'لا توجد طلبات بعد',
                            emptySubtitle: 'قدّم على نشاط وسيظهر هنا.',
                          ),
                          _buildCertificatesTab(),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActivitiesTab({
    required String title,
    required List<ActivityItem> items,
    required String emptyTitle,
    required String emptySubtitle,
  }) {
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 8),
        children: [
          if (_loading)
            const Padding(
              padding: EdgeInsets.only(top: 28),
              child: Center(child: CircularProgressIndicator()),
            )
          else if (items.isEmpty)
            _EmptyStateCard(
              icon: title == 'تسجيلاتي' ? Icons.assignment_outlined : Icons.event_busy,
              title: emptyTitle,
              subtitle: emptySubtitle,
            )
          else
            ...items.map(
              (item) => Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: ActivityCard(
                  item: item,
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => ActivityDetailScreen(
                          activityId: item.id,
                          title: item.name,
                          footerIndex: context.findAncestorStateOfType<_AppShellState>()?.currentIndex ?? 1,
                        ),
                      ),
                    );
                  },
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildCertificatesTab() {
    final completedItems = _myItems.where((item) => item.enrollmentStatus == 2).toList();

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.only(bottom: 8),
        children: [
          _SectionCard(
            title: 'ملخص الشهادات',
            child: Row(
              children: [
                Expanded(
                  child: _StatBox(
                    label: 'الجاهزة',
                    value: completedItems.length,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _StatBox(
                    label: 'الإجمالي',
                    value: context.watch<AppState>().stats?.totalCertificates ?? completedItems.length,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          if (_loading)
            const Padding(
              padding: EdgeInsets.only(top: 28),
              child: Center(child: CircularProgressIndicator()),
            )
          else if (completedItems.isEmpty)
            const _EmptyStateCard(
              icon: Icons.workspace_premium_outlined,
              title: 'لا توجد شهادات بعد',
              subtitle: 'ستظهر هنا الشهادات المرتبطة بالنشاطات المكتملة.',
            )
          else
            ...completedItems.map(
              (item) => Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: Card(
                  elevation: 0,
                  color: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(24),
                    side: const BorderSide(color: Color(0xFFE8EFE0)),
                  ),
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    leading: Container(
                      width: 46,
                      height: 46,
                      decoration: BoxDecoration(
                        color: const Color(0xFFE4EDD4),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: const Icon(Icons.workspace_premium_rounded, color: Color(0xFF5B7523)),
                    ),
                    title: Text(item.name, style: const TextStyle(fontWeight: FontWeight.w800)),
                    subtitle: Text(item.organisation.isNotEmpty ? item.organisation : 'شهادة إتمام نشاط'),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final volunteer = state.volunteer;
    final stats = state.stats;
    final displayName = (volunteer?.name.trim().isNotEmpty ?? false) ? volunteer!.name : 'المتطوع';
    final username = volunteer?.username ?? '';
    final imageUrl = volunteer?.imageUrl ?? '';
    final initials = displayName.isNotEmpty ? displayName.substring(0, 1).toUpperCase() : '?';
    final identityPayload = jsonEncode({
      'role': 'volunteer',
      'id': volunteer?.id,
      'name': displayName,
      'username': username,
      'phone': volunteer?.phone ?? '',
      'email': volunteer?.email ?? '',
      'city_id': volunteer?.cityId,
      'city_name': volunteer?.cityName ?? '',
    });

    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 22),
        children: [
          Container(
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF7E9E39), Color(0xFF4E661D)],
                begin: Alignment.topRight,
                end: Alignment.bottomLeft,
              ),
              borderRadius: BorderRadius.circular(30),
            ),
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                CircleAvatar(
                  radius: 42,
                  backgroundColor: Colors.white.withOpacity(0.18),
                  backgroundImage: imageUrl.isNotEmpty
                      ? NetworkImage(imageUrl)
                      : null,
                  child: imageUrl.isEmpty
                      ? Text(
                          initials,
                          style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w900, color: Colors.white),
                        )
                      : null,
                ),
                const SizedBox(height: 14),
                Text(
                  displayName,
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w900,
                      ),
                ),
                const SizedBox(height: 4),
                Text(
                  username.isNotEmpty ? '@$username' : ' ',
                  style: const TextStyle(color: Colors.white70, fontWeight: FontWeight.w600),
                ),
                const SizedBox(height: 8),
                Text(
                  volunteer?.cityName ?? 'لا توجد مدينة محددة',
                  style: const TextStyle(color: Colors.white70),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          _SectionCard(
            title: 'ملخص الحساب',
            child: Row(
              children: [
                Expanded(child: _StatBox(label: 'الطلبات', value: stats?.totalActivities ?? 0)),
                const SizedBox(width: 10),
                Expanded(child: _StatBox(label: 'الموافقة', value: stats?.approvedActivities ?? 0)),
                const SizedBox(width: 10),
                Expanded(child: _StatBox(label: 'المكتملة', value: stats?.completedActivities ?? 0)),
              ],
            ),
          ),
          const SizedBox(height: 16),
          _SectionCard(
            title: state.isAdmin ? 'مسح هوية متطوع' : 'بطاقة هويتي',
            child: state.isAdmin
                ? Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Text(
                        'استخدم الكاميرا لمسح رمز المتطوع والتعرف على بياناته مباشرة.',
                        style: TextStyle(height: 1.5),
                      ),
                      const SizedBox(height: 14),
                      FilledButton.icon(
                        onPressed: () {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => QrScannerScreen(
                                footerIndex: context.findAncestorStateOfType<_AppShellState>()?.currentIndex ?? 3,
                              ),
                            ),
                          );
                        },
                        icon: const Icon(Icons.qr_code_scanner_rounded),
                        label: const Text(
                          'فتح الماسح',
                          style: TextStyle(fontWeight: FontWeight.w800),
                        ),
                        style: FilledButton.styleFrom(
                          backgroundColor: const Color(0xFF5B7523),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
                        ),
                      ),
                    ],
                  )
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(18),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAF5),
                          borderRadius: BorderRadius.circular(24),
                          border: Border.all(color: const Color(0xFFE8EFE0)),
                        ),
                        child: Column(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(22),
                                border: Border.all(color: const Color(0xFFE5ECD8)),
                              ),
                              child: QrImageView(
                                data: identityPayload,
                                version: QrVersions.auto,
                                size: 220,
                                gapless: false,
                                backgroundColor: Colors.white,
                              ),
                            ),
                            const SizedBox(height: 14),
                            Text(
                              'هذا الرمز يعرّفك كمتطوع عند مسحه من طرف الإدارة.',
                              textAlign: TextAlign.center,
                              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                    color: const Color(0xFF607062),
                                    height: 1.5,
                                  ),
                            ),
                            const SizedBox(height: 10),
                            Text(
                              username.isNotEmpty ? '@$username' : displayName,
                              textAlign: TextAlign.center,
                              style: const TextStyle(fontWeight: FontWeight.w800, color: Color(0xFF304300)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
          ),
          const SizedBox(height: 16),
          _SectionCard(
            title: 'بياناتي',
            child: Column(
              children: [
                _ProfileInfoTile(
                  icon: Icons.phone_rounded,
                  label: 'الهاتف',
                  value: volunteer?.phone ?? '-',
                ),
                _ProfileInfoTile(
                  icon: Icons.email_rounded,
                  label: 'البريد الإلكتروني',
                  value: volunteer?.email ?? '-',
                ),
                _ProfileInfoTile(
                  icon: Icons.badge_rounded,
                  label: 'اسم المستخدم',
                  value: volunteer?.username ?? '-',
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: () => state.signOut(),
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFF5B7523),
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
            ),
            child: const Text(
              'تسجيل الخروج',
              style: TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
        ],
      ),
    );
  }

}

class QrScannerScreen extends StatefulWidget {
  const QrScannerScreen({super.key, this.footerIndex = 3});

  final int footerIndex;

  @override
  State<QrScannerScreen> createState() => _QrScannerScreenState();
}

class _QrScannerScreenState extends State<QrScannerScreen> {
  final MobileScannerController _controller = MobileScannerController();
  String? _lastDetectedValue;
  bool _showingDialog = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _handleDetect(BarcodeCapture capture) async {
    if (_showingDialog) return;

    for (final barcode in capture.barcodes) {
      final rawValue = barcode.rawValue?.trim();
      if (rawValue == null || rawValue.isEmpty || rawValue == _lastDetectedValue) {
        continue;
      }

      _lastDetectedValue = rawValue;
      _showingDialog = true;

      try {
        final payload = _parseQrPayload(rawValue);
        if (payload == null) {
          await _showResultDialog(
            title: 'رمز غير صالح',
            message: 'لم أتمكن من قراءة بيانات الهوية من هذا الرمز.',
          );
          return;
        }

        final role = (payload['role']?.toString() ?? '').toLowerCase();
        if (role.isNotEmpty && role != 'volunteer') {
          await _showResultDialog(
            title: 'هذا الرمز ليس لمتطوع',
            message: 'تم العثور على رمز صحيح، لكنه لا يخص متطوعًا.',
          );
          return;
        }

        await _showVolunteerDialog(payload);
      } finally {
        _showingDialog = false;
      }
      return;
    }
  }

  Map<String, dynamic>? _parseQrPayload(String rawValue) {
    try {
      final decoded = jsonDecode(rawValue);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      if (decoded is Map) {
        return Map<String, dynamic>.from(decoded);
      }
    } catch (_) {}
    return null;
  }

  Future<void> _showVolunteerDialog(Map<String, dynamic> payload) async {
    await _showResultDialog(
      title: 'تم التعرف على المتطوع',
      message: 'تمت قراءة بيانات الهوية بنجاح.',
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _ScanDetailRow(label: 'الاسم', value: payload['name']?.toString() ?? '-'),
          _ScanDetailRow(label: 'اسم المستخدم', value: payload['username']?.toString() ?? '-'),
          _ScanDetailRow(label: 'المدينة', value: payload['city_name']?.toString() ?? '-'),
          _ScanDetailRow(label: 'الهاتف', value: payload['phone']?.toString() ?? '-'),
          _ScanDetailRow(label: 'البريد', value: payload['email']?.toString() ?? '-'),
          _ScanDetailRow(label: 'المعرف', value: payload['id']?.toString() ?? '-'),
        ],
      ),
    );
  }

  Future<void> _showResultDialog({
    required String title,
    required String message,
    Widget? body,
  }) {
    return showDialog<void>(
      context: context,
      barrierDismissible: true,
      builder: (dialogContext) {
        return AlertDialog(
          title: Text(title),
          content: SingleChildScrollView(
            child: body == null
                ? Text(message)
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(message),
                      const SizedBox(height: 14),
                      body,
                    ],
                  ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: const Text('إغلاق'),
            ),
          ],
        );
      },
    );
  }

  void _resetScanner() {
    setState(() {
      _lastDetectedValue = null;
      _showingDialog = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('ماسح الهوية', style: _pageTitleStyle.copyWith(fontSize: 20, color: Colors.white)),
        backgroundColor: const Color(0xFF5B7523),
        foregroundColor: Colors.white,
        actions: [
          TextButton(
            onPressed: _resetScanner,
            child: const Text(
              'إعادة',
              style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildPageTitle('وجّه الكاميرا نحو رمز المتطوع التعريفي.'),
              const SizedBox(height: 12),
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(28),
                  child: Container(
                    decoration: BoxDecoration(
                      color: Colors.black,
                      borderRadius: BorderRadius.circular(28),
                    ),
                    child: MobileScanner(
                      controller: _controller,
                      onDetect: _handleDetect,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              const _EmptyStateCard(
                icon: Icons.qr_code_scanner_rounded,
                title: 'التقاط QR',
                subtitle: 'بعد قراءة الرمز سيظهر تعريف المتطوع في نافذة منبثقة في منتصف الشاشة.',
              ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: _FooterNavBar(
        tabs: _footerTabsForState(context.read<AppState>()),
        index: widget.footerIndex,
        onChanged: (value) => _openShellTab(context, value),
      ),
    );
  }
}

class _ScanDetailRow extends StatelessWidget {
  const _ScanDetailRow({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAF5),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE8EFE0)),
      ),
      child: Row(
        children: [
          Text(label, style: const TextStyle(color: Color(0xFF607062), fontWeight: FontWeight.w700)),
          const Spacer(),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.left,
              style: const TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
        ],
      ),
    );
  }
}

class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 22),
        children: [
          _buildPageTitle('الإشعارات'),
          const SizedBox(height: 12),
          const _EmptyStateCard(
            icon: Icons.notifications_none_rounded,
            title: 'لا توجد إشعارات بعد',
            subtitle: 'ستظهر هنا التنبيهات الخاصة بطلباتك وتحديثاتك.',
          ),
        ],
      ),
    );
  }
}

class FavoritesScreen extends StatelessWidget {
  const FavoritesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final favorites = state.cachedActivities.where((item) => state.isFavorite(item.id)).toList();

    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 22),
        children: [
          _buildPageTitle('المفضلة'),
          const SizedBox(height: 12),
          if (favorites.isEmpty)
            const _EmptyStateCard(
              icon: Icons.bookmark_border_rounded,
              title: 'لا توجد نشاطات مفضلة بعد',
              subtitle: 'اضغط على علامة الحفظ داخل صفحة النشاطات لإضافتها هنا.',
            )
          else
            ...favorites.map(
              (item) => Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: ActivityCard(
                  item: item,
                  isFavorite: true,
                  onToggleFavorite: () => state.toggleFavorite(item),
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                          builder: (_) => ActivityDetailScreen(
                            activityId: item.id,
                            title: item.name,
                            footerIndex: context.findAncestorStateOfType<_AppShellState>()?.currentIndex ?? 1,
                          ),
                        ),
                      );
                  },
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class AdminDashboardScreen extends StatefulWidget {
  const AdminDashboardScreen({super.key});

  @override
  State<AdminDashboardScreen> createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends State<AdminDashboardScreen> {
  bool _loadingRequests = true;
  List<Map<String, dynamic>> _requests = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadRequests());
  }

  Future<void> _loadRequests() async {
    final state = context.read<AppState>();
    if (!state.isSignedIn) return;
    setState(() => _loadingRequests = true);
    try {
      final raw = await state.authorized((token) => state.api.adminRequests(token));
      setState(() => _requests = raw);
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _loadingRequests = false);
    }
  }

  void _showError(String message) {
    _showCenteredPopup(context, message);
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();

    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 22),
        children: [
          _buildPageTitle('لوحة الإدارة'),
          const SizedBox(height: 14),
          _StatsCarousel(stats: state.stats, citiesCount: state.cities.length),
          const SizedBox(height: 16),
          _SectionCard(
            title: 'طلبات بانتظار المراجعة',
            child: _loadingRequests
                ? const Center(child: CircularProgressIndicator())
                : _requests.isEmpty
                    ? const _EmptyStateCard(
                        icon: Icons.inbox_rounded,
                        title: 'لا توجد طلبات حالياً',
                        subtitle: 'ستظهر هنا طلبات التطوع الجديدة للمراجعة.',
                      )
                    : Column(
                        children: _requests.take(4).map((request) {
                          final volunteer = request['volunteer'] as Map<String, dynamic>?;
                          final activity = request['activity'] as Map<String, dynamic>?;
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: ListTile(
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
                              tileColor: const Color(0xFFF8FAF5),
                              title: Text('${volunteer?['name'] ?? '-'} • ${activity?['name'] ?? '-'}'),
                              subtitle: Text(request['city_name']?.toString() ?? ''),
                              trailing: const Icon(Icons.chevron_left_rounded),
                              onTap: () {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('افتح تبويب الطلبات للمراجعة التفصيلية.')),
                                );
                              },
                            ),
                          );
                        }).toList(),
                      ),
          ),
        ],
      ),
    );
  }
}

class AdminActivitiesScreen extends StatefulWidget {
  const AdminActivitiesScreen({super.key});

  @override
  State<AdminActivitiesScreen> createState() => _AdminActivitiesScreenState();
}

class _AdminActivitiesScreenState extends State<AdminActivitiesScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _items = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    final state = context.read<AppState>();
    if (!state.isSignedIn) return;
    setState(() => _loading = true);
    try {
      final raw = await state.authorized((token) => state.api.adminActivities(token));
      setState(() => _items = raw);
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showError(String message) {
    _showCenteredPopup(context, message);
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 22),
        children: [
          _buildPageTitle('إدارة النشاطات'),
          const SizedBox(height: 12),
          if (_loading)
            const Center(child: CircularProgressIndicator())
          else if (_items.isEmpty)
            const _EmptyStateCard(
              icon: Icons.event_busy_rounded,
              title: 'لا توجد نشاطات',
              subtitle: 'لن يظهر هنا أي شيء حتى يتم إضافة نشاطات.',
            )
          else
            ..._items.map(
              (item) => Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: ActivityCard(
                  item: ActivityItem.fromJson(item),
                  onTap: () {},
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class AdminVolunteersScreen extends StatefulWidget {
  const AdminVolunteersScreen({super.key});

  @override
  State<AdminVolunteersScreen> createState() => _AdminVolunteersScreenState();
}

class _AdminVolunteersScreenState extends State<AdminVolunteersScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _items = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    final state = context.read<AppState>();
    if (!state.isSignedIn) return;
    setState(() => _loading = true);
    try {
      final raw = await state.authorized((token) => state.api.adminVolunteers(token));
      setState(() => _items = raw);
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showError(String message) {
    _showCenteredPopup(context, message);
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 22),
        children: [
          _buildPageTitle('إدارة المتطوعين'),
          const SizedBox(height: 12),
          if (_loading)
            const Center(child: CircularProgressIndicator())
          else if (_items.isEmpty)
            const _EmptyStateCard(
              icon: Icons.groups_rounded,
              title: 'لا يوجد متطوعون',
              subtitle: 'لن يظهر هنا أي متطوع حتى يتم تسجيلهم.',
            )
          else
            ..._items.map(
              (item) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: ListTile(
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
                  tileColor: Colors.white,
                  leading: CircleAvatar(
                    backgroundColor: const Color(0xFFE4EDD4),
                    child: Text(
                      (item['name']?.toString().isNotEmpty ?? false) ? item['name'].toString()[0] : '?',
                      style: const TextStyle(color: Color(0xFF304300), fontWeight: FontWeight.w900),
                    ),
                  ),
                  title: Text(item['name']?.toString() ?? '-'),
                  subtitle: Text('${item['city_name'] ?? '-'} • ${item['phone'] ?? '-'}'),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class AdminRequestsScreen extends StatefulWidget {
  const AdminRequestsScreen({super.key});

  @override
  State<AdminRequestsScreen> createState() => _AdminRequestsScreenState();
}

class _AdminRequestsScreenState extends State<AdminRequestsScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _items = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    final state = context.read<AppState>();
    if (!state.isSignedIn) return;
    setState(() => _loading = true);
    try {
      final raw = await state.authorized((token) => state.api.adminRequests(token));
      setState(() => _items = raw);
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _updateStatus(int id, int status) async {
    final state = context.read<AppState>();
    try {
      await state.authorized((token) => state.api.updateRequestStatus(token: token, id: id, status: status));
      await _load();
      _showError(status == 3 ? 'تم رفض الطلب.' : 'تم تحديث الطلب.');
    } catch (error) {
      _showError(error.toString());
    }
  }

  void _showError(String message) {
    _showCenteredPopup(context, message);
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 18, 16, 22),
        children: [
          _buildPageTitle('طلبات التطوع'),
          const SizedBox(height: 12),
          if (_loading)
            const Center(child: CircularProgressIndicator())
          else if (_items.isEmpty)
            const _EmptyStateCard(
              icon: Icons.inbox_rounded,
              title: 'لا توجد طلبات معلّقة',
              subtitle: 'ستظهر هنا طلبات المتطوعين الجديدة.',
            )
          else
            ..._items.map(
              (item) {
                final volunteer = item['volunteer'] as Map<String, dynamic>?;
                final activity = item['activity'] as Map<String, dynamic>?;
                return Padding(
                  padding: const EdgeInsets.only(bottom: 14),
                  child: Card(
                    elevation: 0,
                    color: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(24),
                      side: const BorderSide(color: Color(0xFFE8EFE0)),
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            volunteer?['name']?.toString() ?? '-',
                            style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18),
                          ),
                          const SizedBox(height: 4),
                          Text(activity?['name']?.toString() ?? '-', style: const TextStyle(color: Color(0xFF607062))),
                          const SizedBox(height: 10),
                          Text(item['city_name']?.toString() ?? ''),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: FilledButton(
                                  onPressed: () => _updateStatus(int.tryParse(item['id']?.toString() ?? '') ?? 0, 1),
                                  child: const Text('موافقة'),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: OutlinedButton(
                                  onPressed: () => _updateStatus(int.tryParse(item['id']?.toString() ?? '') ?? 0, 3),
                                  child: const Text('رفض'),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
        ],
      ),
    );
  }
}

class ActivityCard extends StatelessWidget {
  const ActivityCard({
    super.key,
    required this.item,
    required this.onTap,
    this.isFavorite = false,
    this.onToggleFavorite,
  });

  final ActivityItem item;
  final VoidCallback onTap;
  final bool isFavorite;
  final VoidCallback? onToggleFavorite;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(26),
      child: Card(
        elevation: 0,
        color: Colors.white,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(26),
          side: const BorderSide(color: Color(0xFFE8EFE0)),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.name,
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
                        ),
                        const SizedBox(height: 4),
                        Text(item.organisation, style: const TextStyle(color: Color(0xFF607062))),
                      ],
                    ),
                  ),
                  if (onToggleFavorite != null)
                    IconButton(
                      onPressed: onToggleFavorite,
                      icon: Icon(
                        isFavorite ? Icons.bookmark_rounded : Icons.bookmark_border_rounded,
                        color: const Color(0xFF5B7523),
                      ),
                    ),
                  _StatusBadge(status: item.enrollmentStatus),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _MetaChip(label: item.cityName ?? 'مدينة'),
                  _MetaChip(label: _formatDateRange(item.dateFrom, item.dateTo)),
                  _MetaChip(label: item.hours != null ? '${item.hours} ساعة' : 'مرن'),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                item.description.isNotEmpty ? item.description : 'لا يوجد وصف.',
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});

  final int? status;

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      2 => const Color(0xFF0F9D58),
      1 => const Color(0xFF48631D),
      0 => const Color(0xFFD97706),
      _ => const Color(0xFF607062),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        _statusLabel(status),
        style: TextStyle(color: color, fontWeight: FontWeight.w800, fontSize: 12),
      ),
    );
  }
}

class _MetaChip extends StatelessWidget {
  const _MetaChip({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Chip(
      label: Text(label),
      visualDensity: VisualDensity.compact,
      backgroundColor: const Color(0xFFF7F0DC),
      labelStyle: const TextStyle(color: Color(0xFF8A6500)),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAF5),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE8EFE0)),
      ),
      child: Row(
        children: [
          Text(label, style: const TextStyle(color: Color(0xFF607062), fontWeight: FontWeight.w700)),
          const Spacer(),
          Flexible(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w800))),
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({required this.title, required this.child});

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(28),
        side: const BorderSide(color: Color(0xFFE8EFE0)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              title,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w900,
                    color: const Color(0xFF304300),
                  ),
            ),
            const SizedBox(height: 14),
            child,
          ],
        ),
      ),
    );
  }
}

class _ProfileInfoTile extends StatelessWidget {
  const _ProfileInfoTile({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAF5),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE8EFE0)),
      ),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: const Color(0xFFE4EDD4),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, size: 18, color: const Color(0xFF56711E)),
          ),
          const SizedBox(width: 12),
          Text(label, style: const TextStyle(color: Color(0xFF607062), fontWeight: FontWeight.w700)),
          const Spacer(),
          Flexible(
            child: Align(
              alignment: Alignment.centerLeft,
              child: Directionality(
                textDirection: ui.TextDirection.ltr,
                child: Text(
                  value,
                  textAlign: TextAlign.left,
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatBox extends StatelessWidget {
  const _StatBox({required this.label, required this.value});

  final String label;
  final int value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE8EFE0)),
      ),
      child: Column(
        children: [
          Text('$value', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900)),
          const SizedBox(height: 4),
          Text(label, style: const TextStyle(color: Color(0xFF607062), fontSize: 12)),
        ],
      ),
    );
  }
}

class _HeroCard extends StatelessWidget {
  const _HeroCard({required this.volunteerName, required this.stats});

  final String volunteerName;
  final MobileStats? stats;

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(30),
        side: const BorderSide(color: Color(0xFFE8EFE0)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'مرحبًا بعودتك',
              style: TextStyle(
                color: Color(0xFF6B8E23),
                fontWeight: FontWeight.w800,
                letterSpacing: 1.2,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              volunteerName,
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.w900),
            ),
            const SizedBox(height: 8),
            const Text('تصفح الأنشطة المناسبة، قدّم عليها، وتابع رحلتك التطوعية من الهاتف.'),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(child: _StatBox(label: 'الطلبات', value: stats?.totalActivities ?? 0)),
                const SizedBox(width: 10),
                Expanded(child: _StatBox(label: 'الموافقة', value: stats?.approvedActivities ?? 0)),
                const SizedBox(width: 10),
                Expanded(child: _StatBox(label: 'المكتملة', value: stats?.completedActivities ?? 0)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyStateCard extends StatelessWidget {
  const _EmptyStateCard({required this.icon, required this.title, required this.subtitle});

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      color: const Color(0xFFF8FBF3),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(24),
        side: const BorderSide(color: Color(0xFFE3ECD2)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(22),
        child: Column(
          children: [
            Icon(icon, size: 36, color: const Color(0xFF607062)),
            const SizedBox(height: 10),
            Text(title, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
            const SizedBox(height: 6),
            Text(subtitle, textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}

class _CityFilter {
  const _CityFilter({required this.id, required this.label});

  final String id;
  final String label;
}

class _DashboardAction {
  const _DashboardAction({
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
}

class _DashboardActionCard extends StatelessWidget {
  const _DashboardActionCard({required this.action});

  final _DashboardAction action;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: action.onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          boxShadow: const [
            BoxShadow(
              color: Color(0x0F000000),
              blurRadius: 14,
              offset: Offset(0, 6),
            ),
          ],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(action.icon, size: 54, color: action.color),
            const SizedBox(height: 10),
            Text(
              action.label,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Color(0xFF6E6E6E),
                fontWeight: FontWeight.w700,
                fontSize: 15,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatsCarousel extends StatefulWidget {
  const _StatsCarousel({required this.stats, required this.citiesCount});

  final MobileStats? stats;
  final int citiesCount;

  @override
  State<_StatsCarousel> createState() => _StatsCarouselState();
}

class _StatsCarouselState extends State<_StatsCarousel> {
  late final PageController _controller;
  Timer? _timer;
  int _index = 0;

  @override
  void initState() {
    super.initState();
    _controller = PageController(viewportFraction: 0.94);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _scheduleAutoAdvance();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  Future<void> _advanceSlide() async {
    if (!mounted) {
      return;
    }
    if (!_controller.hasClients) {
      _scheduleAutoAdvance();
      return;
    }

    final slideCount = 4;
    final currentPage = _controller.page?.round() ?? _index;
    final nextIndex = (currentPage + 1) % slideCount;

    try {
      await _controller.animateToPage(
        nextIndex,
        duration: const Duration(milliseconds: 500),
        curve: Curves.easeInOut,
      );
    } catch (_) {}

    if (mounted) {
      _scheduleAutoAdvance();
    }
  }

  void _scheduleAutoAdvance() {
    _timer?.cancel();
    _timer = Timer(const Duration(seconds: 4), _advanceSlide);
  }

  @override
  Widget build(BuildContext context) {
    final stats = widget.stats;
    final slides = [
      (
        title: 'إجمالي المتطوعين',
        value: stats?.totalVolunteers ?? 0,
        icon: Icons.groups_rounded,
        colors: const [Color(0xFF8DB34E), Color(0xFF5F7E25)],
      ),
      (
        title: 'إجمالي النشاطات',
        value: stats?.totalActivities ?? 0,
        icon: Icons.event_available_rounded,
        colors: const [Color(0xFF7DA84D), Color(0xFF5A7427)],
      ),
      (
        title: 'المدن',
        value: stats?.totalCities ?? widget.citiesCount,
        icon: Icons.location_city_rounded,
        colors: const [Color(0xFF699B46), Color(0xFF496621)],
      ),
      (
        title: 'الشهادات',
        value: stats?.totalCertificates ?? 0,
        icon: Icons.workspace_premium_rounded,
        colors: const [Color(0xFFA5C15E), Color(0xFF6D8D2E)],
      ),
    ];

    return SizedBox(
      height: 170,
      child: Column(
        children: [
          Expanded(
            child: PageView.builder(
              controller: _controller,
              itemCount: slides.length,
              onPageChanged: (value) => setState(() => _index = value),
              itemBuilder: (context, index) {
                final slide = slides[index];
                return Container(
                  margin: const EdgeInsets.symmetric(horizontal: 2),
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: slide.colors,
                      begin: Alignment.centerRight,
                      end: Alignment.centerLeft,
                    ),
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: const [
                      BoxShadow(
                        color: Color(0x22000000),
                        blurRadius: 16,
                        offset: Offset(0, 8),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Align(
                        alignment: Alignment.centerRight,
                        child: Icon(slide.icon, color: Colors.white, size: 30),
                      ),
                      const Spacer(),
                      Text(
                        '${slide.value}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 42,
                          fontWeight: FontWeight.w900,
                          height: 1.0,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        slide.title,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
          ),
          const SizedBox(height: 10),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(
              slides.length,
              (index) => AnimatedContainer(
                duration: const Duration(milliseconds: 220),
                margin: const EdgeInsets.symmetric(horizontal: 3),
                width: _index == index ? 18 : 7,
                height: 7,
                decoration: BoxDecoration(
                  color: _index == index ? const Color(0xFF688837) : const Color(0xFFCED9B4),
                  borderRadius: BorderRadius.circular(999),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FooterNavBar extends StatelessWidget {
  const _FooterNavBar({
    required this.tabs,
    required this.index,
    required this.onChanged,
  });

  final List<_FooterTabData> tabs;
  final int index;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: Container(
        margin: const EdgeInsets.fromLTRB(16, 0, 16, 14),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: const Color(0xFF5F7E25),
          borderRadius: BorderRadius.circular(26),
          boxShadow: const [
            BoxShadow(
              color: Color(0x22000000),
              blurRadius: 18,
              offset: Offset(0, 8),
            ),
          ],
        ),
        child: Row(
          children: List.generate(
            tabs.length,
            (tabIndex) {
              final tab = tabs[tabIndex];
              return Expanded(
                child: _FooterTab(
                  label: tab.label,
                  active: index == tabIndex,
                  icon: tab.icon,
                  avatarUrl: tab.avatarUrl,
                  onTap: () => onChanged(tabIndex),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}

class _FooterTab extends StatelessWidget {
  const _FooterTab({
    required this.label,
    required this.active,
    required this.onTap,
    this.icon,
    this.avatarUrl,
  });

  final String label;
  final bool active;
  final VoidCallback onTap;
  final IconData? icon;
  final String? avatarUrl;

  @override
  Widget build(BuildContext context) {
    final child = avatarUrl != null
        ? CircleAvatar(
            radius: 15,
            backgroundColor: Colors.white.withOpacity(0.15),
            backgroundImage: avatarUrl!.isNotEmpty ? NetworkImage(avatarUrl!) : null,
            child: avatarUrl == null || avatarUrl!.isEmpty
                ? const Icon(Icons.person_rounded, color: Colors.white, size: 18)
                : null,
          )
        : Icon(icon, color: Colors.white, size: 24);

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        margin: const EdgeInsets.symmetric(horizontal: 4),
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: active ? Colors.white.withOpacity(0.12) : Colors.transparent,
          borderRadius: BorderRadius.circular(18),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            child,
            const SizedBox(height: 6),
            Text(
              label,
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }
}

void _showCenteredPopup(BuildContext context, String message) {
  if (!context.mounted) return;
  showDialog<void>(
    context: context,
    builder: (dialogContext) {
      return AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        contentPadding: const EdgeInsets.fromLTRB(24, 24, 24, 12),
        title: const Text(
          'تنبيه',
          textAlign: TextAlign.center,
        ),
        content: Text(
          message,
          textAlign: TextAlign.center,
        ),
        actionsAlignment: MainAxisAlignment.center,
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(),
            child: const Text('حسنًا'),
          ),
        ],
      );
    },
  );
}

String _formatDateRange(String? from, String? to) {
  final formatter = DateFormat('d MMM yyyy', 'ar');
  String format(String? value) {
    if (value == null || value.isEmpty) return 'قيد التحديد';
    final parsed = DateTime.tryParse(value);
    if (parsed == null) return value;
    return formatter.format(parsed);
  }

  return '${format(from)} - ${format(to)}';
}

String _statusLabel(int? status) {
  return switch (status) {
    2 => 'مكتمل',
    1 => 'مقبول',
    0 => 'قيد الانتظار',
    _ => 'لم يتم التقديم',
  };
}
