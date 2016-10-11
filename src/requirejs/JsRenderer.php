<?php
/**
 * @copyright Copyright (c) 2016 Roman Ishchenko
 * @license https://github.com/ischenko/yii2-jsloader-requirejs/blob/master/LICENSE
 * @link https://github.com/ischenko/yii2-jsloader-requirejs#readme
 */

namespace ischenko\yii2\jsloader\requirejs;

use ischenko\yii2\jsloader\helpers\JsExpression;

/**
 * Implementation of a JsRenderer for RequireJS
 *
 * @author Roman Ishchenko <roman@ishchenko.ck.ua>
 * @since 1.0
 */
class JsRenderer implements \ischenko\yii2\jsloader\JsRendererInterface
{
    /**
     * Performs rendering of js expression
     *
     * @param JsExpression $expression
     * @return string
     */
    public function renderJsExpression(JsExpression $expression)
    {
        if (!$expression->getExpression()
            && !$expression->getDependencies()
        ) {
            return '';
        }

        list($modules, $injects) = $this->extractDependencies($expression);

        if (($code = $expression->getExpression()) instanceof JsExpression) {
            $code = $this->renderJsExpression($code);
        }

        if ($modules === []) {
            return $code;
        }

        $requireBlock = 'require([' . implode(',', $modules) . ']';

        if (!empty($code)) {
            $requireBlock .= ', function(' . implode(',', $injects) . ") {\n{$code}\n}";
        }

        return $requireBlock . ');';
    }

    /**
     * @param JsExpression $expression
     *
     * @return array
     */
    private function extractDependencies(JsExpression $expression)
    {
        $pad = 0;
        $injects = [];
        $modules = [];

        /** @var Module $dependency */
        foreach ($expression->getDependencies() as $dependency) {
            if ($dependency->getFiles() === []) {
                continue;
            }

            if (($inject = $dependency->getExports()) !== null) {
                if ($pad > 0) {
                    for ($i = 0; $i < $pad; $i++) {
                        $injects[] = 'undefined';
                    }

                    $pad = -1;
                }

                $injects[] = $inject;
            }

            $pad++;
            $modules[] = json_encode($dependency->getName());
        }

        return [$modules, $injects];
    }
}
