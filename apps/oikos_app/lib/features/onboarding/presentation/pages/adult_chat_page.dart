import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

// Modelo da Mensagem
class ChatMessage {
  final String text;
  final bool isUser;
  ChatMessage({required this.text, required this.isUser});
}

// Provider de Estado do Chat
class OnboardingChatState {
  final List<ChatMessage> messages;
  final int step;
  final String? displayName;
  final String? primaryGoal;
  final String? familyCode;
  final bool isFinished;

  OnboardingChatState({
    required this.messages,
    required this.step,
    this.displayName,
    this.primaryGoal,
    this.familyCode,
    this.isFinished = false,
  });

  OnboardingChatState copyWith({
    List<ChatMessage>? messages,
    int? step,
    String? displayName,
    String? primaryGoal,
    String? familyCode,
    bool? isFinished,
  }) {
    return OnboardingChatState(
      messages: messages ?? this.messages,
      step: step ?? this.step,
      displayName: displayName ?? this.displayName,
      primaryGoal: primaryGoal ?? this.primaryGoal,
      familyCode: familyCode ?? this.familyCode,
      isFinished: isFinished ?? this.isFinished,
    );
  }
}

class OnboardingChatNotifier extends StateNotifier<OnboardingChatState> {
  OnboardingChatNotifier()
      : super(OnboardingChatState(
          messages: [
            ChatMessage(
              text: 'Olá. Bem-vindo ao Oikos. Como você prefere ser chamado(a)?',
              isUser: false,
            ),
          ],
          step: 0,
        ));

  void handleUserReply(String text) async {
    if (state.isFinished || text.trim().isEmpty) return;

    // Adiciona a mensagem do usuário
    state = state.copyWith(
      messages: [...state.messages, ChatMessage(text: text, isUser: true)],
    );

    // Simula tempo de digitação da IA
    await Future.delayed(const Duration(milliseconds: 600));

    if (state.step == 0) {
      // Capturou o nome
      state = state.copyWith(
        step: 1,
        displayName: text,
        messages: [
          ...state.messages,
          ChatMessage(
            text: 'Prazer em conhecer, $text. Qual é o principal objetivo da sua família ao usar o Oikos? (ex: organização financeira, rotina doméstica...)',
            isUser: false,
          )
        ],
      );
    } else if (state.step == 1) {
      // Capturou o objetivo
      state = state.copyWith(
        step: 2,
        primaryGoal: text,
        messages: [
          ...state.messages,
          ChatMessage(
            text: 'Entendido. Focaremos em "${text.toLowerCase()}".',
            isUser: false,
          )
        ],
      );

      await Future.delayed(const Duration(milliseconds: 800));

      // Finaliza e gera o código
      state = state.copyWith(
        step: 3,
        familyCode: 'OKS-782',
        isFinished: true,
        messages: [
          ...state.messages,
          ChatMessage(
            text: 'Seu perfil foi configurado. Você já pode convidar outros membros da família utilizando o código de convite OKS-782. Deseja prosseguir para o painel principal?',
            isUser: false,
          )
        ],
      );
    }
  }
}

final onboardingChatProvider = StateNotifierProvider<OnboardingChatNotifier, OnboardingChatState>((ref) {
  return OnboardingChatNotifier();
});

class AdultChatPage extends ConsumerStatefulWidget {
  const AdultChatPage({super.key});

  @override
  ConsumerState<AdultChatPage> createState() => _AdultChatPageState();
}

class _AdultChatPageState extends ConsumerState<AdultChatPage> {
  final TextEditingController _textController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  void _sendMessage() {
    final text = _textController.text;
    if (text.trim().isNotEmpty) {
      ref.read(onboardingChatProvider.notifier).handleUserReply(text);
      _textController.clear();
      _scrollToBottom();
    }
  }

  @override
  Widget build(BuildContext context) {
    final chatState = ref.watch(onboardingChatProvider);
    // Para manter o scroll embaixo quando novas mensagens chegam
    ref.listen(onboardingChatProvider, (previous, next) {
      if (previous?.messages.length != next.messages.length) {
        _scrollToBottom();
      }
    });

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text('Oikos Setup', style: TextStyle(color: Color(0xFF2C3E50), fontSize: 18)),
        backgroundColor: Colors.white,
        elevation: 1,
        centerTitle: true,
        iconTheme: const IconThemeData(color: Color(0xFF2C3E50)),
      ),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.all(16.0),
              itemCount: chatState.messages.length,
              itemBuilder: (context, index) {
                final msg = chatState.messages[index];
                return _buildChatBubble(msg);
              },
            ),
          ),
          if (chatState.isFinished)
            Padding(
              padding: const EdgeInsets.all(24.0),
              child: SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2C3E50),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  onPressed: () {
                    // TODO: Salvar json do profiling localmente/remotamente
                    Navigator.pushReplacementNamed(context, '/settings/family');
                  },
                  child: const Text('ACESSAR PAINEL', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ),
            )
          else
            _buildInputArea(),
        ],
      ),
    );
  }

  Widget _buildChatBubble(ChatMessage msg) {
    return Align(
      alignment: msg.isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 8.0),
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 12.0),
        decoration: BoxDecoration(
          color: msg.isUser ? const Color(0xFF2C3E50) : Colors.white,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: msg.isUser ? const Radius.circular(16) : const Radius.circular(0),
            bottomRight: msg.isUser ? const Radius.circular(0) : const Radius.circular(16),
          ),
          border: msg.isUser ? null : Border.all(color: Colors.grey.withValues(alpha: 0.2)),
        ),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.75,
        ),
        child: Text(
          msg.text,
          style: TextStyle(
            color: msg.isUser ? Colors.white : const Color(0xFF2C3E50),
            fontSize: 15,
            height: 1.4,
          ),
        ),
      ),
    );
  }

  Widget _buildInputArea() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 12.0),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Color(0xFFEEEEEE))),
      ),
      child: SafeArea(
        child: Row(
          children: [
            Expanded(
              child: TextField(
                controller: _textController,
                decoration: InputDecoration(
                  hintText: 'Sua resposta...',
                  hintStyle: const TextStyle(color: Colors.grey),
                  filled: true,
                  fillColor: const Color(0xFFF5F6F8),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: BorderSide.none,
                  ),
                ),
                onSubmitted: (_) => _sendMessage(),
              ),
            ),
            const SizedBox(width: 8),
            GestureDetector(
              onTap: _sendMessage,
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: const BoxDecoration(
                  color: Color(0xFF2C3E50),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.send, color: Colors.white, size: 20),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
