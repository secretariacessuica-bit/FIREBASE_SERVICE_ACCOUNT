import 'package:flutter/material.dart';

class FloatingPetSpeech extends StatefulWidget {
  final String text;
  final VoidCallback onTap;

  const FloatingPetSpeech({
    super.key,
    required this.text,
    required this.onTap,
  });

  @override
  State<FloatingPetSpeech> createState() => _FloatingPetSpeechState();
}

class _FloatingPetSpeechState extends State<FloatingPetSpeech> with SingleTickerProviderStateMixin {
  late AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat(reverse: true); // Faz o balão flutuar suavemente
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SlideTransition(
      position: Tween<Offset>(
        begin: const Offset(0, -0.05),
        end: const Offset(0, 0.05),
      ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeInOut)),
      child: GestureDetector(
        onTap: widget.onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20).copyWith(bottomLeft: Radius.zero),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.1),
                blurRadius: 10,
                offset: const Offset(0, 5),
              )
            ],
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Flexible(
                child: Text(
                  widget.text,
                  style: const TextStyle(fontSize: 14, color: Color(0xFF4A3E3D), fontWeight: FontWeight.w600),
                ),
              ),
              const SizedBox(width: 8),
              const Icon(Icons.touch_app, size: 16, color: Colors.blueAccent),
            ],
          ),
        ),
      ),
    );
  }
}
