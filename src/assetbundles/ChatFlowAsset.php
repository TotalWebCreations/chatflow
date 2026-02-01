<?php

namespace totalwebcreations\chatflow\assetbundles;

use craft\web\AssetBundle;

class ChatFlowAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = '@totalwebcreations/chatflow/assets';

        $this->css = [
            'css/chatflow.css',
        ];

        $this->js = [
            'js/chatflow.js',
        ];

        parent::init();
    }
}
