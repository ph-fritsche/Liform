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

use Pitch\Liform\TransformResult;
use Symfony\Component\Form\FormView;

/**
 * @author Nacho Martín <nacho@limenius.com>
 * @author Philipp Fritsche <ph.fritsche@gmail.com>
 */
class StringTransformer implements TransformerInterface
{
    public function transform(
        FormView $view
    ): TransformResult {
        $result = new TransformResult();

        $result->schema->type = 'string';

        $result->schema->maxLength = $view->vars['attr']['maxlength'] ?? null;
        $result->schema->minLength = $view->vars['attr']['minlength'] ?? null;
        $result->schema->pattern = $view->vars['attr']['pattern'] ?? null;

        return $result;
    }
}
