<?php

namespace QuneMedia\ChatGpt\Connector;

class OpenAiModels
{
    public static function get($sModel)
    {
        // OpenAI models
        require __DIR__ . '/config/openai-models.php';

        return $aOpenAiModels[$sModel] ?? null;
    }

    public static function getList()
    {
        $aModels = [];

        // OpenAI models
        require __DIR__ . '/config/openai-models.php';

        foreach ($aOpenAiModels as $sModel => $aData) {
            $aModels[$sModel] = $aData['description'];
        }

        return $aModels;
    }

    public static function getDefaultModel($sType = 'text')
    {
        // OpenAI models
        require __DIR__ . '/config/openai-models.php';

        foreach ($aOpenAiModels as $sModel => $aData) {
            if (is_array($aData['default'])) {
                if (in_array($sType, $aData['default'])) {
                    return $sModel;
                }
            }
        }

        // FALLBACK
        return array_key_first($aOpenAiModels);
    }

    public static function getDefaultImageModel()
    {
        return self::getDefaultModel('image');
    }

    public static function getDefaultTranslationModel()
    {
        return self::getDefaultModel('translation');
    }

    public static function getConstraints()
    {
        // OpenAI models
        require __DIR__ . '/config/openai-models.php';

        return array_keys($aOpenAiModels);
    }
}