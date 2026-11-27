import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class LoveColors {
  static const rose = Color(0xFFE8476A);
  static const roseDeep = Color(0xFFC72C55);
  static const plum = Color(0xFF7B4BD6);
  static const blush = Color(0xFFFFF5F7);
  static const petal = Color(0xFFFFE3EA);
  static const ink = Color(0xFF2B1B2E);
  static const muted = Color(0xFF7A6A7E);
  static const mint = Color(0xFF2BB673);
  static const amber = Color(0xFFF2A33A);

  static const heroGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [rose, Color(0xFFB54BC9), plum],
  );
}

ThemeData buildTheme() {
  final base = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: LoveColors.rose,
      primary: LoveColors.rose,
      secondary: LoveColors.plum,
      surface: Colors.white,
    ),
    scaffoldBackgroundColor: LoveColors.blush,
  );
  final text = base.textTheme.apply(
    fontFamily: GoogleFonts.hind().fontFamily,
    bodyColor: LoveColors.ink,
    displayColor: LoveColors.ink,
  );
  return base.copyWith(
    textTheme: text,
    appBarTheme: AppBarTheme(
      backgroundColor: LoveColors.blush,
      foregroundColor: LoveColors.ink,
      elevation: 0,
      centerTitle: false,
      titleTextStyle: text.titleLarge?.copyWith(fontWeight: FontWeight.w700),
    ),
    cardTheme: CardThemeData(
      color: Colors.white,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(22),
        side: const BorderSide(color: Color(0xFFF6DCE3)),
      ),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size.fromHeight(54),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        textStyle: text.titleMedium?.copyWith(fontWeight: FontWeight.w700),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Color(0xFFF0D3DB)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(16),
        borderSide: const BorderSide(color: Color(0xFFF0D3DB)),
      ),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Colors.white,
      indicatorColor: LoveColors.petal,
      labelTextStyle: WidgetStatePropertyAll(
        text.labelMedium?.copyWith(fontWeight: FontWeight.w600),
      ),
    ),
  );
}
