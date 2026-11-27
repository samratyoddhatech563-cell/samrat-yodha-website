import 'package:flutter/material.dart';

import '../data/app_state.dart';
import '../theme.dart';
import '../widgets/common.dart';

class QuestionsTab extends StatelessWidget {
  const QuestionsTab({super.key});

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final t = Theme.of(context).textTheme;
    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 12, 18, 24),
      children: [
        Text('सवाल और चेक-इन', style: t.headlineSmall?.copyWith(fontWeight: FontWeight.w800)),
        Text('एक टैप में पूछें, एक टैप में जवाब दें',
            style: t.bodyMedium?.copyWith(color: LoveColors.muted)),
        SectionTitle('${s.partner.name} ने पूछा'),
        for (final q in s.inbox) ...[
          _IncomingCard(q: q),
          const SizedBox(height: 10),
        ],
        SectionTitle('${s.partner.name} से पूछें'),
        const _AskPanel(),
        SectionTitle('ऑटो शेड्यूल',
            trailing: TextButton.icon(
              onPressed: () => _addSchedule(context, s),
              icon: const Icon(Icons.add_rounded),
              label: const Text('नया'),
            )),
        Text('तय समय पर सवाल अपने आप ${s.partner.name} तक पहुँच जाएगा।',
            style: t.bodySmall?.copyWith(color: LoveColors.muted)),
        const SizedBox(height: 10),
        Card(
          child: Column(
            children: [
              for (final (i, sc) in s.schedules.indexed) ...[
                if (i > 0) const Divider(height: 1, indent: 16, endIndent: 16),
                SwitchListTile(
                  value: sc.enabled,
                  onChanged: (v) => s.toggleSchedule(sc, v),
                  secondary: Text(sc.emoji, style: const TextStyle(fontSize: 24)),
                  title: Text(sc.text, style: const TextStyle(fontWeight: FontWeight.w600)),
                  subtitle: Text('रोज़ ${formatTime(sc.time)}'),
                ),
              ],
            ],
          ),
        ),
        SectionTitle('भेजे गए सवाल'),
        Card(
          child: Column(
            children: [
              for (final (i, q) in s.sent.indexed) ...[
                if (i > 0) const Divider(height: 1, indent: 16, endIndent: 16),
                ListTile(
                  leading: Text(q.emoji, style: const TextStyle(fontSize: 22)),
                  title: Text(q.text),
                  subtitle: Text(q.note ?? (q.answer == null ? 'जवाब का इंतज़ार' : timeAgo(q.at))),
                  trailing: Text(timeAgo(q.at),
                      style: const TextStyle(color: LoveColors.muted, fontSize: 12)),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }

  Future<void> _addSchedule(BuildContext context, AppState s) async {
    var choice = presetQuestions.first;
    final picked = await showModalBottomSheet<(String, String)>(
      context: context,
      showDragHandle: true,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) => Padding(
          padding: const EdgeInsets.fromLTRB(20, 0, 20, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('कौन-सा सवाल रोज़ भेजें?',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 17)),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final p in presetQuestions)
                    ChoiceChip(
                      label: Text('${p.$1} ${p.$2}'),
                      selected: choice == p,
                      onSelected: (_) => setSheet(() => choice = p),
                    ),
                ],
              ),
              const SizedBox(height: 18),
              FilledButton(
                onPressed: () => Navigator.pop(ctx, choice),
                child: const Text('समय चुनें'),
              ),
            ],
          ),
        ),
      ),
    );
    if (picked == null || !context.mounted) return;
    final time = await showTimePicker(
      context: context,
      initialTime: const TimeOfDay(hour: 13, minute: 30),
      helpText: 'रोज़ किस समय भेजें?',
    );
    if (time == null || !context.mounted) return;
    s.addSchedule(Schedule(emoji: picked.$1, text: picked.$2, time: time));
    showToast(context, '“${picked.$2}” रोज़ ${formatTime(time)} पर जाएगा');
  }
}

class _IncomingCard extends StatefulWidget {
  const _IncomingCard({required this.q});

  final Question q;

  @override
  State<_IncomingCard> createState() => _IncomingCardState();
}

class _IncomingCardState extends State<_IncomingCard> {
  final _note = TextEditingController();

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    final q = widget.q;
    final answered = q.answer != null;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(q.emoji, style: const TextStyle(fontSize: 28)),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(q.text,
                          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 17)),
                      Text(timeAgo(q.at),
                          style: const TextStyle(color: LoveColors.muted, fontSize: 12)),
                    ],
                  ),
                ),
                if (answered) const Pill(label: 'जवाब दिया ✓', color: LoveColors.mint),
              ],
            ),
            if (!answered) ...[
              const SizedBox(height: 14),
              Row(
                children: [
                  _answerBtn('✅ हाँ', LoveColors.mint, () => _reply(s, Answer.yes)),
                  const SizedBox(width: 8),
                  _answerBtn('❌ नहीं', LoveColors.roseDeep, () => _reply(s, Answer.no)),
                  const SizedBox(width: 8),
                  _answerBtn('⏳ बाद में', LoveColors.amber, () => _reply(s, Answer.later)),
                ],
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _note,
                decoration: const InputDecoration(
                  hintText: 'कुछ और लिखें… (जैसे: दाल-चावल 😋)',
                  isDense: true,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _answerBtn(String label, Color c, VoidCallback onTap) {
    return Expanded(
      child: OutlinedButton(
        style: OutlinedButton.styleFrom(
          foregroundColor: c,
          side: BorderSide(color: c.withValues(alpha: 0.4)),
          padding: const EdgeInsets.symmetric(vertical: 12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        ),
        onPressed: onTap,
        child: Text(label, style: const TextStyle(fontWeight: FontWeight.w700)),
      ),
    );
  }

  void _reply(AppState s, Answer a) {
    s.answer(widget.q, a, note: _note.text);
    showToast(context, 'जवाब ${s.partner.name} को भेज दिया गया');
  }
}

class _AskPanel extends StatefulWidget {
  const _AskPanel();

  @override
  State<_AskPanel> createState() => _AskPanelState();
}

class _AskPanelState extends State<_AskPanel> {
  final _custom = TextEditingController();

  @override
  Widget build(BuildContext context) {
    final s = AppScope.of(context);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final (emoji, text) in presetQuestions)
                  ActionChip(
                    avatar: Text(emoji),
                    label: Text(text),
                    onPressed: () {
                      s.ask(emoji, text);
                      showToast(context, '“$text” भेज दिया गया');
                    },
                  ),
              ],
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _custom,
              decoration: InputDecoration(
                hintText: 'अपना सवाल लिखें…',
                suffixIcon: IconButton(
                  icon: const Icon(Icons.send_rounded, color: LoveColors.rose),
                  onPressed: () {
                    final text = _custom.text.trim();
                    if (text.isEmpty) return;
                    s.ask('💬', text);
                    _custom.clear();
                    showToast(context, 'सवाल भेज दिया गया');
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
