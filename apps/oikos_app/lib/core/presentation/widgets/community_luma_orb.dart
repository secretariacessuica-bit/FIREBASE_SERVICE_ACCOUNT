import 'package:flutter/material.dart';

class CommunityLumaOrb extends StatefulWidget {
  final double hungerLevel; // 0.0 a 1.0

  const CommunityLumaOrb({super.key, required this.hungerLevel});

  @override
  State<CommunityLumaOrb> createState() => _CommunityLumaOrbState();
}

class _CommunityLumaOrbState extends State<CommunityLumaOrb> with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      // Se tiver muita fome, pulsa rápido (urgência). Se tiver cheia, pulsa lento.
      duration: Duration(milliseconds: widget.hungerLevel < 0.4 ? 800 : 2000),
    )..repeat(reverse: true);
  }

  @override
  void didUpdateWidget(CommunityLumaOrb oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.hungerLevel != widget.hungerLevel) {
      _controller.duration = Duration(milliseconds: widget.hungerLevel < 0.4 ? 800 : 2000);
      _controller.repeat(reverse: true);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    bool isUrgent = widget.hungerLevel < 0.4;
    Color orbColor = isUrgent ? const Color(0xFFE57373) : const Color(0xFF90D2A8); // Laranja/Vermelho vs Verde Luma

    return FadeTransition(
      opacity: Tween<double>(begin: 0.6, end: 1.0).animate(_controller),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [
            BoxShadow(
              color: orbColor.withOpacity(0.3),
              blurRadius: 10,
              spreadRadius: 2,
            )
          ],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.pets, color: orbColor, size: 18),
            const SizedBox(width: 8),
            SizedBox(
              width: 50,
              child: LinearProgressIndicator(
                value: widget.hungerLevel,
                backgroundColor: Colors.grey.shade200,
                color: orbColor,
                minHeight: 6,
                borderRadius: BorderRadius.circular(3),
              ),
            )
          ],
        ),
      ),
    );
  }
}
