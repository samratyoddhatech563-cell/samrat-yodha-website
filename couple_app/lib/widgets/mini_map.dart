import 'package:flutter/material.dart';

import '../theme.dart';

/// नक्शे की जगह एक हल्का चित्र। असली ऐप में यहाँ Google Maps आएगा।
class MiniMap extends StatelessWidget {
  const MiniMap({super.key, required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        CustomPaint(painter: _MapPainter()),
        Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 46,
                height: 46,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  gradient: LoveColors.heroGradient,
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 3),
                  boxShadow: [
                    BoxShadow(color: LoveColors.rose.withValues(alpha: 0.4), blurRadius: 16),
                  ],
                ),
                child: Text(label,
                    style: const TextStyle(
                        color: Colors.white, fontWeight: FontWeight.w800, fontSize: 18)),
              ),
              CustomPaint(size: const Size(14, 8), painter: _PinTail()),
            ],
          ),
        ),
      ],
    );
  }
}

class _MapPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    canvas.drawRect(Offset.zero & size, Paint()..color = const Color(0xFFF3EEF6));

    final park = Paint()..color = const Color(0xFFDDEFD9);
    canvas.drawRRect(
        RRect.fromRectAndRadius(
            Rect.fromLTWH(size.width * 0.06, size.height * 0.12, size.width * 0.22, 60),
            const Radius.circular(14)),
        park);
    canvas.drawRRect(
        RRect.fromRectAndRadius(
            Rect.fromLTWH(size.width * 0.70, size.height * 0.58, size.width * 0.24, 54),
            const Radius.circular(14)),
        park);

    final water = Paint()
      ..color = const Color(0xFFCFE3F7)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 16
      ..strokeCap = StrokeCap.round;
    final river = Path()
      ..moveTo(-10, size.height * 0.85)
      ..quadraticBezierTo(size.width * 0.35, size.height * 0.62, size.width + 10, size.height * 0.92);
    canvas.drawPath(river, water);

    final road = Paint()
      ..color = Colors.white
      ..strokeWidth = 9
      ..strokeCap = StrokeCap.round;
    canvas.drawLine(Offset(0, size.height * 0.42), Offset(size.width, size.height * 0.30), road);
    canvas.drawLine(Offset(size.width * 0.38, 0), Offset(size.width * 0.58, size.height), road);
    final minor = Paint()
      ..color = Colors.white
      ..strokeWidth = 4;
    canvas.drawLine(Offset(size.width * 0.78, 0), Offset(size.width * 0.72, size.height), minor);
    canvas.drawLine(Offset(0, size.height * 0.70), Offset(size.width, size.height * 0.62), minor);

    final ring = Paint()..color = LoveColors.rose.withValues(alpha: 0.10);
    canvas.drawCircle(Offset(size.width / 2, size.height / 2 - 4), 58, ring);
    canvas.drawCircle(Offset(size.width / 2, size.height / 2 - 4), 36, ring);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _PinTail extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final path = Path()
      ..moveTo(0, 0)
      ..lineTo(size.width, 0)
      ..lineTo(size.width / 2, size.height)
      ..close();
    canvas.drawPath(path, Paint()..color = LoveColors.plum);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
