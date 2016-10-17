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

        if (($code = $expression->getExpression()) instanceof JsExpression) {
            $code = $this->renderJsExpression($code);
        }

        if (($packages = $this->removePackages($expression)) !== []) {
            list($modules, $injects) = $this->extractRequireJsModules($packages);
            $code = $this->renderRequireJsCode($code, $modules, $injects);
        }

        list($modules, $injects) = $this->extractRequireJsModules($expression->getDependencies());

        return $this->renderRequireJsCode($code, $modules, $injects);
    }

    /**
     * Performs rendering of requirejs code block
     *
     * @param string $code
     * @param array $modules
     * @param array $injects
     * @return string
     */
    private function renderRequireJsCode($code, array $modules, array $injects)
    {
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
     * @return Module[] a list of dependencies which have multiple files
     */
    private function removePackages(JsExpression $expression)
    {
        $dependencies = $removedDependencies = [];

        foreach ($expression->getDependencies() as $dependency) {
            if (count($dependency->getFiles()) > 1) {
                $removedDependencies[] = $dependency;
                continue;
            }

            $dependencies[] = $dependency;
        }

        $expression->setDependencies($dependencies);

        return $removedDependencies;
    }

    /**
     * @param Module[] $dependencies
     *
     * @return array
     */
    private function extractRequireJsModules(array $dependencies)
    {
        $pad = 0;
        $injects = [];
        $modules = [];

        /** @var Module $dependency */
        foreach ($dependencies as $dependency) {
            $files = $dependency->getFiles();

            if ($files === []) {
                continue;
            }

            if (($filesCount = count($files)) > 1) {
                $pad += $filesCount;
                $files = array_keys($files);
                $baseUrlPattern = preg_quote($dependency->getBaseUrl());
                $baseUrlPattern = '#^' . $baseUrlPattern . '#';

                foreach ($files as $file) {
                    $modules[] = json_encode(preg_replace($baseUrlPattern, $dependency->getName(), $file));
                }

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
