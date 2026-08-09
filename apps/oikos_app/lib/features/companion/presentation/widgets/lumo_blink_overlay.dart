import 'dart:async';
import 'dart:math';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

/// Widget isolado que renderiza a camada de olhos do Lumo com piscar automático.
///
/// O piscar é disparado por um [Timer.periodic] com intervalo aleatório
/// entre [_minBlinkInterval] e [_maxBlinkInterval], imitando o comportamento
/// natural do olhar humano. A animação de fechar/abrir o olho dura [_blinkDuration].
class LumoBlinkOverlay extends StatefulWidget {
  final String eyeAsset;
  final double size;

  const LumoBlinkOverlay({
    super.key,
    required this.eyeAsset,
    required this.size,
  });

  @override
  State<LumoBlinkOverlay> createState() => _LumoBlinkOverlayState();
}

class _LumoBlinkOverlayState extends State<LumoBlinkOverlay>
    with SingleTickerProviderStateMixin {
  late final AnimationController _blinkController;
  late final Animation<double> _blinkAnimation;
  Timer? _blinkTimer;
  final Random _random = Random();

  // ── Constantes de timing ─────────────────────────────────────────────────
  static const Duration _blinkDuration = Duration(milliseconds: 110);
  static const Duration _minBlinkInterval = Duration(seconds: 3);
  static const Duration _maxBlinkInterval = Duration(seconds: 6);

  @override
  void initState() {
    super.initState();
    _blinkController = AnimationController(
      vsync: this,
      duration: _blinkDuration,
    );

    // scaleY: 1.0 (aberto) → 0.05 (fechado) → 1.0 (aberto)
    _blinkAnimation = TweenSequence<double>([
      TweenSequenceItem(
        tween: Tween(begin: 1.0, end: 0.05).chain(CurveTween(curve: Curves.easeIn)),
        weight: 40,
      ),
      TweenSequenceItem(
        tween: Tween(begin: 0.05, end: 1.0).chain(CurveTween(curve: Curves.easeOut)),
        weight: 60,
      ),
    ]).animate(_blinkController);

    _scheduleNextBlink();
  }

  void _scheduleNextBlink() {
    final int jitterMs = _random.nextInt(
      _maxBlinkInterval.inMilliseconds - _minBlinkInterval.inMilliseconds,
    );
    final Duration interval = _minBlinkInterval + Duration(milliseconds: jitterMs);

    _blinkTimer = Timer(interval, () {
      if (mounted) {
        _blinkController.forward(from: 0.0).then((_) {
          if (mounted) _scheduleNextBlink();
        });
      }
    });
  }

  @override
  void dispose() {
    _blinkTimer?.cancel();
    _blinkController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _blinkAnimation,
      builder: (context, child) {
        return Transform.scale(
          scaleY: _blinkAnimation.value,
          child: child,
        );
      },
      child: SvgPicture.asset(
        widget.eyeAsset,
        width: widget.size,
      ),
    );
  }
}
