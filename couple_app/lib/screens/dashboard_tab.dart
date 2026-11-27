import 'package:flutter/material.dart';

import '../data/app_state.dart';
import '../theme.dart';
import '../widgets/common.dart';
import '../widgets/mini_map.dart';

class DashboardTab extends StatelessWidget {
  const DashboardTab({super.key, required this.onOpenQuestions});

  final VoidCallback onOpenQuestions;

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final p = s.partner;
    final t = Theme.of(context).textTheme;
    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 12, 18, 24),
      children: [
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('नमस्ते, ${s.myName}',
                      style: t.headlineSmall?.copyWith(fontWeight: FontWeight.w800)),
                  Text('${p.name} अभी ${p.city} में हैं',
                      style: t.bodyMedium?.copyWith(color: LoveColors.muted)),
                ],
              ),
            ),
            IconButton.filledTonal(
              onPressed: () => showToast(context, 'कोई नई सूचना नहीं'),
              icon: const Icon(Icons.notifications_none_rounded),
            ),
          ],
        ),
        const SizedBox(height: 16),
        _CoupleHero(state: s),
        const SizedBox(height: 14),
        _PartnerStatusCard(p: p),
        const SizedBox(height: 14),
        _QuickActions(onAsk: onOpenQuestions),
        const SectionTitle('लाइव लोकेशन'),
        _LocationCard(p: p),
        SectionTitle('आज के चेक-इन',
            trailing: TextButton(onPressed: onOpenQuestions, child: const Text('सब देखें'))),
        _TodaySummary(state: s),
        const SectionTitle('मेरा स्टेटस'),
        _MyStatusCard(state: s),
      ],
    );
  }
}

class _CoupleHero extends StatelessWidget {
  const _CoupleHero({required this.state});

  final AppState state;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 18),
      decoration: BoxDecoration(
        gradient: LoveColors.heroGradient,
        borderRadius: BorderRadius.circular(26),
        boxShadow: [
          BoxShadow(
              color: LoveColors.plum.withValues(alpha: 0.25),
              blurRadius: 24,
              offset: const Offset(0, 10)),
        ],
      ),
      child: Row(
        children: [
          SizedBox(
            width: 92,
            height: 56,
            child: Stack(
              children: [
                Avatar(letter: state.myName!.characters.first, color: LoveColors.plum),
                Positioned(
                  left: 38,
                  child: Avatar(letter: state.partner.name.characters.first),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${state.daysTogether} दिन',
                    style: const TextStyle(
                        color: Colors.white, fontSize: 28, fontWeight: FontWeight.w800, height: 1.1)),
                const Text('साथ में ❤️  ·  सालगिरह 23 दिन में',
                    style: TextStyle(color: Colors.white70, fontWeight: FontWeight.w500)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PartnerStatusCard extends StatelessWidget {
  const _PartnerStatusCard({required this.p});

  final PartnerStatus p;

  @override
  Widget build(BuildContext context) {
    final t = Theme.of(context).textTheme;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(p.mood, style: const TextStyle(fontSize: 30)),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('${p.name} क्या कर रही हैं',
                          style: t.labelLarge?.copyWith(color: LoveColors.muted)),
                      Text('“${p.statusText}”',
                          style: t.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _Stat(
                    icon: p.weatherIcon,
                    value: '${p.tempC}°C',
                    label: p.weather,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _Stat(
                    icon: '🔋',
                    value: '${p.battery}%',
                    label: 'बैटरी',
                    valueColor: p.battery < 20 ? LoveColors.roseDeep : LoveColors.mint,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _Stat(icon: '📍', value: p.area, label: p.city),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                const Icon(Icons.schedule_rounded, size: 14, color: LoveColors.muted),
                const SizedBox(width: 4),
                Text('अपडेट: ${timeAgo(p.updatedAt)}',
                    style: t.bodySmall?.copyWith(color: LoveColors.muted)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.icon, required this.value, required this.label, this.valueColor});

  final String icon;
  final String value;
  final String label;
  final Color? valueColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
      decoration: BoxDecoration(
        color: LoveColors.blush,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(icon, style: const TextStyle(fontSize: 20)),
          const SizedBox(height: 6),
          Text(value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                  fontWeight: FontWeight.w800, fontSize: 16, color: valueColor ?? LoveColors.ink)),
          Text(label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 12, color: LoveColors.muted)),
        ],
      ),
    );
  }
}

class _QuickActions extends StatelessWidget {
  const _QuickActions({required this.onAsk});

  final VoidCallback onAsk;

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    return Row(
      children: [
        Expanded(
          child: _ActionTile(
            emoji: '💓',
            label: 'Miss You',
            color: LoveColors.rose,
            onTap: () => showToast(context, '${s.partner.name} के फ़ोन पर 💓 पहुँच गया'),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _ActionTile(
            emoji: '🍛',
            label: 'खाना खाया?',
            color: LoveColors.amber,
            onTap: () {
              s.ask('🍛', 'खाना खाया?');
              showToast(context, 'सवाल भेज दिया गया');
            },
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _ActionTile(
            emoji: '🆘',
            label: 'SOS',
            color: LoveColors.roseDeep,
            onTap: () => _confirmSos(context, s),
          ),
        ),
      ],
    );
  }

  void _confirmSos(BuildContext context, AppState s) {
    showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('SOS भेजें?'),
        content: Text('${s.partner.name} को आपकी अभी की लोकेशन के साथ इमरजेंसी अलर्ट जाएगा।'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('रहने दें')),
          FilledButton(
            style: FilledButton.styleFrom(
                backgroundColor: LoveColors.roseDeep, minimumSize: const Size(0, 44)),
            onPressed: () {
              Navigator.pop(ctx);
              showToast(context, 'SOS अलर्ट भेज दिया गया');
            },
            child: const Text('हाँ, भेजें'),
          ),
        ],
      ),
    );
  }
}

class _ActionTile extends StatelessWidget {
  const _ActionTile(
      {required this.emoji, required this.label, required this.color, required this.onTap});

  final String emoji;
  final String label;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        borderRadius: BorderRadius.circular(20),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: color.withValues(alpha: 0.25)),
          ),
          child: Column(
            children: [
              Text(emoji, style: const TextStyle(fontSize: 24)),
              const SizedBox(height: 4),
              Text(label,
                  style: TextStyle(fontWeight: FontWeight.w700, color: color, fontSize: 13)),
            ],
          ),
        ),
      ),
    );
  }
}

class _LocationCard extends StatelessWidget {
  const _LocationCard({required this.p});

  final PartnerStatus p;

  @override
  Widget build(BuildContext context) {
    final live = p.location == LocationShare.live;
    return Card(
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          SizedBox(height: 170, child: MiniMap(label: p.name.characters.first)),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('${p.area}, ${p.city}',
                          style: const TextStyle(fontWeight: FontWeight.w700)),
                      const Text('आपसे लगभग 498 किमी दूर',
                          style: TextStyle(color: LoveColors.muted, fontSize: 12.5)),
                    ],
                  ),
                ),
                Pill(
                  label: live ? 'लाइव शेयरिंग चालू' : 'सिर्फ़ शहर',
                  icon: live ? Icons.circle : Icons.location_city_rounded,
                  color: live ? LoveColors.mint : LoveColors.plum,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _TodaySummary extends StatelessWidget {
  const _TodaySummary({required this.state});

  final AppState state;

  @override
  Widget build(BuildContext context) {
    final items = [...state.sent.take(2), ...state.inbox.take(1)];
    return Card(
      child: Column(
        children: [
          for (final (i, q) in items.indexed) ...[
            if (i > 0) const Divider(height: 1, indent: 16, endIndent: 16),
            ListTile(
              leading: Text(q.emoji, style: const TextStyle(fontSize: 24)),
              title: Text(q.text, style: const TextStyle(fontWeight: FontWeight.w600)),
              subtitle: Text(q.note ?? timeAgo(q.at)),
              trailing: _AnswerBadge(answer: q.answer),
            ),
          ],
        ],
      ),
    );
  }
}

class _AnswerBadge extends StatelessWidget {
  const _AnswerBadge({required this.answer});

  final Answer? answer;

  @override
  Widget build(BuildContext context) {
    return switch (answer) {
      Answer.yes => const Pill(label: 'हाँ ✅', color: LoveColors.mint),
      Answer.no => const Pill(label: 'नहीं', color: LoveColors.roseDeep),
      Answer.later => const Pill(label: 'बाद में', color: LoveColors.amber),
      null => const Pill(label: 'जवाब बाकी', color: LoveColors.muted),
    };
  }
}

class _MyStatusCard extends StatelessWidget {
  const _MyStatusCard({required this.state});

  final AppState state;

  static const moods = ['😊', '🥰', '😴', '😔', '🤒', '😎'];
  static const quick = ['ऑफ़िस में हूँ', 'खाना खा रहा हूँ', 'रास्ते में हूँ', 'घर पर हूँ', 'सो रहा हूँ'];

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                for (final m in moods)
                  GestureDetector(
                    onTap: () => state.update(() => state.myMood = m),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: state.myMood == m ? LoveColors.petal : Colors.transparent,
                        shape: BoxShape.circle,
                      ),
                      child: Text(m, style: const TextStyle(fontSize: 26)),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final q in quick)
                  ChoiceChip(
                    label: Text(q),
                    selected: state.myStatus == q,
                    onSelected: (_) {
                      state.update(() => state.myStatus = q);
                      showToast(context, '${state.partner.name} को आपका स्टेटस दिख रहा है');
                    },
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
