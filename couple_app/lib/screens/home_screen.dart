import 'package:flutter/material.dart';

import '../data/app_state.dart';
import 'dashboard_tab.dart';
import 'questions_tab.dart';
import 'settings_tab.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, this.initialTab = 0});

  final int initialTab;

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  late int _tab = widget.initialTab;

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final pending = s.inbox.where((q) => q.answer == null).length;
    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: IndexedStack(
          index: _tab,
          children: [
            DashboardTab(onOpenQuestions: () => setState(() => _tab = 1)),
            const QuestionsTab(),
            const SettingsTab(),
          ],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: [
          const NavigationDestination(
            icon: Icon(Icons.favorite_border_rounded),
            selectedIcon: Icon(Icons.favorite_rounded),
            label: 'होम',
          ),
          NavigationDestination(
            icon: Badge(
              isLabelVisible: pending > 0,
              label: Text('$pending'),
              child: const Icon(Icons.chat_bubble_outline_rounded),
            ),
            selectedIcon: const Icon(Icons.chat_bubble_rounded),
            label: 'सवाल',
          ),
          const NavigationDestination(
            icon: Icon(Icons.tune_rounded),
            label: 'सेटिंग्स',
          ),
        ],
      ),
    );
  }
}
