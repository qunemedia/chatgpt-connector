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

    protected function _createRequest(array $aParams = [])
    {
        // INITIALIZE
        $this->_initialize($this->_sMode);

        // SET PARAMETERS
        curl_setopt($this->oCurl, CURLOPT_POSTFIELDS, json_encode($aParams));

        $sResponse = curl_exec($this->oCurl);
        $iHttpCode = curl_getinfo($this->oCurl, CURLINFO_HTTP_CODE);

        if ($sResponse === false) {
            return ['error' => ['code' => 'CURL_ERROR', 'message' => curl_error($this->oCurl)]];
        }

        $aResponse = json_decode($sResponse, true);

        if ($iHttpCode >= 400) {
            return ['error' => ['code' => $iHttpCode, 'message' => $aResponse['error']['message'] ?? 'Unknown error']];
        }

        return $aResponse;
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

        // CHECK MAX TOKENS
        $iMaxTokens = $this->_checkMaxTokens((int) $iMaxTokens);

        // CHECK HTML PROMPT
        if ($bHtml && ($iMaxTokens >= self::MIN_TOKENS) ) {
            $sPrompt = $sPrompt . PHP_EOL . $this->_getExtendedPrompt($iLang);
        }

        $aParams = [
            'model' => $this->_sModel,
            'prompt' => $sPrompt,
            'max_tokens' => $iMaxTokens,
            'temperature' => (double) $sTemperature,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ];

        $aResponse = $this->_createRequest($aParams);

        $aOutput["id"] = $aResponse['id'] ?? null;
        $aOutput["model"] = $sModel;
        $aOutput['data'] = $aResponse['choices'][0]['text'] ?? null;
        // TODO: #64861 DEPRECATED - REMOVE IN FUTURE
        $aOutput['continue'] = FALSE;
        $aOutput['error'] = $aResponse['error']['code'] ?? null;
        $aOutput['error_msg'] = $aResponse['error']['message'];

        // HANDLING INCOMPLETE RESPONSE
        if (isset($aResponse['choices'][0]['finish_reason']) && $aResponse['choices'][0]['finish_reason'] === 'length') {
            $sContinuationPrompt = $sPrompt . $aResponse['choices'][0]['text'];

            for ($i = 0; $i < self::MAX_CONTINUE_REQUESTS; $i++) {
                $aParams['prompt'] = $sContinuationPrompt;
                $aResponse = $this->_createRequest($aParams);
                $aOutput['data'] .= $aResponse['choices'][0]['text'] ?? '';

                if (isset($aResponse['choices'][0]['finish_reason']) && $aResponse['choices'][0]['finish_reason'] !== 'length') {
                    break;
                }

                $sContinuationPrompt .= $aResponse['choices'][0]['text'];
            }
        }

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
