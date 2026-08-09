import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../domain/lumo_variant.dart';
import 'lumo_blink_overlay.dart';

/// Renderiza o mascote Lumo com animações internas contínuas:
///
/// - **Respiração** — escala vertical oscila suavemente em loop
/// - **Piscar de olhos** — via [LumoBlinkOverlay] com timer aleatório
/// - **Bounce suave** — flutuação vertical em idle
/// - **Animações contextuais** — comportamento diferente por [LumoVariant]:
///   - `celebrating` → bounce rápido + glow pulsante
///   - `sleeping`    → respiração lenta + opacidade reduzida
///   - `excited`     → bounce energético
///   - `happy`       → bounce médio + glow pulsante
class LumoRenderer extends StatefulWidget {
  final LumoVariant variant;
  final double size;
  final bool loop;

  const LumoRenderer({
    super.key,
    this.variant = LumoVariant.idle,
    this.size = 150,
    this.loop = true,
  });

  @override
  State<LumoRenderer> createState() => _LumoRendererState();
}

class _LumoRendererState extends State<LumoRenderer>
    with TickerProviderStateMixin {
  // ── Controlador de respiração ─────────────────────────────────────────────
  late final AnimationController _breathController;
  late final Animation<double> _breathAnimation;

  // ── Controlador de glow pulsante ──────────────────────────────────────────
  late final AnimationController _glowController;
  late final Animation<double> _glowAnimation;

  @override
  void initState() {
    super.initState();
    _setupBreathAnimation();
    _setupGlowAnimation();
    _applyVariantAnimations();
  }

  @override
  void didUpdateWidget(LumoRenderer oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.variant != widget.variant) {
      _applyVariantAnimations();
    }
  }

  // ── Setup: Respiração ─────────────────────────────────────────────────────
  void _setupBreathAnimation() {
    _breathController = AnimationController(
      vsync: this,
      duration: _breathDuration,
    )..repeat(reverse: true);

    _breathAnimation = Tween<double>(begin: 1.0, end: 1.03).animate(
      CurvedAnimation(parent: _breathController, curve: Curves.easeInOut),
    );
  }

  // ── Setup: Glow pulsante ──────────────────────────────────────────────────
  void _setupGlowAnimation() {
    _glowController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );

    _glowAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _glowController, curve: Curves.easeInOut),
    );
  }

  // ── Duração de respiração por variante ───────────────────────────────────
  Duration get _breathDuration {
    switch (widget.variant) {
      case LumoVariant.sleeping:
        return const Duration(milliseconds: 4200); // respiração lenta
      case LumoVariant.excited:
      case LumoVariant.celebrating:
        return const Duration(milliseconds: 1400); // agitado
      default:
        return const Duration(milliseconds: 2500); // normal
    }
  }

  // ── Escala máxima de respiração por variante ─────────────────────────────
  double get _breathScale {
    switch (widget.variant) {
      case LumoVariant.sleeping:
        return 1.04; // mais profundo
      case LumoVariant.celebrating:
      case LumoVariant.excited:
        return 1.02; // mais contido (está em movimento)
      default:
        return 1.03;
    }
  }

  // ── Aplicar animações ao mudar de variante ────────────────────────────────
  void _applyVariantAnimations() {
    // Reiniciar respiração com nova duração
    _breathController
      ..stop()
      ..duration = _breathDuration
      ..repeat(reverse: true);

    // Glow pulsante para variantes energéticas
    final bool shouldPulseGlow = widget.variant == LumoVariant.celebrating ||
        widget.variant == LumoVariant.happy ||
        widget.variant == LumoVariant.excited;

    if (shouldPulseGlow) {
      _glowController.repeat(reverse: true);
    } else {
      _glowController.stop();
      _glowController.reset();
    }
  }

  @override
  void dispose() {
    _breathController.dispose();
    _glowController.dispose();
    super.dispose();
  }

  // ── Parâmetros de bounce por variante ────────────────────────────────────
  ({Duration duration, double amplitude}) get _bounceParams {
    switch (widget.variant) {
      case LumoVariant.celebrating:
        return (duration: 380.ms, amplitude: 0.07);
      case LumoVariant.excited:
        return (duration: 500.ms, amplitude: 0.055);
      case LumoVariant.happy:
        return (duration: 700.ms, amplitude: 0.04);
      case LumoVariant.sleeping:
        return (duration: 0.ms, amplitude: 0.0); // sem bounce
      default:
        return (duration: 900.ms, amplitude: 0.025); // idle suave
    }
  }

  // ── Opacidade do corpo para variante sleeping ────────────────────────────
  double get _bodyOpacity {
    return widget.variant == LumoVariant.sleeping ? 0.82 : 1.0;
  }

  @override
  Widget build(BuildContext context) {
    final props = widget.variant.properties;
    final bounce = _bounceParams;

    return SizedBox(
      width: widget.size,
      height: widget.size * 1.2,
      child: Stack(
        alignment: Alignment.center,
        children: [
          // ── Sombra ────────────────────────────────────────────────────────
          Positioned(
            bottom: 0,
            child: SvgPicture.asset(
              'assets/images/lumo/shadow/shadow.svg',
              width: widget.size * 0.8,
            ),
          ),

          // ── Corpo + Face + Braços (com respiração + bounce) ──────────────
          AnimatedPositioned(
            duration: const Duration(milliseconds: 350),
            curve: Curves.easeOutBack,
            bottom: 15 - props.yOffset,
            child: AnimatedRotation(
              turns: props.bodyRotation / 360.0,
              duration: const Duration(milliseconds: 350),
              child: AnimatedOpacity(
                opacity: _bodyOpacity,
                duration: const Duration(milliseconds: 600),
                child: AnimatedBuilder(
                  animation: _breathAnimation,
                  builder: (context, child) {
                    return Transform.scale(
                      scaleY: _breathScale == 1.03
                          ? _breathAnimation.value
                          : Tween<double>(begin: 1.0, end: _breathScale)
                              .animate(CurvedAnimation(
                                  parent: _breathController,
                                  curve: Curves.easeInOut))
                              .value,
                      child: child,
                    );
                  },
                  child: SizedBox(
                    width: widget.size,
                    height: widget.size,
                    child: Stack(
                      alignment: Alignment.center,
                      children: [
                        // Glow
                        _buildGlow(props.glowOpacity),

                        // Braço esquerdo
                        Positioned(
                          left: widget.size * 0.15,
                          top: widget.size * 0.55,
                          child: AnimatedRotation(
                            turns: props.leftArmRotation / 360.0,
                            duration: const Duration(milliseconds: 350),
                            child: SvgPicture.asset(
                              'assets/images/lumo/arms/arm_left.svg',
                              width: widget.size * 0.35,
                            ),
                          ),
                        ),

                        // Braço direito
                        Positioned(
                          right: widget.size * 0.15,
                          top: widget.size * 0.55,
                          child: AnimatedRotation(
                            turns: props.rightArmRotation / 360.0,
                            duration: const Duration(milliseconds: 350),
                            child: SvgPicture.asset(
                              'assets/images/lumo/arms/arm_right.svg',
                              width: widget.size * 0.35,
                            ),
                          ),
                        ),

                        // Corpo
                        SvgPicture.asset(
                          'assets/images/lumo/body/body.svg',
                          width: widget.size,
                          height: widget.size,
                        ),

                        // Face (olhos com piscar + boca)
                        Positioned(
                          top: widget.size * 0.40,
                          child: SizedBox(
                            width: widget.size * 0.6,
                            height: widget.size * 0.4,
                            child: Stack(
                              alignment: Alignment.center,
                              children: [
                                // Olhos com piscar automático
                                Positioned(
                                  top: 0,
                                  child: LumoBlinkOverlay(
                                    eyeAsset: props.eyeAsset,
                                    size: widget.size * 0.6,
                                  ),
                                ),
                                // Boca
                                Positioned(
                                  top: widget.size * 0.15,
                                  child: SvgPicture.asset(
                                    props.mouthAsset,
                                    width: widget.size * 0.6,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    )
        // Bounce suave em loop — variante controla amplitude e duração
        .animate(
          onPlay: (controller) => (widget.loop && bounce.duration.inMilliseconds > 0)
              ? controller.repeat(reverse: true)
              : (bounce.duration.inMilliseconds > 0 ? controller.forward() : null),
        )
        .slideY(
          begin: 0.0,
          end: -bounce.amplitude,
          duration: bounce.duration,
          curve: Curves.easeInOut,
        );
  }

  // ── Glow com pulsação para variantes energéticas ─────────────────────────
  Widget _buildGlow(double baseOpacity) {
    final bool pulsing = widget.variant == LumoVariant.celebrating ||
        widget.variant == LumoVariant.happy ||
        widget.variant == LumoVariant.excited;

    if (pulsing) {
      return AnimatedBuilder(
        animation: _glowAnimation,
        builder: (context, child) {
          final double pulseOpacity =
              baseOpacity + (_glowAnimation.value * 0.25);
          return Opacity(
            opacity: pulseOpacity.clamp(0.0, 1.0),
            child: child,
          );
        },
        child: SvgPicture.asset(
          'assets/images/lumo/glow/glow.svg',
          width: widget.size,
          height: widget.size,
        ),
      );
    }

    return AnimatedOpacity(
      duration: const Duration(milliseconds: 350),
      opacity: baseOpacity,
      child: SvgPicture.asset(
        'assets/images/lumo/glow/glow.svg',
        width: widget.size,
        height: widget.size,
      ),
    );
  }
}
