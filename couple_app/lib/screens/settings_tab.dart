import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../data/app_state.dart';
import '../theme.dart';
import '../widgets/common.dart';

class SettingsTab extends StatelessWidget {
  const SettingsTab({super.key});

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final t = Theme.of(context).textTheme;
    final sharingSomething =
        s.myLocationShare != LocationShare.off || s.shareBattery || s.shareStatus;
    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 12, 18, 24),
      children: [
        Text('सेटिंग्स और प्राइवेसी',
            style: t.headlineSmall?.copyWith(fontWeight: FontWeight.w800)),
        Text('आप क्या शेयर करें, यह सिर्फ़ आप तय करते हैं',
            style: t.bodyMedium?.copyWith(color: LoveColors.muted)),
        const SizedBox(height: 16),
        Card(
          child: ListTile(
            contentPadding: const EdgeInsets.fromLTRB(16, 6, 8, 6),
            leading: Avatar(letter: s.myName!.characters.first, color: LoveColors.plum, size: 46),
            title: Text(s.myName!, style: const TextStyle(fontWeight: FontWeight.w700)),
            subtitle: Text('Love ID: ${s.myId}'),
            trailing: IconButton(
              icon: const Icon(Icons.copy_rounded),
              onPressed: () {
                Clipboard.setData(ClipboardData(text: s.myId!));
                showToast(context, 'ID कॉपी हो गई');
              },
            ),
          ),
        ),
        const SectionTitle('मेरी लोकेशन'),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                SegmentedButton<LocationShare>(
                  showSelectedIcon: false,
                  segments: const [
                    ButtonSegment(value: LocationShare.off, label: Text('बंद')),
                    ButtonSegment(value: LocationShare.cityOnly, label: Text('सिर्फ़ शहर')),
                    ButtonSegment(value: LocationShare.live, label: Text('लाइव')),
                  ],
                  selected: {s.myLocationShare},
                  onSelectionChanged: (v) => _changeLocation(context, s, v.first),
                ),
                const SizedBox(height: 12),
                Text(
                  switch (s.myLocationShare) {
                    LocationShare.off => '${s.partner.name} को आपकी लोकेशन नहीं दिखेगी।',
                    LocationShare.cityOnly =>
                      '${s.partner.name} को सिर्फ़ आपका शहर और वहाँ का मौसम दिखेगा।',
                    LocationShare.live =>
                      '${s.partner.name} नक्शे पर आपकी लाइव लोकेशन देख सकते हैं। फ़ोन में "लोकेशन शेयर हो रही है" की सूचना लगातार दिखेगी।',
                  },
                  style: const TextStyle(color: LoveColors.muted),
                ),
              ],
            ),
          ),
        ),
        const SectionTitle('और क्या शेयर करें'),
        Card(
          child: Column(
            children: [
              SwitchListTile(
                secondary: const Icon(Icons.battery_5_bar_rounded),
                title: const Text('बैटरी'),
                value: s.shareBattery,
                onChanged: (v) => s.update(() => s.shareBattery = v),
              ),
              const Divider(height: 1, indent: 16, endIndent: 16),
              SwitchListTile(
                secondary: const Icon(Icons.emoji_emotions_outlined),
                title: const Text('स्टेटस और मूड'),
                value: s.shareStatus,
                onChanged: (v) => s.update(() => s.shareStatus = v),
              ),
              const Divider(height: 1, indent: 16, endIndent: 16),
              ListTile(
                leading: const Icon(Icons.history_rounded),
                title: const Text('लोकेशन हिस्ट्री रखें'),
                trailing: DropdownButton<int>(
                  value: s.historyDays,
                  underline: const SizedBox(),
                  items: const [
                    DropdownMenuItem(value: 0, child: Text('बिल्कुल नहीं')),
                    DropdownMenuItem(value: 1, child: Text('1 दिन')),
                    DropdownMenuItem(value: 7, child: Text('7 दिन')),
                  ],
                  onChanged: (v) => s.update(() => s.historyDays = v ?? 7),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        FilledButton.tonalIcon(
          style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)),
          onPressed: sharingSomething
              ? () {
                  s.pauseAll();
                  showToast(context, 'सारी शेयरिंग रोक दी गई। ${s.partner.name} को सूचना भेजी गई।');
                }
              : null,
          icon: const Icon(Icons.pause_circle_outline_rounded),
          label: const Text('सारी शेयरिंग अभी रोकें'),
        ),
        const SectionTitle('जोड़ी'),
        Card(
          child: Column(
            children: [
              ListTile(
                leading: Avatar(letter: s.partner.name.characters.first, size: 42),
                title: Text('${s.partner.name} से जुड़े हैं'),
                subtitle: Text('Love ID: ${s.partnerId}'),
              ),
              const Divider(height: 1, indent: 16, endIndent: 16),
              ListTile(
                leading: const Icon(Icons.link_off_rounded, color: LoveColors.roseDeep),
                title: const Text('जोड़ी तोड़ें (Unpair)',
                    style: TextStyle(color: LoveColors.roseDeep, fontWeight: FontWeight.w600)),
                subtitle: const Text('इसके बाद कोई किसी की जानकारी नहीं देख पाएगा'),
                onTap: () => _confirmUnpair(context, s),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Future<void> _changeLocation(BuildContext context, AppState s, LocationShare v) async {
    if (v == LocationShare.live) {
      final ok = await showDialog<bool>(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text('लाइव लोकेशन चालू करें?'),
          content: Text(
              '${s.partner.name} आपकी लाइव लोकेशन तब भी देख सकेंगे जब ऐप बंद हो। आप इसे कभी भी यहीं से बंद कर सकते हैं, और बंद करते ही उन्हें पता चल जाएगा।'),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('अभी नहीं')),
            FilledButton(
              style: FilledButton.styleFrom(minimumSize: const Size(0, 44)),
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('हाँ, चालू करें'),
            ),
          ],
        ),
      );
      if (ok != true) return;
    }
    s.setLocationShare(v);
  }

  Future<void> _confirmUnpair(BuildContext context, AppState s) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('जोड़ी तोड़ें?'),
        content: Text(
            '${s.partner.name} के साथ शेयर हो रही सारी जानकारी तुरंत बंद हो जाएगी और पुरानी लोकेशन हिस्ट्री मिट जाएगी।'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('रहने दें')),
          FilledButton(
            style: FilledButton.styleFrom(
                backgroundColor: LoveColors.roseDeep, minimumSize: const Size(0, 44)),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('हाँ, तोड़ें'),
          ),
        ],
      ),
    );
    if (ok == true) s.unpair();
  }
}
