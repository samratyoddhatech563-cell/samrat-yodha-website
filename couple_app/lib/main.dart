import 'package:flutter/material.dart';

import 'data/app_state.dart';
import 'screens/home_screen.dart';
import 'screens/pair_screen.dart';
import 'screens/welcome_screen.dart';
import 'theme.dart';

void main() => runApp(MyLoveApp(state: AppState.fromDemoLink(Uri.base)));

class MyLoveApp extends StatelessWidget {
  const MyLoveApp({super.key, required this.state});

  final AppState state;

  @override
  Widget build(BuildContext context) {
    return AppScope(
      state: state,
      child: MaterialApp(
        title: 'My Love',
        debugShowCheckedModeBanner: false,
        theme: buildTheme(),
        home: const _Root(),
      ),
    );
  }
}

/// साइन-अप → जोड़ी → होम: जहाँ यूज़र है, वही स्क्रीन दिखती है।
class _Root extends StatelessWidget {
  const _Root();

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final Widget page = s.myId == null
        ? const WelcomeScreen()
        : !s.isPaired
            ? const PairScreen()
            : HomeScreen(initialTab: s.initialTab);
    return AnimatedSwitcher(duration: const Duration(milliseconds: 350), child: page);
  }
}
