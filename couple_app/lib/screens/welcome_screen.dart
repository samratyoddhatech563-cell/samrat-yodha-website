import 'package:flutter/material.dart';

import '../data/app_state.dart';
import '../theme.dart';
import '../widgets/common.dart';

class WelcomeScreen extends StatefulWidget {
  const WelcomeScreen({super.key});

  @override
  State<WelcomeScreen> createState() => _WelcomeScreenState();
}

class _WelcomeScreenState extends State<WelcomeScreen> {
  final _name = TextEditingController();
  final _phone = TextEditingController();
  String? _error;

  void _start() {
    if (_name.text.trim().isEmpty) {
      setState(() => _error = 'अपना नाम लिखिए');
      return;
    }
    if (_phone.text.replaceAll(RegExp(r'\D'), '').length != 10) {
      setState(() => _error = '10 अंकों का मोबाइल नंबर लिखिए');
      return;
    }
    // TODO(backend): यहाँ Supabase से OTP भेजकर नंबर की पुष्टि होगी।
    AppScope.of(context).signUp(_name.text);
  }

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: 20),
                  const Center(child: HeartLogo()),
                  const SizedBox(height: 24),
                  Text('My Love',
                      textAlign: TextAlign.center,
                      style: t.headlineLarge?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 6),
                  Text('दो दिल, एक ऐप — दूर रहकर भी पास',
                      textAlign: TextAlign.center,
                      style: t.titleMedium?.copyWith(color: LoveColors.muted)),
                  const SizedBox(height: 36),
                  TextField(
                    controller: _name,
                    textCapitalization: TextCapitalization.words,
                    decoration: const InputDecoration(
                      labelText: 'आपका नाम',
                      prefixIcon: Icon(Icons.person_outline_rounded),
                    ),
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: _phone,
                    keyboardType: TextInputType.phone,
                    maxLength: 10,
                    decoration: const InputDecoration(
                      labelText: 'मोबाइल नंबर',
                      prefixText: '+91  ',
                      counterText: '',
                      prefixIcon: Icon(Icons.phone_iphone_rounded),
                    ),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 10),
                    Text(_error!, style: const TextStyle(color: LoveColors.roseDeep)),
                  ],
                  const SizedBox(height: 24),
                  FilledButton(onPressed: _start, child: const Text('शुरू करें  →')),
                  const SizedBox(height: 18),
                  Row(
                    children: [
                      const Icon(Icons.lock_outline_rounded, size: 16, color: LoveColors.muted),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          'आपकी जानकारी सिर्फ़ उसी पार्टनर को दिखेगी जिसे आप ख़ुद जोड़ेंगे।',
                          style: t.bodySmall?.copyWith(color: LoveColors.muted),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
