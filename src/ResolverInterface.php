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

namespace Pitch\Liform;

use Pitch\Liform\Transformer\TransformerInterface;
use Symfony\Component\Form\FormView;

/**
 * @author Nacho Martín <nacho@limenius.com>
 * @author Philipp Fritsche <ph.fritsche@gmail.com>
 */
interface ResolverInterface
{
    /**
     * Determine the Transformer that will handle the given FormView.
     */
    public function resolve(
        FormView $view
    ): TransformerInterface;
}
