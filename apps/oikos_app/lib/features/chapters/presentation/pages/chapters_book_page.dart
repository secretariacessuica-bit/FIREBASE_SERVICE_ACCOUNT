import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../domain/entities/household_chapter.dart';
import '../domain/entities/chapter_value_objects.dart';

class ChaptersBookPage extends StatelessWidget {
  final List<HouseholdChapter> closedChapters;
  final HouseholdChapter? activeChapter;

  const ChaptersBookPage({
    super.key,
    required this.closedChapters,
    this.activeChapter,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('A Nossa História', style: TextStyle(fontFamily: 'Serif', letterSpacing: 1.2)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 20),
        children: [
          const Text(
            "ÍNDICE BIOGRÁFICO",
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 12,
              letterSpacing: 3,
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w300,
            ),
          ),
          const SizedBox(height: 50),
          ...closedChapters.asMap().entries.map((entry) {
            int index = entry.key + 1;
            HouseholdChapter chapter = entry.value;
            return _buildChapterIndexItem(context, index, chapter);
          }),
          if (activeChapter != null) ...[
            const SizedBox(height: 30),
            _buildActiveChapterIndexItem(context, activeChapter!),
          ],
        ],
      ),
    );
  }

  Widget _buildChapterIndexItem(BuildContext context, int chapterNumber, HouseholdChapter chapter) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 40),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Text(
            "CAPÍTULO ${_toRoman(chapterNumber)}",
            style: const TextStyle(fontSize: 14, color: AppColors.textSecondary, fontStyle: FontStyle.italic),
          ),
          const SizedBox(height: 8),
          Text(
            chapter.chapterTitle,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 22,
              fontFamily: 'Serif',
              color: AppColors.textPrimary,
              height: 1.3,
            ),
          ),
          const SizedBox(height: 12),
          const Divider(indent: 100, endIndent: 100, color: AppColors.border, thickness: 1),
          const SizedBox(height: 16),
          const Text(
            "O que desta Era continua vivendo em nossa família hoje?",
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, fontStyle: FontStyle.italic, color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }

  Widget _buildActiveChapterIndexItem(BuildContext context, HouseholdChapter chapter) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 40),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          const Text(
            "A ERA ATUAL",
            style: TextStyle(fontSize: 12, color: AppColors.accent, letterSpacing: 2),
          ),
          const SizedBox(height: 8),
          Text(
            chapter.chapterTitle,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 22,
              fontFamily: 'Serif',
              color: AppColors.textPrimary,
              height: 1.3,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            "Sendo escrito agora...",
            style: TextStyle(fontSize: 14, color: AppColors.textSecondary.withOpacity(0.5), fontStyle: FontStyle.italic),
          ),
        ],
      ),
    );
  }

  String _toRoman(int number) {
    const romanNumerals = ["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X"];
    if (number > 0 && number <= 10) return romanNumerals[number - 1];
    return number.toString();
  }
}
