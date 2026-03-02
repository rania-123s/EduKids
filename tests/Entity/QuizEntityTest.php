<?php

namespace App\Tests\Entity;

use App\Entity\Cours;
use App\Entity\Question;
use App\Entity\Quiz;
use PHPUnit\Framework\TestCase;

final class QuizEntityTest extends TestCase
{
    public function testBasicFieldsAndQuestionRelation(): void
    {
        $cours = new Cours();
        $quiz = new Quiz();
        $question = new Question();

        $quiz
            ->setTitre('Quiz de culture generale')
            ->setCours($cours)
            ->setScoreMax('20')
            ->addQuestion($question);

        self::assertSame('Quiz de culture generale', $quiz->getTitre());
        self::assertSame($cours, $quiz->getCours());
        self::assertSame('20', $quiz->getScoreMax());
        self::assertCount(1, $quiz->getQuestions());
        self::assertSame($quiz, $question->getQuiz());

        $quiz->removeQuestion($question);
        self::assertCount(0, $quiz->getQuestions());
        self::assertNull($question->getQuiz());
    }
}

