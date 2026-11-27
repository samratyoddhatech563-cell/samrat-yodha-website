import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:my_love/data/app_state.dart';
import 'package:my_love/main.dart';

void main() {
  setUpAll(() => GoogleFonts.config.allowRuntimeFetching = false);

  Future<void> pumpApp(WidgetTester tester, AppState state) async {
    tester.view.physicalSize = const Size(1170, 2532);
    tester.view.devicePixelRatio = 3;
    addTearDown(tester.view.reset);
    await tester.pumpWidget(MyLoveApp(state: state));
    await tester.pumpAndSettle();
  }

  testWidgets('साइन-अप → ID मिलती है → पार्टनर से जुड़ते हैं → होम', (tester) async {
    final state = AppState();
    await pumpApp(tester, state);

    await tester.enterText(find.byType(TextField).at(0), 'राहुल');
    await tester.enterText(find.byType(TextField).at(1), '9876543210');
    await tester.tap(find.text('शुरू करें  →'));
    await tester.pumpAndSettle();

    expect(state.myId, isNotNull);
    expect(find.text(state.myId!), findsOneWidget);

    // गलत ID पर गलती दिखती है
    await tester.enterText(find.byType(TextField), 'ML-1234-0001');
    await tester.tap(find.text('जुड़ने का अनुरोध भेजें'));
    await tester.pump();
    expect(find.textContaining('सही नहीं'), findsOneWidget);

    // अपनी ID नहीं डाल सकते
    await tester.enterText(find.byType(TextField), state.myId!);
    await tester.tap(find.text('जुड़ने का अनुरोध भेजें'));
    await tester.pump();
    expect(find.textContaining('आपकी अपनी ID'), findsOneWidget);

    await tester.enterText(find.byType(TextField), 'ML-1234-5670');
    await tester.tap(find.text('जुड़ने का अनुरोध भेजें'));
    await tester.pump();
    expect(state.isPaired, isFalse, reason: 'Accept से पहले जोड़ी नहीं बननी चाहिए');

    await tester.tap(find.text('डेमो: पार्टनर ने स्वीकार किया ✓'));
    await tester.pumpAndSettle();
    expect(state.isPaired, isTrue);
    expect(find.text('नमस्ते, राहुल'), findsOneWidget);
  });

  testWidgets('सवाल का जवाब देने पर बैज घटता है', (tester) async {
    final state = AppState()
      ..signUp('राहुल')
      ..pairWith('ML-1234-5670');
    await pumpApp(tester, state);

    await tester.tap(find.text('सवाल'));
    await tester.pumpAndSettle();
    expect(state.inbox.where((q) => q.answer == null).length, 1);

    await tester.tap(find.text('✅ हाँ'));
    await tester.pumpAndSettle();
    expect(state.inbox.first.answer, Answer.yes);
    expect(state.inbox.where((q) => q.answer == null), isEmpty);
  });

  testWidgets('लाइव लोकेशन बिना पुष्टि के चालू नहीं होती', (tester) async {
    final state = AppState()
      ..signUp('राहुल')
      ..pairWith('ML-1234-5670');
    await pumpApp(tester, state);

    await tester.tap(find.text('सेटिंग्स'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('लाइव'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('अभी नहीं'));
    await tester.pumpAndSettle();
    expect(state.myLocationShare, LocationShare.cityOnly);

    await tester.tap(find.text('लाइव'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('हाँ, चालू करें'));
    await tester.pumpAndSettle();
    expect(state.myLocationShare, LocationShare.live);

    await tester.scrollUntilVisible(find.text('सारी शेयरिंग अभी रोकें'), 200);
    await tester.tap(find.text('सारी शेयरिंग अभी रोकें'));
    await tester.pumpAndSettle();
    expect(state.myLocationShare, LocationShare.off);
    expect(state.shareBattery, isFalse);
  });
}
