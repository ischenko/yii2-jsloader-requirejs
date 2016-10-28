# yii2-jsloader-requirejs

[![Build Status](https://travis-ci.org/ischenko/yii2-jsloader-requirejs.svg?branch=master)](https://travis-ci.org/ischenko/yii2-jsloader-requirejs)
[![Code Climate](https://codeclimate.com/github/ischenko/yii2-jsloader-requirejs/badges/gpa.svg)](https://codeclimate.com/github/ischenko/yii2-jsloader-requirejs)
[![Test Coverage](https://codeclimate.com/github/ischenko/yii2-jsloader-requirejs/badges/coverage.svg)](https://codeclimate.com/github/ischenko/yii2-jsloader-requirejs/coverage)

An Yii2 extension that allows to register asset bundles as [RequireJS](http://requirejs.org) modules.

## Installation
*Requires PHP >= 5.4*

*Requires [ischenko/yii2-jsloader](https://github.com/ischenko/yii2-jsloader) >= 1.1*

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```
composer require ischenko/yii2-jsloader-requirejs
```

or add

```json
"ischenko/yii2-jsloader-requirejs": "*"
```

to the `require` section of your composer.json.

## Usage

Add the [behavior](https://github.com/ischenko/yii2-jsloader#usage) and requirejs loader to the view configuration

```php
    ...
    'components' => [
        ...
        'view' => [
            'as jsLoader' => [
                'class' => 'ischenko\yii2\jsloader\Behavior',
                'loader' => [
                    'class' => 'ischenko\yii2\jsloader\RequireJs',
                ]
            ]
        ]
        ...
    ]
    ...
```

Modules configuration accepts options described in [RequireJS API docs](http://requirejs.org/docs/api.html#config). 
It is also possible to set aliases for modules, for example:

```php
    ...
    'components' => [
        ...
        'view' => [
            'as jsLoader' => [
                'class' => 'ischenko\yii2\jsloader\Behavior',
                'loader' => [
                    'config' => [
                        'shim' => [
                            'yii\web\JqueryAsset' => [
                                'exports' => 'jQuery'
                            ],
                            'app\assets\jQueryFireflyAsset' => [
                                'deps' => ['yii\web\JqueryAsset']
                            ]
                        ],
                        'aliases' => [
                            'yii\web\JqueryAsset' => 'jq',
                            'app\assets\jQueryFireflyAsset' => 'jqff'
                        ]
                    ],
                    'class' => 'ischenko\yii2\jsloader\RequireJs',
                ]
            ]
        ]
        ...
    ]
    ...
```

Or you can set alias, exports, init options from asset bundle:

```php
class jQueryFireflyAsset extends AssetBundle
{
    public $js
        = [
            'jquery.firefly.min.js'
        ];

    public $jsOptions
        = [
            'requirejs' => [
                'alias' => 'jqff',
                //'init' => 'function(jQuery) { /* do some init here */ }'
                //'exports' => 'some-exported'
            ]
        ];

    public $depends
        = [
            'yii\web\JqueryAsset',
        ];

        
//    public function registerAssetFiles($view)
//    {
//        parent::registerAssetFiles($view);
        
//        $this->jsOptions['requirejs']['init'] =<<<EOS
//function(jQuery) {
//    Or do some complex init... 
//}
//EOS;
//    }

}
```

This will produce following output:

```javascript
var require = {
    "shim": {
        "jq": {
            "exports": "jQuery"
        }, 
        "jqff": {
            "deps": ["jq"]
        }
    },
    "paths": {
        "jq": ["/assets/e7b76d86/jquery"],
        "jqff": ["/assets/4127fff7/jquery.firefly.min"]
    }
};
```

**Please note** that aliases works only within client-side code. On server-side you still need to operate with actual module names.

##License
**MIT**
