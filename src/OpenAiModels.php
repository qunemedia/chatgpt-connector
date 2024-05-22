<?php

namespace QuneMedia\ChatGpt\Connector;

class OpenAiModels
{
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

    public static function getDefaultModel()
    {
        // OpenAI models
        require __DIR__ . '/config/openai-models.php';

        return array_key_first($aOpenAiModels);
    }

    public static function getConstraints()
    {
        // OpenAI models
        require __DIR__ . '/config/openai-models.php';

        return array_keys($aOpenAiModels);
    }
}