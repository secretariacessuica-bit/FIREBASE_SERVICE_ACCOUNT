import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../domain/entities/memory_treasure.dart';
import '../widgets/memory_cover.dart';
import '../widgets/reflection_card.dart';

class MemoryViewer extends StatelessWidget {
  final MemoryTreasure memory;

  const MemoryViewer({
    super.key,
    required this.memory,
  });

  Future<void> _requestDelete(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Remover Tesouro?'),
        content: const Text('As memórias são partes importantes da história da família. Tem certeza de que deseja apagar essa lembrança para sempre?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar', style: TextStyle(color: AppColors.textPrimary)),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Remover', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      // Future: Call repository.deleteTreasure(memory.id, confirmed: true)
      Navigator.pop(context);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.close_rounded, color: AppColors.textPrimary),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.delete_outline_rounded, color: AppColors.textSecondary),
            onPressed: () => _requestDelete(context),
          ),
        ],
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32.0, vertical: 48.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              MemoryCover(
                emoji: memory.coverEmoji,
                theme: memory.theme,
                size: 200,
              ),
              const SizedBox(height: 64),
              Text(
                memory.title,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 32,
                  fontWeight: FontWeight.w900,
                  color: AppColors.textPrimary,
                  height: 1.2,
                ),
              ),
              const SizedBox(height: 32),
              Text(
                memory.narrative,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w400,
                  color: AppColors.textPrimary,
                  height: 1.6,
                ),
              ),
              const SizedBox(height: 64),
              ReflectionCard(reflection: memory.reflection),
              const SizedBox(height: 48),
            ],
          ),
        ),
      ),
    );
  }
}
