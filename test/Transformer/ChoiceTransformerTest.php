<?php

/*
 * Original file is part of the Limenius\Liform package.
 *
 * (c) Limenius <https://github.com/Limenius/>
 *
 * This file is part of the Pitch\Liform package.
 *
 * (c) Philipp Fritsche <ph.fritsche@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pitch\Liform\Transformer;

use Pitch\Liform\TransformationTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Nacho Martín <nacho@limenius.com>
 * @author Philipp Fritsche <ph.fritsche@gmail.com>
 */
class ChoiceTransformerTest extends TransformationTestCase
{
    public function testChoice()
    {
        $view = $this->createFormView(ChoiceType::class, [
            'choices' => ['a' => 'A', 'b' => 'B'],
        ]);
        
        $transformer = new ChoiceTransformer();
        $result = $transformer->transform($view);

        $this->assertEquals('string', $result->schema->type);
        $this->assertEquals(['A','B'], $result->schema->enum);
        $this->assertEquals(['a','b'], $result->schema->enumTitles);
    }

    public function testTranslateChoiceLabels()
    {
        $view = $this->createFormView(ChoiceType::class, [
            'choices' => ['a' => 'A', 'b' => 'B'],
        ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->exactly(2))->method('trans')
            ->withConsecutive(['a'], ['b'])
            ->willReturn('foo', 'bar');
        /** @var TranslatorInterface $translator */

        $transformer = new ChoiceTransformer($translator);
        $result = $transformer->transform($view);

        $this->assertEquals(['foo', 'bar'], $result->schema->enumTitles);
    }

    public function testMultiple()
    {
        $view = $this->createFormView(ChoiceType::class, [
            'choices' => ['a' => 'A', 'b' => 'B'],
            'multiple' => true,
        ]);

        $transformer = new ChoiceTransformer();
        $result = $transformer->transform($view);

        $this->assertEquals('array', $result->schema->type);

        $this->assertEquals('string', $result->schema->items->type);
        $this->assertEquals(['A','B'], $result->schema->items->enum);
        $this->assertEquals(['a','b'], $result->schema->items->enumTitles);

        $this->assertEquals(1, $result->schema->minItems);
    }

    public function testExpanded()
    {
        $view = $this->createFormView(ChoiceType::class, [
            'expanded' => true,
        ]);

        $transformer = new ChoiceTransformer();
        $result = $transformer->transform($view);

        $this->assertTrue($result->schema->choiceExpanded);
    }
}
