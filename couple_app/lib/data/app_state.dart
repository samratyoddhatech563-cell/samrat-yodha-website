import 'package:flutter/material.dart';

import 'love_id.dart';

/// लोकेशन कितनी शेयर हो रही है — फ़ैसला हमेशा शेयर करने वाले के हाथ में।
enum LocationShare { off, cityOnly, live }

enum Answer { yes, no, later }

class PartnerStatus {
  PartnerStatus({
    required this.name,
    required this.city,
    required this.area,
    required this.tempC,
    required this.weather,
    required this.weatherIcon,
    required this.battery,
    required this.statusText,
    required this.mood,
    required this.updatedAt,
    required this.location,
  });

  final String name;
  final String city;
  final String area;
  final int tempC;
  final String weather;
  final String weatherIcon;
  final int battery;
  final String statusText;
  final String mood;
  final DateTime updatedAt;
  final LocationShare location;
}

class Question {
  Question({required this.text, required this.emoji, required this.at, this.answer, this.note});

  final String text;
  final String emoji;
  final DateTime at;
  Answer? answer;
  String? note;
}

class Schedule {
  Schedule({required this.text, required this.emoji, required this.time, this.enabled = true});

  final String text;
  final String emoji;
  final TimeOfDay time;
  bool enabled;
}

const presetQuestions = <(String, String)>[
  ('🍛', 'खाना खाया?'),
  ('📍', 'क्या कर रहे हो?'),
  ('💊', 'दवाई ली?'),
  ('💧', 'पानी पिया?'),
  ('😴', 'नींद कैसी आई?'),
  ('🏠', 'घर पहुँच गए?'),
];

/// पूरे ऐप की स्थिति। अभी यह डेमो डेटा से चलती है;
/// अगले चरण में यही काम Supabase (रियल-टाइम) करेगा।
class AppState extends ChangeNotifier {
  AppState();

  /// डेमो लिंक, ताकि बिना फ़ॉर्म भरे कोई भी स्क्रीन सीधे खुल सके:
  /// `?demo=pair`, `?demo=home`, `?demo=questions`, `?demo=settings`।
  factory AppState.fromDemoLink(Uri uri) {
    final s = AppState();
    final demo = uri.queryParameters['demo'];
    if (demo == null) return s;
    s.signUp('राहुल');
    if (demo == 'pair') return s;
    s.pairWith('ML-1234-5670');
    s.initialTab = switch (demo) { 'questions' => 1, 'settings' => 2, _ => 0 };
    return s;
  }

  int initialTab = 0;
  String? myName;
  String? myId;
  String? partnerId;
  DateTime togetherSince = DateTime.now().subtract(const Duration(days: 412));

  LocationShare myLocationShare = LocationShare.cityOnly;
  bool shareBattery = true;
  bool shareStatus = true;
  int historyDays = 7;
  String myStatus = 'ऑफ़िस में हूँ';
  String myMood = '😊';

  PartnerStatus partner = PartnerStatus(
    name: 'प्रिया',
    city: 'लखनऊ',
    area: 'हज़रतगंज',
    tempC: 28,
    weather: 'हल्की बारिश',
    weatherIcon: '🌦️',
    battery: 64,
    statusText: 'कॉलेज से लौट रही हूँ',
    mood: '🥰',
    updatedAt: DateTime.now().subtract(const Duration(minutes: 3)),
    location: LocationShare.live,
  );

  final List<Question> inbox = [
    Question(
        text: 'खाना खाया?',
        emoji: '🍛',
        at: DateTime.now().subtract(const Duration(minutes: 12))),
    Question(
        text: 'पानी पिया?',
        emoji: '💧',
        at: DateTime.now().subtract(const Duration(hours: 2)),
        answer: Answer.yes),
  ];

  final List<Question> sent = [
    Question(
        text: 'घर पहुँच गए?',
        emoji: '🏠',
        at: DateTime.now().subtract(const Duration(minutes: 40)),
        answer: Answer.yes,
        note: 'हाँ, अभी पहुँची ❤️'),
  ];

  final List<Schedule> schedules = [
    Schedule(text: 'खाना खाया?', emoji: '🍛', time: const TimeOfDay(hour: 13, minute: 30)),
    Schedule(text: 'दवाई ली?', emoji: '💊', time: const TimeOfDay(hour: 21, minute: 0)),
    Schedule(
        text: 'नींद कैसी आई?',
        emoji: '😴',
        time: const TimeOfDay(hour: 8, minute: 0),
        enabled: false),
  ];

  bool get isPaired => partnerId != null;
  int get daysTogether => DateTime.now().difference(togetherSince).inDays;

  void signUp(String name) {
    myName = name.trim();
    myId = LoveId.generate();
    notifyListeners();
  }

  void pairWith(String id) {
    partnerId = LoveId.normalize(id);
    notifyListeners();
  }

  void unpair() {
    partnerId = null;
    notifyListeners();
  }

  void answer(Question q, Answer a, {String? note}) {
    q.answer = a;
    q.note = (note == null || note.trim().isEmpty) ? null : note.trim();
    notifyListeners();
  }

  void ask(String emoji, String text) {
    sent.insert(0, Question(text: text, emoji: emoji, at: DateTime.now()));
    notifyListeners();
  }

  void addSchedule(Schedule s) {
    schedules.add(s);
    schedules.sort((a, b) =>
        (a.time.hour * 60 + a.time.minute).compareTo(b.time.hour * 60 + b.time.minute));
    notifyListeners();
  }

  void toggleSchedule(Schedule s, bool v) {
    s.enabled = v;
    notifyListeners();
  }

  void setLocationShare(LocationShare v) {
    myLocationShare = v;
    notifyListeners();
  }

  void pauseAll() {
    myLocationShare = LocationShare.off;
    shareBattery = false;
    shareStatus = false;
    notifyListeners();
  }

  void update(VoidCallback change) {
    change();
    notifyListeners();
  }
}

class AppScope extends InheritedNotifier<AppState> {
  const AppScope({super.key, required AppState state, required super.child})
      : super(notifier: state);

  static AppState of(BuildContext context) =>
      context.dependOnInheritedWidgetOfExactType<AppScope>()!.notifier!;
}

String timeAgo(DateTime t) {
  final d = DateTime.now().difference(t);
  if (d.inMinutes < 1) return 'अभी';
  if (d.inMinutes < 60) return '${d.inMinutes} मिनट पहले';
  if (d.inHours < 24) return '${d.inHours} घंटे पहले';
  return '${d.inDays} दिन पहले';
}

String formatTime(TimeOfDay t) {
  final h = t.hourOfPeriod == 0 ? 12 : t.hourOfPeriod;
  final m = t.minute.toString().padLeft(2, '0');
  final p = t.period == DayPeriod.am ? 'सुबह' : (t.hour < 17 ? 'दोपहर' : 'शाम');
  return '$p $h:$m';
}
