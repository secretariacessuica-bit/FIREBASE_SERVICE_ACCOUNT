import '../../domain/entities/journey.dart';
import '../../domain/entities/chapter.dart';
import '../../domain/entities/lesson.dart';
import '../../domain/entities/exercise.dart';
import '../../domain/entities/question.dart';
import '../../domain/entities/answer.dart';
import '../../../missions/domain/entities/mission.dart';

final frenchSeed = Journey(
  id: 'journey_french_1',
  title: 'Français',
  description: 'Aprenda expressões e números do quotidiano na Suíça.',
  colorHex: '#E07A5F',
  iconPath: 'assets/icons/french.png',
  chapters: [
    Chapter(
      id: 'chapter_french_1_1',
      title: 'Capítulo 1',
      description: 'Cotidiano em Lausanne',
      order: 1,
      lessons: [
        Lesson(
          id: 'lesson_french_1_1_1',
          title: 'Entender o preço no caixa',
          description: 'Aprenda a compreender preços em francês suíço.',
          order: 1,
          content: const LessonContent(
            id: 'content_french_1_1_1',
            text: 'Na Suíça Romanda (Lausanne, Genebra, Vaud, etc.), alguns números são diferentes do francês da França. Por exemplo, 90 diz-se "nonante".',
          ),
          exercises: [
            Exercise(
              id: 'ex_french_1_1_1_1',
              mission: const Mission(
                id: 'ch_numbers_nonante_001',
                title: 'Entender o preço no caixa',
                description: 'Entender o preço no caixa em francês suíço',
                contextDescription: 'Você está numa loja em Lausanne. A atendente informa o total da compra em francês suíço.',
                promptPhrase: 'Ça fait nonante-cinq francs.',
                options: [
                  MissionOption(id: 'opt_75', label: '75 francos', isCorrect: false),
                  MissionOption(id: 'opt_85', label: '85 francos', isCorrect: false),
                  MissionOption(id: 'opt_95', label: '95 francos', isCorrect: true),
                ],
                helpExplanation: 'Na Suíça Romanda, "nonante" significa noventa. "Nonante-cinq" corresponde a 95.',
                practicedCompetency: 'Compreender preços com números usados na Suíça Romanda.',
              ),
              question: const Question(
                id: 'q_french_1_1_1_1',
                text: 'Quanto você precisa pagar para a frase: "Ça fait nonante-cinq francs"?',
                type: QuestionType.multipleChoice,
                options: [
                  Answer(id: 'opt_75', text: '75 francos', isCorrect: false),
                  Answer(id: 'opt_85', text: '85 francos', isCorrect: false),
                  Answer(id: 'opt_95', text: '95 francos', isCorrect: true, explanation: 'Na Suíça Romanda, "nonante" significa noventa. "Nonante-cinq" corresponde a 95.'),
                ],
              ),
            ),
          ],
        ),
      ],
    ),
  ],
);
