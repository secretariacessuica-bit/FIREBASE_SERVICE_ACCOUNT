import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../app/theme/app_colors.dart';
import '../../../../shared/widgets/pin_keyboard.dart';
import '../providers/onboarding_wizard_provider.dart';
import '../widgets/onboarding_scaffold.dart';

class PinSetupPage extends ConsumerStatefulWidget {
  const PinSetupPage({super.key});

  @override
  ConsumerState<PinSetupPage> createState() => _PinSetupPageState();
}

class _PinSetupPageState extends ConsumerState<PinSetupPage> {
  String _pin = '';
  String _confirmedPin = '';
  bool _isConfirming = false;
  bool _hasError = false;

  void _onKeyPress(String key) {
    setState(() {
      _hasError = false;
      if (key == 'backspace') {
        if (_isConfirming && _confirmedPin.isNotEmpty) {
          _confirmedPin = _confirmedPin.substring(0, _confirmedPin.length - 1);
        } else if (!_isConfirming && _pin.isNotEmpty) {
          _pin = _pin.substring(0, _pin.length - 1);
        }
      } else {
        if (_isConfirming && _confirmedPin.length < 4) {
          _confirmedPin += key;
          if (_confirmedPin.length == 4) {
            _validateAndProceed();
          }
        } else if (!_isConfirming && _pin.length < 4) {
          _pin += key;
          if (_pin.length == 4) {
            setState(() {
              _isConfirming = true;
            });
          }
        }
      }
    });
  }

  void _validateAndProceed() {
    if (_pin == _confirmedPin) {
      ref.read(onboardingWizardProvider.notifier).setPin(_pin);
      ref.read(onboardingWizardProvider.notifier).nextStep();
    } else {
      setState(() {
        _hasError = true;
        _confirmedPin = ''; // reset confirmation
      });
    }
  }

  void _onBack() {
    if (_isConfirming) {
      setState(() {
        _isConfirming = false;
        _pin = '';
        _confirmedPin = '';
        _hasError = false;
      });
    } else {
      ref.read(onboardingWizardProvider.notifier).previousStep();
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentInput = _isConfirming ? _confirmedPin : _pin;
    
    return OnboardingScaffold(
      title: 'Proteja a Família',
      subtitle: _isConfirming ? 'Confirme o PIN para finalizar.' : 'Crie um PIN de 4 dígitos para acessar o Life.',
      progress: 0.60,
      onBack: _onBack,
      onNext: null, // we auto-proceed when pin is 4 digits
      child: Column(
        children: [
          const SizedBox(height: 32),
          // PIN Dots
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(4, (index) {
              return Container(
                margin: const EdgeInsets.symmetric(horizontal: 12),
                width: 24,
                height: 24,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: index < currentInput.length ? AppColors.primary : AppColors.surface,
                  border: Border.all(
                    color: _hasError ? Colors.red : (index < currentInput.length ? AppColors.primary : AppColors.textSecondary.withValues(alpha: 0.2)),
                    width: 2,
                  ),
                ),
              );
            }),
          ),
          const SizedBox(height: 16),
          if (_hasError)
            const Text(
              'Os PINs não conferem. Tente novamente.',
              style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold),
            )
          else
            const SizedBox(height: 16), // placeholder so layout doesn't jump
            
          const SizedBox(height: 48),
          PinKeyboard(
            onDigitPressed: _onKeyPress,
          ),
        ],
      ),
    );
  }
}
