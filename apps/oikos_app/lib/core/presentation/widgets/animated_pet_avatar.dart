import 'package:flutter/material.dart';

class AnimatedPetAvatar extends StatefulWidget {
  final double size;
  final IconData icon;
  final Color color;
  final bool isHungry;

  const AnimatedPetAvatar({
    super.key,
    this.size = 80,
    required this.icon,
    required this.color,
    this.isHungry = false,
  });

  @override
  State<AnimatedPetAvatar> createState() => _AnimatedPetAvatarState();
}

class _AnimatedPetAvatarState extends State<AnimatedPetAvatar> with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 2), // Respiração lenta e calma
    )..repeat(reverse: true);

    _scaleAnimation = Tween<double>(begin: 0.95, end: 1.05).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _scaleAnimation,
      builder: (context, child) {
        return Transform.scale(
          scale: _scaleAnimation.value,
          child: Container(
            width: widget.size,
            height: widget.size,
            decoration: BoxDecoration(
              color: widget.isHungry ? Colors.orange.withOpacity(0.2) : widget.color.withOpacity(0.2),
              shape: BoxShape.circle,
              border: Border.all(
                color: widget.isHungry ? Colors.orange : widget.color,
                width: widget.isHungry ? 3 : 2,
              ),
              boxShadow: [
                BoxShadow(
                  color: widget.color.withOpacity(0.1),
                  blurRadius: 15,
                  spreadRadius: 5,
                )
              ],
            ),
            child: Icon(
              widget.icon,
              size: widget.size * 0.6,
              color: widget.isHungry ? Colors.orange : widget.color,
            ),
          ),
        );
      },
    );
  }
}
