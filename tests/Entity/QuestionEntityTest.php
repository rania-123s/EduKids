<?php

namespace App\Tests\Entity;

use App\Entity\Question;
use App\Entity\Quiz;
use PHPUnit\Framework\TestCase;

final class QuestionEntityTest extends TestCase
{
    public function testBasicSettersAndQuizRelation(): void
    {
        $quiz = new Quiz();
        $question = new Question();

        $question
            ->setQuiz($quiz)
            ->setEnonce('Quelle est la capitale de la France ?')
            ->setBonneReponse('Paris')
            ->setChoix(4);

        self::assertSame($quiz, $question->getQuiz());
        self::assertSame('Quelle est la capitale de la France ?', $question->getEnonce());
        self::assertSame('Paris', $question->getBonneReponse());
        self::assertSame(4, $question->getChoix());
    }
}

