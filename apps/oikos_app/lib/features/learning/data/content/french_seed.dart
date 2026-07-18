import '../../domain/entities/journey.dart';
import '../../domain/entities/chapter.dart';
import '../../domain/entities/lesson.dart';
import '../../domain/entities/exercise.dart';
import '../../domain/entities/question.dart';
import '../../domain/entities/answer.dart';

final frenchSeed = Journey(
  id: 'journey_french_1',
  title: 'Français',
  description: 'Descubra novas palavras em francês.',
  colorHex: '#E07A5F', // Cor quente de argila, combinando com Oikos
  iconPath: 'assets/icons/french.png', // Mesmo que não exista, o UI tratará ou falhará graciosamente
  chapters: [
    Chapter(
      id: 'chapter_french_1_1',
      title: 'Capítulo 1',
      description: 'Saudações',
      order: 1,
      lessons: [
        Lesson(
          id: 'lesson_french_1_1_1',
          title: 'Primeiros Laços',
          description: 'Aprenda a dizer olá em francês.',
          order: 1,
          content: const LessonContent(
            id: 'content_french_1_1_1',
            text: 'Para dizer "Olá" ou "Bom dia" em francês, dizemos "Bonjour".',
          ),
          exercises: [
            Exercise(
              id: 'ex_french_1_1_1_1',
              question: const Question(
                id: 'q_french_1_1_1_1',
                text: 'Como se diz "Olá" em francês?',
                type: QuestionType.multipleChoice,
                options: [
                  Answer(id: 'a1_fr', text: 'Merci', isCorrect: false),
                  Answer(id: 'a2_fr', text: 'Bonjour', isCorrect: true, explanation: 'Bonjour = Bom dia / Olá'),
                  Answer(id: 'a3_fr', text: 'Au revoir', isCorrect: false),
                ],
              ),
            ),
          ],
        ),
      ],
    ),
  ],
);
