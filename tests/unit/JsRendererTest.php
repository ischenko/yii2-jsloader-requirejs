<?php

namespace ischenko\yii2\jsloader\tests\unit\requirejs;

use ischenko\yii2\jsloader\helpers\JsExpression;
use ischenko\yii2\jsloader\requirejs\JsRenderer;
use ischenko\yii2\jsloader\requirejs\Module;

class JsRendererTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    /**
     * @var \ischenko\yii2\jsloader\tests\UnitTester
     */
    protected $tester;

    protected function _before()
    {
        parent::_before();
    }

    /** Tests go below */

    public function testInstance()
    {
        verify(new JsRenderer())->isInstanceOf('ischenko\yii2\jsloader\JsRendererInterface');
    }

    public function testRenderJsExpression()
    {
        $renderer = new JsRenderer();
        $expression = new JsExpression();

        verify($expression->render($renderer))->equals('');

        $expression->setExpression('test;');

        verify($expression->render($renderer))->equals('test;');

        $mod1 = new Module('mod1');
        $mod1->addFile('file');

        $mod2 = new Module('mod2');

        verify((new JsExpression(null, [$mod2]))->render($renderer))->equals('');

        $mod2->addFile('file');

        $mod1->setExports('mod1');

        verify((new JsExpression(null, [$mod2]))->render($renderer))->equals("require([\"mod2\"]);");

        verify((new JsExpression(new JsExpression(null, [$mod2])))->render($renderer))->equals("require([\"mod2\"]);");

        verify((new JsExpression('test;', [$mod2, $mod1]))->render($renderer))
            ->equals("require([\"mod2\",\"mod1\"], function(undefined,mod1) {\ntest;\n});");

        $mod1->setExports(null);

        verify((new JsExpression('test;', [$mod2, $mod1]))->render($renderer))
            ->equals("require([\"mod2\",\"mod1\"], function() {\ntest;\n});");

        $mod1->setExports('mod1');

        $mod3 = new Module('mod3');
        $mod3->addFile('file');

        verify((new JsExpression('test;', [$mod2, $mod1, $mod3]))->render($renderer))
            ->equals("require([\"mod2\",\"mod1\",\"mod3\"], function(undefined,mod1) {\ntest;\n});");

        verify((new JsExpression('test;', [$mod2, $mod1, $mod3, $mod3, $mod1, $mod3, $mod3, $mod3, $mod1]))->render($renderer))
            ->equals("require([\"mod2\",\"mod1\",\"mod3\",\"mod3\",\"mod1\",\"mod3\",\"mod3\",\"mod3\",\"mod1\"], function(undefined,mod1,undefined,undefined,mod1,undefined,undefined,undefined,mod1) {\ntest;\n});");

        $expression = new JsExpression(new JsExpression('test;', [$mod2]), [$mod1]);

        verify($expression->render($renderer))->equals("require([\"mod1\"], function(mod1) {\nrequire([\"mod2\"], function() {\ntest;\n});\n});");

        $expression = new JsExpression(new JsExpression('test;', [$mod2]), [$mod1, $mod3]);

        $mod1->addFile('file2');
        $mod3->setExports('mod3');

        verify($expression->render($renderer))->equals("require([\"mod3\"], function(mod3) {\nrequire([\"file.js\",\"file2.js\"], function() {\nrequire([\"mod2\"], function() {\ntest;\n});\n});\n});");
    }
}
