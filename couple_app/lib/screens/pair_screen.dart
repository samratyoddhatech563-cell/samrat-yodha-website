import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../data/app_state.dart';
import '../data/love_id.dart';
import '../theme.dart';
import '../widgets/common.dart';

class PairScreen extends StatefulWidget {
  const PairScreen({super.key});

  @override
  State<PairScreen> createState() => _PairScreenState();
}

class _PairScreenState extends State<PairScreen> {
  final _partner = TextEditingController();
  String? _error;
  String? _waitingFor;

  void _send() {
    final s = AppScope.of(context);
    final id = LoveId.normalize(_partner.text);
    if (!LoveId.isValid(id)) {
      setState(() => _error = 'यह ID सही नहीं लग रही। दोबारा जाँच लीजिए।');
      return;
    }
    if (id == s.myId) {
      setState(() => _error = 'यह आपकी अपनी ID है 🙂 पार्टनर की ID डालिए।');
      return;
    }
    // TODO(backend): पार्टनर को जोड़ी का अनुरोध भेजें; वे Accept करें तभी जोड़ी बने।
    setState(() {
      _error = null;
      _waitingFor = id;
    });
  }

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final t = Theme.of(context).textTheme;
    return Scaffold(
      appBar: AppBar(title: const Text('पार्टनर से जुड़ें')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 4, 20, 28),
          children: [
            Text('नमस्ते ${s.myName} 👋', style: t.titleLarge?.copyWith(fontWeight: FontWeight.w700)),
            const SizedBox(height: 4),
            Text('यह आपकी यूनिक Love ID है। इसे अपने पार्टनर को दीजिए या QR स्कैन करवाइए।',
                style: t.bodyMedium?.copyWith(color: LoveColors.muted)),
            const SizedBox(height: 16),
            _MyIdCard(id: s.myId!),
            const SectionTitle('पार्टनर की ID डालें'),
            if (_waitingFor == null) ...[
              TextField(
                controller: _partner,
                textCapitalization: TextCapitalization.characters,
                decoration: InputDecoration(
                  hintText: 'ML-0000-0000',
                  prefixIcon: const Icon(Icons.favorite_border_rounded),
                  suffixIcon: IconButton(
                    tooltip: 'QR स्कैन करें',
                    icon: const Icon(Icons.qr_code_scanner_rounded),
                    onPressed: () => showToast(context, 'QR स्कैनर फ़ोन पर कैमरे से खुलेगा'),
                  ),
                  errorText: _error,
                ),
                onSubmitted: (_) => _send(),
              ),
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: _send,
                icon: const Icon(Icons.send_rounded),
                label: const Text('जुड़ने का अनुरोध भेजें'),
              ),
            ] else
              _WaitingCard(
                id: _waitingFor!,
                onAccepted: () => s.pairWith(_waitingFor!),
                onCancel: () => setState(() => _waitingFor = null),
              ),
            const SizedBox(height: 22),
            const _HowItWorks(),
          ],
        ),
      ),
    );
  }
}

class _MyIdCard extends StatelessWidget {
  const _MyIdCard({required this.id});

  final String id;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LoveColors.heroGradient,
        borderRadius: BorderRadius.circular(26),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('मेरी Love ID',
                    style: TextStyle(color: Colors.white70, fontWeight: FontWeight.w600)),
                const SizedBox(height: 6),
                FittedBox(
                  child: Text(id,
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 26,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 1.2)),
                ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: const BorderSide(color: Colors.white54),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () {
                    Clipboard.setData(ClipboardData(text: id));
                    showToast(context, 'ID कॉपी हो गई');
                  },
                  icon: const Icon(Icons.copy_rounded, size: 18),
                  label: const Text('कॉपी करें'),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
                color: Colors.white, borderRadius: BorderRadius.circular(16)),
            child: QrImageView(
              data: 'mylove://pair/$id',
              size: 104,
              padding: EdgeInsets.zero,
              eyeStyle: const QrEyeStyle(eyeShape: QrEyeShape.circle, color: LoveColors.ink),
              dataModuleStyle: const QrDataModuleStyle(
                  dataModuleShape: QrDataModuleShape.circle, color: LoveColors.ink),
            ),
          ),
        ],
      ),
    );
  }
}

class _WaitingCard extends StatelessWidget {
  const _WaitingCard({required this.id, required this.onAccepted, required this.onCancel});

  final String id;
  final VoidCallback onAccepted;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          children: [
            const SizedBox(
                width: 28, height: 28, child: CircularProgressIndicator(strokeWidth: 3)),
            const SizedBox(height: 12),
            Text('$id को अनुरोध भेज दिया गया',
                textAlign: TextAlign.center,
                style: const TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 4),
            const Text('जैसे ही वे "स्वीकार करें" दबाएँगे, आप जुड़ जाएँगे।',
                textAlign: TextAlign.center, style: TextStyle(color: LoveColors.muted)),
            const SizedBox(height: 14),
            FilledButton(
              onPressed: onAccepted,
              child: const Text('डेमो: पार्टनर ने स्वीकार किया ✓'),
            ),
            TextButton(onPressed: onCancel, child: const Text('अनुरोध वापस लें')),
          ],
        ),
      ),
    );
  }
}

class _HowItWorks extends StatelessWidget {
  const _HowItWorks();

  @override
  Widget build(BuildContext context) {
    const steps = [
      ('1', 'एक-दूसरे की Love ID डालें या QR स्कैन करें'),
      ('2', 'दोनों "स्वीकार करें" दबाएँ — बिना सहमति जोड़ी नहीं बनती'),
      ('3', 'क्या शेयर करना है (लोकेशन, बैटरी, स्टेटस) हर कोई ख़ुद चुनता है'),
    ];
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('कैसे काम करता है', style: TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 12),
            for (final (n, text) in steps)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    CircleAvatar(
                      radius: 12,
                      backgroundColor: LoveColors.petal,
                      child: Text(n,
                          style: const TextStyle(
                              fontSize: 12,
                              color: LoveColors.roseDeep,
                              fontWeight: FontWeight.w800)),
                    ),
                    const SizedBox(width: 10),
                    Expanded(child: Text(text)),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}
