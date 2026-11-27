import 'dart:math';

import 'package:flutter_test/flutter_test.dart';
import 'package:my_love/data/love_id.dart';

void main() {
  test('बनी हुई ID सही फ़ॉर्मेट में और वैध होती है', () {
    final r = Random(7);
    for (var i = 0; i < 500; i++) {
      final id = LoveId.generate(r);
      expect(id, matches(RegExp(r'^ML-\d{4}-\d{4}$')));
      expect(LoveId.isValid(id), isTrue, reason: id);
    }
  });

  test('एक अंक बदलने पर ID अमान्य हो जाती है', () {
    final id = LoveId.generate(Random(1));
    final digits = id.replaceAll(RegExp(r'\D'), '');
    for (var pos = 0; pos < 8; pos++) {
      final d = int.parse(digits[pos]);
      final changed =
          digits.replaceRange(pos, pos + 1, '${(d + 1) % 10}');
      expect(LoveId.isValid(changed), isFalse, reason: changed);
    }
  });

  test('टाइप करने का तरीका मायने नहीं रखता', () {
    final id = LoveId.generate(Random(3));
    final digits = id.replaceAll(RegExp(r'\D'), '');
    expect(LoveId.isValid(digits), isTrue);
    expect(LoveId.isValid('ml ${digits.substring(0, 4)} ${digits.substring(4)}'),
        isTrue);
    expect(LoveId.normalize(digits), id);
  });

  test('गलत इनपुट अमान्य है', () {
    expect(LoveId.isValid(''), isFalse);
    expect(LoveId.isValid('ML-123-4567'), isFalse);
    expect(LoveId.isValid('hello'), isFalse);
  });
}
