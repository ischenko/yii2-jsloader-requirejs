<?php

namespace ischenko\yii2\jsloader\tests\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Util\Stub;
use yii\web\AssetManager;
use yii\web\View;

class Unit extends \Codeception\Module
{
    /**
     * @return View
     */
    public function mockView($params = [], $testCase = false)
    {
        return Stub::construct('yii\web\View', [], array_merge([
            'assetManager' => Stub::makeEmpty(AssetManager::class, [
                'getAssetUrl' => function ($bundle, $asset) {
                    return $asset;
                }
            ])
        ], $params), $testCase);
    }

    /**
     * @return \ischenko\yii2\jsloader\ConfigInterface
     */
    public function mockConfigInterface($params = [], $testCase = false)
    {
        return Stub::makeEmpty('ischenko\yii2\jsloader\ConfigInterface', $params, $testCase);
    }

    /**
     * Provides reflection for specific property of an object
     *
     * @param mixed $object
     * @param string $property
     *
     * @return \ReflectionProperty
     */
    public function getProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);

        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property;
    }

    /**
     * Provides reflection for a method of provided object
     *
     * @param mixed $object
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function getMethod($object, $method, $arguments = [])
    {
        $reflection = new \ReflectionClass($object);

        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }
}
