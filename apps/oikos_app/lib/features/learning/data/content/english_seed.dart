import '../../domain/entities/journey.dart';
import '../../domain/entities/chapter.dart';
import '../../domain/entities/lesson.dart';
import '../../domain/entities/exercise.dart';
import '../../domain/entities/question.dart';
import '../../domain/entities/answer.dart';

final englishSeed = Journey(
  id: 'journey_english_1',
  title: 'Inglês',
  description: 'Descubra novas palavras.',
  colorHex: '#2196F3',
  iconPath: 'assets/icons/english.png',
  chapters: [
    Chapter(
      id: 'chapter_english_1_1',
      title: 'Capítulo 1',
      description: 'Animais de Estimação',
      order: 1,
      lessons: [
        Lesson(
          id: 'lesson_english_1_1_1',
          title: 'Meu primeiro amigo',
          description: 'Aprenda sobre animais em inglês.',
          order: 1,
          content: const LessonContent(
            id: 'content_english_1_1_1',
            text: 'O cachorro é o melhor amigo do homem. Em inglês, nós chamamos ele de "Dog".',
          ),
          exercises: [
            Exercise(
              id: 'ex_english_1_1_1_1',
              question: const Question(
                id: 'q_english_1_1_1_1',
                text: 'Qual palavra significa cachorro em inglês?',
                type: QuestionType.multipleChoice,
                options: [
                  Answer(id: 'a1', text: 'Cat', isCorrect: false),
                  Answer(id: 'a2', text: 'Bird', isCorrect: false),
                  Answer(id: 'a3', text: 'Dog', isCorrect: true, explanation: 'Dog = Cachorro'),
                ],
              ),
            ),
          ],
        ),
      ],
    ),
  ],
);
