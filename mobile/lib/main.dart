import 'dart:async';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'api.dart';
import 'models.dart';

const String defaultApiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'https://portal.i-volunteer.ly',
);

void main() {
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
        title: 'I Volunteer',
        theme: ThemeData(
          useMaterial3: true,
          colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF304300)),
          scaffoldBackgroundColor: const Color(0xFFF4F6F1),
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
  VolunteerProfile? volunteer;
  MobileStats? stats;
  List<Map<String, dynamic>> cities = [];

  bool get isSignedIn => token != null && token!.isNotEmpty;

  Future<void> bootstrap() async {
    final prefs = await SharedPreferences.getInstance();
    final savedApiBaseUrl = prefs.getString('api_base_url');
    if (savedApiBaseUrl != null && savedApiBaseUrl.trim().isNotEmpty) {
      apiBaseUrl = savedApiBaseUrl.trim();
      api = MobileApi(apiBaseUrl);
    }

    token = prefs.getString('token');

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

  Future<void> updateApiBaseUrl(String value) async {
    final normalized = value.trim();
    if (normalized.isEmpty) {
      throw ApiException('API URL cannot be empty.');
    }

    apiBaseUrl = normalized;
    api = MobileApi(apiBaseUrl);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('api_base_url', apiBaseUrl);
    await prefs.remove('token');
    token = null;
    volunteer = null;
    stats = null;
    cities = [];
    notifyListeners();
  }

  Future<void> signIn(String identifier, String password) async {
    final auth = await api.login(identifier, password);
    token = auth.token;
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
    volunteer = VolunteerProfile.fromJson(Map<String, dynamic>.from(me['data']['volunteer'] as Map));
    stats = MobileStats.fromJson(Map<String, dynamic>.from(me['data']['stats'] as Map));
    notifyListeners();
  }

  Future<void> refreshSession() async {
    if (!isSignedIn) return;
    final auth = await api.refresh(token!);
    token = auth.token;
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
      throw ApiException('You are not signed in.');
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
    return const Scaffold(
      body: Center(child: CircularProgressIndicator()),
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
  bool _busy = false;

  @override
  void dispose() {
    _identifierController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final identifier = _identifierController.text.trim();
    final password = _passwordController.text;
    if (identifier.isEmpty || password.isEmpty) {
      _showError('Enter your username, phone, or email and password.');
      return;
    }

    setState(() => _busy = true);
    try {
      await context.read<AppState>().signIn(identifier, password);
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();

    return Scaffold(
      body: SafeArea(
        child: Stack(
          children: [
            Positioned(
              top: -120,
              right: -90,
              child: Container(
                width: 260,
                height: 260,
                decoration: const BoxDecoration(
                  shape: BoxShape.circle,
                  color: Color(0xFFDCE8CB),
                ),
              ),
            ),
            Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const _BrandHeader(),
                    const SizedBox(height: 18),
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
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            const Text('Email, phone, or username'),
                            const SizedBox(height: 8),
                            TextField(
                              controller: _identifierController,
                              decoration: const InputDecoration(
                                border: OutlineInputBorder(),
                                hintText: 'your@email.com',
                              ),
                            ),
                            const SizedBox(height: 12),
                            const Text('Password'),
                            const SizedBox(height: 8),
                            TextField(
                              controller: _passwordController,
                              obscureText: true,
                              decoration: const InputDecoration(
                                border: OutlineInputBorder(),
                                hintText: 'Password',
                              ),
                            ),
                            const SizedBox(height: 16),
                            FilledButton(
                              onPressed: _busy ? null : _submit,
                              child: _busy
                                  ? const SizedBox(
                                      width: 18,
                                      height: 18,
                                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                    )
                                  : const Text('Sign in'),
                            ),
                            const SizedBox(height: 12),
                            Text(
                              'Flutter mobile client connected to your existing volunteer backend.',
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'API: ${state.apiBaseUrl}',
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                            const SizedBox(height: 10),
                            TextButton.icon(
                              onPressed: () {
                                Navigator.of(context).push(
                                  MaterialPageRoute(builder: (_) => const ApiConfigScreen()),
                                );
                              },
                              icon: const Icon(Icons.tune),
                              label: const Text('API settings'),
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
        const Text(
          'I Volunteer',
          style: TextStyle(
            fontSize: 34,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.8,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'Find the right volunteer activity and apply from your phone.',
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: const Color(0xFF607062)),
        ),
      ],
    );
  }
}

class AppShell extends StatefulWidget {
  const AppShell({super.key});

  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      DiscoverScreen(onOpenActivity: _openActivity),
      const MyActivitiesScreen(),
      const ProfileScreen(),
    ];

    return Scaffold(
      body: pages[_index],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (value) => setState(() => _index = value),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.explore_outlined), label: 'Discover'),
          NavigationDestination(icon: Icon(Icons.list_alt_outlined), label: 'My Activities'),
          NavigationDestination(icon: Icon(Icons.person_outline), label: 'Profile'),
        ],
      ),
    );
  }

  void _openActivity(int id, String title) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ActivityDetailScreen(activityId: id, title: title),
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
      await state.refreshProfile();
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  void _onSearchChanged(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 350), _load);
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();
    final chips = [
      const _CityFilter(id: '', label: 'My city'),
      const _CityFilter(id: 'all', label: 'All cities'),
      ...state.cities.map(
        (city) => _CityFilter(
          id: city['id'].toString(),
          label: city['name']?.toString() ?? 'City',
        ),
      ),
    ];

    return SafeArea(
      child: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _HeroCard(
              volunteerName: state.volunteer?.name ?? 'Volunteer',
              stats: state.stats,
            ),
            const SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Discover activities', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                TextButton(onPressed: _load, child: const Text('Refresh')),
              ],
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.search),
                border: OutlineInputBorder(),
                hintText: 'Search activity or organization',
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              height: 46,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemBuilder: (_, index) {
                  final chip = chips[index];
                  final active = _cityId == chip.id;
                  return ChoiceChip(
                    label: Text(chip.label),
                    selected: active,
                    onSelected: (_) {
                      setState(() => _cityId = chip.id);
                      _load();
                    },
                  );
                },
                separatorBuilder: (_, __) => const SizedBox(width: 8),
                itemCount: chips.length,
              ),
            ),
            const SizedBox(height: 12),
            if (_loading)
              const Padding(
                padding: EdgeInsets.only(top: 48),
                child: Center(child: CircularProgressIndicator()),
              )
            else if (_items.isEmpty)
              const _EmptyState(
                icon: Icons.event_busy,
                title: 'No activities found',
                subtitle: 'Try a different city or search term.',
              )
            else
              ..._items.map(
                (item) => Padding(
                  padding: const EdgeInsets.only(bottom: 14),
                  child: ActivityCard(
                    item: item,
                    onTap: () => widget.onOpenActivity(item.id, item.name),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class ActivityDetailScreen extends StatefulWidget {
  const ActivityDetailScreen({super.key, required this.activityId, required this.title});

  final int activityId;
  final String title;

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
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    final item = _item;
    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: _loading || item == null
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
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
                            _MetaChip(label: item.cityName ?? 'City'),
                            _MetaChip(label: _formatDateRange(item.dateFrom, item.dateTo)),
                            _MetaChip(label: item.hours != null ? '${item.hours} hours' : 'Flexible'),
                            _MetaChip(label: _statusLabel(item.enrollmentStatus)),
                          ],
                        ),
                        const SizedBox(height: 16),
                        Text(item.description.isNotEmpty ? item.description : 'No detailed description provided.'),
                        const SizedBox(height: 16),
                        Text('Requirements', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                        const SizedBox(height: 6),
                        Text(item.requiredFiles.isNotEmpty ? item.requiredFiles : 'None listed'),
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
                              : Text(item.isEnrolled ? 'Withdraw from activity' : 'Apply now'),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
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
  List<ActivityItem> _items = [];

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
      final raw = await state.authorized((token) => state.api.myActivities(token));
      setState(() {
        _items = raw.map(ActivityItem.fromJson).toList();
      });
    } catch (error) {
      _showError(error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('My activities', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800)),
                TextButton(onPressed: _load, child: const Text('Refresh')),
              ],
            ),
            const SizedBox(height: 12),
            if (_loading)
              const Padding(
                padding: EdgeInsets.only(top: 48),
                child: Center(child: CircularProgressIndicator()),
              )
            else if (_items.isEmpty)
              const _EmptyState(
                icon: Icons.assignment_outlined,
                title: 'No applications yet',
                subtitle: 'Apply to an activity and it will appear here.',
              )
            else
              ..._items.map(
                (item) => Padding(
                  padding: const EdgeInsets.only(bottom: 14),
                  child: ActivityCard(
                    item: item,
                    onTap: () {
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => ActivityDetailScreen(activityId: item.id, title: item.name),
                        ),
                      );
                    },
                  ),
                ),
              ),
          ],
        ),
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

    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
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
                children: [
                  CircleAvatar(
                    radius: 34,
                    backgroundColor: const Color(0xFFDDE8CF),
                    child: Text(
                      (volunteer?.name.isNotEmpty ?? false) ? volunteer!.name[0].toUpperCase() : '?',
                      style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w800, color: Color(0xFF304300)),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(volunteer?.name ?? 'Volunteer', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w900)),
                  const SizedBox(height: 4),
                  Text(volunteer?.cityName ?? 'No city assigned'),
                  const SizedBox(height: 18),
                  _InfoRow(label: 'Phone', value: volunteer?.phone ?? '-'),
                  _InfoRow(label: 'Email', value: volunteer?.email ?? '-'),
                  _InfoRow(label: 'Username', value: volunteer?.username ?? '-'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(child: _StatBox(label: 'Applied', value: stats?.totalActivities ?? 0)),
              const SizedBox(width: 10),
              Expanded(child: _StatBox(label: 'Approved', value: stats?.approvedActivities ?? 0)),
              const SizedBox(width: 10),
              Expanded(child: _StatBox(label: 'Completed', value: stats?.completedActivities ?? 0)),
            ],
          ),
          const SizedBox(height: 16),
          FilledButton.tonal(
            onPressed: () => state.signOut(),
            child: const Text('Log out'),
          ),
          const SizedBox(height: 10),
          OutlinedButton.icon(
            onPressed: () {
              Navigator.of(context).push(
                MaterialPageRoute(builder: (_) => const ApiConfigScreen()),
              );
            },
            icon: const Icon(Icons.tune),
            label: const Text('API settings'),
          ),
        ],
      ),
    );
  }
}

class ApiConfigScreen extends StatefulWidget {
  const ApiConfigScreen({super.key});

  @override
  State<ApiConfigScreen> createState() => _ApiConfigScreenState();
}

class _ApiConfigScreenState extends State<ApiConfigScreen> {
  late final TextEditingController _controller;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_controller.text.isEmpty) {
      _controller.text = context.read<AppState>().apiBaseUrl;
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final value = _controller.text.trim();
    if (value.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Enter a valid API URL.')));
      return;
    }

    setState(() => _saving = true);
    try {
      await context.read<AppState>().updateApiBaseUrl(value);
      if (mounted) {
        Navigator.of(context).pop();
      }
    } catch (error) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error.toString())));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = context.watch<AppState>();

    return Scaffold(
      appBar: AppBar(title: const Text('API settings')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            'Current API URL',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 8),
          Text(state.apiBaseUrl),
          const SizedBox(height: 20),
          const Text('You can point the app to the live portal or a local backend for testing.'),
          const SizedBox(height: 12),
          TextField(
            controller: _controller,
            keyboardType: TextInputType.url,
            decoration: const InputDecoration(
              border: OutlineInputBorder(),
              labelText: 'API URL',
              hintText: 'https://portal.i-volunteer.ly',
            ),
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: _saving ? null : _save,
            child: _saving
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Text('Save'),
          ),
        ],
      ),
    );
  }
}

class ActivityCard extends StatelessWidget {
  const ActivityCard({super.key, required this.item, required this.onTap});

  final ActivityItem item;
  final VoidCallback onTap;

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
                  _StatusBadge(status: item.enrollmentStatus),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _MetaChip(label: item.cityName ?? 'City'),
                  _MetaChip(label: _formatDateRange(item.dateFrom, item.dateTo)),
                  _MetaChip(label: item.hours != null ? '${item.hours} hours' : 'Flexible'),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                item.description.isNotEmpty ? item.description : 'No description provided.',
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
              'Welcome back',
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
            const Text('Browse suitable activities, apply, and track your volunteer journey on the phone.'),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(child: _StatBox(label: 'Applied', value: stats?.totalActivities ?? 0)),
                const SizedBox(width: 10),
                Expanded(child: _StatBox(label: 'Approved', value: stats?.approvedActivities ?? 0)),
                const SizedBox(width: 10),
                Expanded(child: _StatBox(label: 'Completed', value: stats?.completedActivities ?? 0)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.icon, required this.title, required this.subtitle});

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 40),
      child: Column(
        children: [
          Icon(icon, size: 36, color: const Color(0xFF607062)),
          const SizedBox(height: 10),
          Text(title, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
          const SizedBox(height: 6),
          Text(subtitle, textAlign: TextAlign.center),
        ],
      ),
    );
  }
}

class _CityFilter {
  const _CityFilter({required this.id, required this.label});

  final String id;
  final String label;
}

String _formatDateRange(String? from, String? to) {
  final formatter = DateFormat('MMM d, yyyy');
  String format(String? value) {
    if (value == null || value.isEmpty) return 'TBA';
    final parsed = DateTime.tryParse(value);
    if (parsed == null) return value;
    return formatter.format(parsed);
  }

  return '${format(from)} - ${format(to)}';
}

String _statusLabel(int? status) {
  return switch (status) {
    2 => 'Completed',
    1 => 'Approved',
    0 => 'Pending',
    _ => 'Not applied',
  };
}
