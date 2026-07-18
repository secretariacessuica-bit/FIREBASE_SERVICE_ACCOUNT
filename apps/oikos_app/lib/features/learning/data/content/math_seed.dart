import '../../domain/entities/journey.dart';
import '../../domain/entities/chapter.dart';
import '../../domain/entities/lesson.dart';
import '../../domain/entities/exercise.dart';
import '../../domain/entities/question.dart';
import '../../domain/entities/answer.dart';

final mathSeed = Journey(
  id: 'journey_math_1',
  title: 'Matemática',
  description: 'Aprenda os números e operações básicas.',
  colorHex: '#4CAF50',
  iconPath: 'assets/icons/math.png',
  chapters: [
    Chapter(
      id: 'chapter_math_1_1',
      title: 'Capítulo 1',
      description: 'Adição Simples',
      order: 1,
      lessons: [
        Lesson(
          id: 'lesson_math_1_1_1',
          title: 'A magia de somar',
          description: 'Vamos aprender a juntar coisas.',
          order: 1,
          content: const LessonContent(
            id: 'content_math_1_1_1',
            text: 'A adição é quando juntamos quantidades. Se você tem coisas e ganha mais, você soma!',
          ),
          exercises: [
            Exercise(
              id: 'ex_math_1_1_1_1',
              question: const Question(
                id: 'q_math_1_1_1_1',
                text: 'Maria tem 2 maçãs. João deu mais 2 maçãs para ela. Com quantas maçãs Maria ficou?',
                type: QuestionType.multipleChoice,
                options: [
                  Answer(id: 'a1', text: '3', isCorrect: false),
                  Answer(id: 'a2', text: '4', isCorrect: true, explanation: '2 + 2 = 4'),
                  Answer(id: 'a3', text: '5', isCorrect: false),
                ],
              ),
            ),
          ],
        ),
      ],
    ),
  ],
);
