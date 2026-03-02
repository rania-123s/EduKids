<?php

namespace App\Tests\Entity\Quiz;

use App\Entity\Quiz\Question;
use App\Entity\Quiz\Quiz;
use PHPUnit\Framework\TestCase;

final class QuizTest extends TestCase
{
    public function testConstructorDefaultsAndQuestionRelation(): void
    {
        $quiz = new Quiz();

        self::assertInstanceOf(\DateTimeImmutable::class, $quiz->getCreatedAt());
        self::assertFalse($quiz->isPublished());
        self::assertFalse($quiz->isChatbotEnabled());
        self::assertCount(0, $quiz->getQuestions());
        self::assertCount(0, $quiz->getAttempts());

        $question = new Question();
        $quiz->addQuestion($question);

        self::assertCount(1, $quiz->getQuestions());
        self::assertSame($quiz, $question->getQuiz());

        $quiz->removeQuestion($question);
        self::assertCount(0, $quiz->getQuestions());
        self::assertNull($question->getQuiz());
    }
}

