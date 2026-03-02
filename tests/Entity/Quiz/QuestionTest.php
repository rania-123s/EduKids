<?php

namespace App\Tests\Entity\Quiz;

use App\Entity\Quiz\Question;
use App\Entity\Quiz\QuestionOption;
use PHPUnit\Framework\TestCase;

final class QuestionTest extends TestCase
{
    public function testOptionsAndCorrectAnswerHelpers(): void
    {
        $question = (new Question())
            ->setTexte('Capitale de la France ?')
            ->setType(Question::TYPE_QCM);

        $opt1 = (new QuestionOption())->setTexte('Lyon')->setOrdre(0)->setCorrect(false);
        $opt2 = (new QuestionOption())->setTexte('Paris')->setOrdre(1)->setCorrect(true);
        $opt3 = (new QuestionOption())->setTexte('Marseille')->setOrdre(2)->setCorrect(false);

        $question->addQuestionOption($opt1)->addQuestionOption($opt2)->addQuestionOption($opt3);

        self::assertCount(3, $question->getQuestionOptions());
        self::assertSame(['Lyon', 'Paris', 'Marseille'], $question->getOptions());
        self::assertSame(1, $question->getCorrectOptionIndex());
        self::assertSame($opt2, $question->getCorrectOption());
        self::assertSame($question, $opt2->getQuestion());

        $question->removeQuestionOption($opt2);
        self::assertCount(2, $question->getQuestionOptions());
        self::assertNull($opt2->getQuestion());
        self::assertNull($question->getCorrectOptionIndex());
        self::assertNull($question->getCorrectOption());
    }
}

