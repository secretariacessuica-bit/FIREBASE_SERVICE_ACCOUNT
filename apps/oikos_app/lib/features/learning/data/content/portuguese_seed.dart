import '../../domain/entities/journey.dart';
import '../../domain/entities/chapter.dart';
import '../../domain/entities/lesson.dart';
import '../../domain/entities/exercise.dart';
import '../../domain/entities/question.dart';
import '../../domain/entities/answer.dart';

final portugueseSeed = Journey(
  id: 'journey_portuguese_1',
  title: 'Português',
  description: 'Explore as palavras e a gramática.',
  colorHex: '#FFC107',
  iconPath: 'assets/icons/portuguese.png',
  chapters: [
    Chapter(
      id: 'chapter_portuguese_1_1',
      title: 'Capítulo 1',
      description: 'As Sílabas',
      order: 1,
      lessons: [
        Lesson(
          id: 'lesson_portuguese_1_1_1',
          title: 'Separando palavras',
          description: 'Descubra como formar os sons.',
          order: 1,
          content: const LessonContent(
            id: 'content_portuguese_1_1_1',
            text: 'A palavra BOLA é formada pelas sílabas BO e LA. É muito fácil!',
          ),
          exercises: [
            Exercise(
              id: 'ex_portuguese_1_1_1_1',
              question: const Question(
                id: 'q_portuguese_1_1_1_1',
                text: 'Qual das palavras abaixo está escrita corretamente com a sílaba BO?',
                type: QuestionType.multipleChoice,
                options: [
                  Answer(id: 'a1', text: 'Pola', isCorrect: false),
                  Answer(id: 'a2', text: 'Bola', isCorrect: true, explanation: 'BO + LA = BOLA'),
                  Answer(id: 'a3', text: 'Mola', isCorrect: false),
                ],
              ),
            ),
          ],
        ),
      ],
    ),
  ],
);
