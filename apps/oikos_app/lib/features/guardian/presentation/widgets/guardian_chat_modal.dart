import 'package:flutter/material.dart';
import '../../../../app/theme/adaptive_theme.dart';
import '../../domain/entities/interrogation_models.dart';
import '../../data/insight_engine.dart';

class GuardianChatModal extends StatefulWidget {
  final AdaptiveTheme theme;
  final InterrogationPrompt prompt;

  const GuardianChatModal({
    super.key,
    required this.theme,
    required this.prompt,
  });

  @override
  State<GuardianChatModal> createState() => _GuardianChatModalState();
}

class _GuardianChatModalState extends State<GuardianChatModal> {
  final TextEditingController _controller = TextEditingController();
  final InsightEngine _engine = InsightEngine();
  bool _isProcessing = false;
  GuardianInsight? _insightResult;

  void _submitAnswer() async {
    if (_controller.text.trim().isEmpty) return;
    
    setState(() => _isProcessing = true);
    
    // Motor extrai as tags
    final insight = await _engine.extractInsight(_controller.text, widget.prompt);
    
    setState(() {
      _insightResult = insight;
      _isProcessing = false;
    });

    // Simula a injeção do vetor no MemberIdentity (apenas visual aqui)
    Future.delayed(const Duration(seconds: 3), () {
      if (mounted) Navigator.pop(context); // Fecha o modal sozinho
    });
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
        left: 24,
        right: 24,
        top: 24,
      ),
      decoration: BoxDecoration(
        color: widget.theme.backgroundColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(32)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Icon(Icons.cruelty_free, color: widget.theme.primaryColor, size: 40),
              const SizedBox(width: 16),
              Expanded(
                child: Text(
                  widget.prompt.question,
                  style: widget.theme.headingStyle.copyWith(fontSize: 20, color: widget.theme.primaryColor),
                ),
              )
            ],
          ),
          const SizedBox(height: 24),
          
          if (_insightResult == null) ...[
            TextField(
              controller: _controller,
              decoration: InputDecoration(
                hintText: "Responda do seu jeito...",
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: widget.theme.buttonRadius,
                  borderSide: BorderSide.none,
                ),
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: widget.theme.primaryColor,
                shape: RoundedRectangleBorder(borderRadius: widget.theme.buttonRadius),
                padding: const EdgeInsets.symmetric(vertical: 16),
              ),
              onPressed: _isProcessing ? null : _submitAnswer,
              child: _isProcessing 
                ? const CircularProgressIndicator(color: Colors.white)
                : Text("Enviar", style: TextStyle(color: widget.theme.onPrimaryColor, fontSize: 16, fontWeight: FontWeight.bold)),
            )
          ] else ...[
            // Feedback do motor LLM
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: widget.theme.surfaceColor,
                borderRadius: widget.theme.buttonRadius,
              ),
              child: Column(
                children: [
                  Text("Que legal! Anotei isso.", style: widget.theme.bodyStyle),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    children: _insightResult!.extractedTags.map((tag) => Chip(
                      label: Text("#$tag"),
                      backgroundColor: Colors.white,
                      labelStyle: TextStyle(color: widget.theme.primaryColor, fontSize: 12),
                    )).toList(),
                  )
                ],
              ),
            )
          ]
        ],
      ),
    );
  }
}
