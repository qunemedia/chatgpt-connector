<?php
//<!-- Simple ChatGPT Class that enables both text and image prompt
//to use this class in another file just import it and call one of the 2 functions createTextRequest() or generateImage() with your prompt (or options)
//
//Code Example:
//
//include_once('ChatGPT.php'); // include class from folder
//$ai = new ChatGPT(); // initialize class object
//echo $ai->generateImage('a cat on a post lamp')['data'] ?? 'ERROR!'; // print the image URL or error text
//echo $ai->createTextRequest('what is the weather in Romania?')['data'] ?? 'ERROR!'; // print the text response or error text -->

namespace QuneMedia\ChatGpt\Connector;

class ChatGPT
{
    const MIN_TOKENS = 375;
    const MAX_TOKENS = 5000;

    const MAX_CONTINUE_REQUESTS = 3;

    protected $_sApiKey = null;
    protected $_sOpenAiApiUrl = null;
    protected $_sOpenAiApiImageUrl = null;
    protected $_sOpenAiApiTranslationUrl = null;

    protected $_sModel = 'gpt-3.5-turbo';
    protected $_sMode = 'text';

    public $oCurl;

    public function __construct()
    {
        $this->oCurl = curl_init();
    }

    private function _getApiKey(): string
    {
        return $this->_sApiKey;
    }

    public function setApiKey($sApiKey)
    {
        $this->_sApiKey = $sApiKey;
    }

    private function _getOpenAiApiUrl(): string
    {
        if ($this->_sOpenAiApiImageUrl == null) {
            $aModel = OpenAiModels::get($this->_sModel);

            $this->_sOpenAiApiUrl = $aModel['api']['url'];
        }

        return $this->_sOpenAiApiUrl;
    }

    public function setOpenAiApiUrl($sOpenAiApiUrl)
    {
        $this->_sOpenAiApiUrl = $sOpenAiApiUrl;
    }

    private function _getOpenAiApiImageUrl(): string
    {
        if ($this->_sOpenAiApiImageUrl == null) {
            $aModel = OpenAiModels::get(OpenAiModels::getDefaultImageModel());

            $this->_sOpenAiApiImageUrl = $aModel['api']['image_url'];
        }

        return $this->_sOpenAiApiImageUrl;
    }

    public function setOpenAiApiImageUrl($sOpenAiApiImageUrl)
    {
        $this->_sOpenAiApiImageUrl = $sOpenAiApiImageUrl;
    }

    private function _getOpenAiApiTranslationUrl(): string
    {
        return $this->_getOpenAiApiUrl();
    }

    private function _setModel($sModel)
    {
        $this->_sModel = $sModel;
    }

    public function setMode($sMode)
    {
        $this->_sMode = $sMode;
    }

    private function _initialize($sRequestType = "text" || "image" || "translation")
    {
        $this->oCurl = curl_init();

        if ($sRequestType === 'image') {
            curl_setopt($this->oCurl, CURLOPT_URL, $this->_getOpenAiApiImageUrl());
        } elseif ($sRequestType === 'translation') {
            curl_setopt($this->oCurl, CURLOPT_URL, $this->_getOpenAiApiTranslationUrl());
        } else {
            curl_setopt($this->oCurl, CURLOPT_URL, $this->_getOpenAiApiUrl());
        }

        curl_setopt($this->oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->oCurl, CURLOPT_POST, true);

        $aHeaders = array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->_getApiKey()
        );

        curl_setopt($this->oCurl, CURLOPT_HTTPHEADER, $aHeaders);
    }

    /**
     * Generates a text response based on the given prompt using the specified parameters.
     *
     * @param string $sPrompt The prompt for generating the text response.
     * @param string $sModel The GPT-3 model to use for text generation.
     * @param float $sTemperature The temperature parameter for controlling randomness (default: 0.7).
     * @param int $iMaxTokens The maximum number of tokens in the generated text (default: 1000).
     * @return array An array containing 'data' and 'error' keys, representing the generated text and any errors.
     */
    public function createTextRequest($sPrompt, $sModel = 'gpt-3.5-turbo', $sTemperature = 0.7, $iMaxTokens = 1000, $bHtml = false, $iLang = null): array
    {
        curl_reset($this->oCurl);

        // SET MODEL
        $this->_setModel($sModel);

        // INITIALIZE
        $this->_initialize($this->_sMode);

        // CHECK MAX TOKENS
        $iMaxTokens = $this->_checkMaxTokens((int) $iMaxTokens);

        // CHECK HTML PROMPT
        if ($bHtml && ($iMaxTokens >= self::MIN_TOKENS) ) {
            $sPrompt = $sPrompt . PHP_EOL . $this->_getExtendedPrompt($iLang);
        }

        $aData["model"] = $sModel;
        $aData["prompt"] = $sPrompt;
        $aData["temperature"] = (double) $sTemperature;
        $aData["max_tokens"] = $iMaxTokens;

        curl_setopt($this->oCurl, CURLOPT_POSTFIELDS, json_encode($aData));

        $aResponse = curl_exec($this->oCurl);
        $aResponse = json_decode($aResponse, true);

        // CUSTOM ERROR CODES
        if (isset($aResponse['error']['message']) && $aResponse['error']['code'] == '') {
            if (strpos($aResponse['error']['message'], 'Please reduce your prompt; or completion length.') !== false) {
                $aResponse['error']['code'] = 900;
            } else {
                $aResponse['error']['code'] = 999;
            }
        }

        $aOutput["model"] = $sModel;
        $aOutput['data'] = $aResponse['choices'][0]['text'] ?? null;
        $aOutput['continue'] = $aResponse['choices'][0]['finish_reason'] == 'length';
        $aOutput['error'] = $aResponse['error']['code'] ?? null;
        $aOutput['error_msg'] = $aResponse['error']['message'];

        return $aOutput;
    }

    protected function _getExtendedPrompt($iLang): string
    {
        return '';
    }

    /**
     * Generates an image URL based on the given prompt and parameters.
     *
     * @param string $sPrompt The prompt for generating the image URL.
     * @param string $sImageSize The desired image size (default: '512x512').
     * @param int $iNumberOfImages The number of images to generate (default: 1).
     * @return array An array containing ['data'] and ['error'] keys, representing the generated image URL and any errors.
     */
    public function generateImage($sPrompt, $sImageSize = '512x512', $iNumberOfImages = 1): array
    {
        curl_reset($this->oCurl);
        $this->_initialize('image');

        $aData["prompt"] = $sPrompt;
        $aData["n"] = $iNumberOfImages;
        $aData["size"] = $sImageSize;

        curl_setopt($this->oCurl, CURLOPT_POSTFIELDS, json_encode($aData));

        $response = curl_exec($this->oCurl);
        $response = json_decode($response, true);

        $aOutput['data'] = $response['data'][0]['url'] ?? null;
        $aOutput['error'] =  $response['error']['code'] ?? null;
        return $aOutput;
    }

    protected function _checkMaxTokens($iMaxTokens): int
    {
        if ($iMaxTokens < self::MIN_TOKENS) {
            $iMaxTokens = self::MIN_TOKENS;
        }

        if (($iMaxTokens * 1.1) > self::MAX_TOKENS) {
            $iMaxTokens = self::MAX_TOKENS;
        }

        return $iMaxTokens;
    }
}
