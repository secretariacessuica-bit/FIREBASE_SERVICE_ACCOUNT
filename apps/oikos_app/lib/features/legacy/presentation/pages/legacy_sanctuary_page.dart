import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../domain/entities/enduring_value.dart';
import '../domain/entities/legacy_value_objects.dart';

class LegacySanctuaryPage extends StatelessWidget {
  final List<EnduringValue> establishedValues;

  const LegacySanctuaryPage({
    super.key,
    required this.establishedValues,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('O Nosso Legado', style: TextStyle(fontFamily: 'Serif', letterSpacing: 2.0)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body: establishedValues.isEmpty 
        ? _buildEmptyState()
        : _buildPillarsGrid(context),
    );
  }

  Widget _buildEmptyState() {
    return const Center(
      child: Padding(
        padding: EdgeInsets.all(40.0),
        child: Text(
          "O legado está sendo construído com o tempo.\nAinda não há pilares fundamentados na casa.",
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 16, color: AppColors.textSecondary, fontStyle: FontStyle.italic),
        ),
      ),
    );
  }

  Widget _buildPillarsGrid(BuildContext context) {
    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 40),
      itemCount: establishedValues.length,
      itemBuilder: (context, index) {
        final value = establishedValues[index];
        return _buildPillarItem(context, value);
      },
    );
  }

  Widget _buildPillarItem(BuildContext context, EnduringValue value) {
    return Container(
      margin: const EdgeInsets.only(bottom: 40),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Container(
            width: 4,
            height: 40,
            color: AppColors.accent.withOpacity(0.5),
          ),
          const SizedBox(height: 16),
          Text(
            _getArchetypeTitle(value.archetype).toUpperCase(),
            style: const TextStyle(fontSize: 10, color: AppColors.textSecondary, letterSpacing: 4),
          ),
          const SizedBox(height: 8),
          Text(
            value.familyName,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 26,
              fontFamily: 'Serif',
              color: AppColors.textPrimary,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            value.reflection,
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 14, color: AppColors.textSecondary, fontStyle: FontStyle.italic, height: 1.6),
          ),
          const SizedBox(height: 24),
          _buildEvidencesSection(value.evidences),
          const SizedBox(height: 16),
          Container(
            width: 4,
            height: 40,
            color: AppColors.accent.withOpacity(0.5),
          ),
        ],
      ),
    );
  }

  Widget _buildEvidencesSection(List<LegacyEvidence> evidences) {
    return Column(
      children: evidences.map((evidence) {
        return Padding(
          padding: const EdgeInsets.only(bottom: 8.0),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.history_edu, size: 14, color: AppColors.accent),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  evidence.contribution,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                ),
              ),
            ],
          ),
        );
      }).toList(),
    );
  }

  String _getArchetypeTitle(LegacyArchetype archetype) {
    switch (archetype) {
      case LegacyArchetype.courage: return "Coragem";
      case LegacyArchetype.care: return "Cuidado";
      case LegacyArchetype.curiosity: return "Curiosidade";
      case LegacyArchetype.generosity: return "Generosidade";
      case LegacyArchetype.hope: return "Esperança";
      case LegacyArchetype.tradition: return "Tradição";
      case LegacyArchetype.belonging: return "Pertencimento";
      case LegacyArchetype.resilience: return "Resiliência";
      default: return "Fundamento";
    }
  }
}
