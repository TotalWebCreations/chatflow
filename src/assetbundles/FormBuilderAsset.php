<?php

namespace totalwebcreations\chatflow\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\jqueryui\JqueryUiAsset;

class FormBuilderAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = '@totalwebcreations/chatflow/assets';

        $this->depends = [
            CpAsset::class,
            JqueryUiAsset::class,
        ];

        $this->css = [
            'css/form-builder.css',
        ];

        $this->js = [
            'js/form-builder.js',
        ];

        parent::init();
    }
}
