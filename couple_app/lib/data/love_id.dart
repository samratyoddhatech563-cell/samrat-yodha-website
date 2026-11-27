import 'dart:math';

/// हर यूज़र की यूनिक "Love ID", जैसे `ML-4827-1193`.
///
/// आख़िरी अंक एक चेक-डिजिट है, ताकि टाइप करने में हुई गलती
/// (एक अंक गलत या दो अंक आगे-पीछे) तुरंत पकड़ी जा सके।
class LoveId {
  static const prefix = 'ML';
  static final _pattern = RegExp(r'^ML-(\d{4})-(\d{4})$');

  /// 7 अंकों के लिए चेक-डिजिट (Luhn की तरह, स्थान के हिसाब से वज़न)।
  static int checkDigit(String sevenDigits) {
    var sum = 0;
    for (var i = 0; i < sevenDigits.length; i++) {
      final d = sevenDigits.codeUnitAt(i) - 48;
      sum += d * (i + 1);
    }
    return sum % 10;
  }

  static String generate([Random? random]) {
    final r = random ?? Random.secure();
    final body = List.generate(7, (_) => r.nextInt(10)).join();
    final digits = '$body${checkDigit(body)}';
    return '$prefix-${digits.substring(0, 4)}-${digits.substring(4)}';
  }

  /// यूज़र जैसे भी टाइप करे (छोटे अक्षर, स्पेस, बिना डैश) उसे सही रूप में लाता है।
  static String normalize(String input) {
    final digits = input.replaceAll(RegExp(r'[^0-9]'), '');
    if (digits.length != 8) return input.trim().toUpperCase();
    return '$prefix-${digits.substring(0, 4)}-${digits.substring(4)}';
  }

  static bool isValid(String input) {
    final m = _pattern.firstMatch(normalize(input));
    if (m == null) return false;
    final digits = '${m.group(1)}${m.group(2)}';
    return checkDigit(digits.substring(0, 7)) == digits.codeUnitAt(7) - 48;
  }
}
