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

    protected $_API_KEY = "ADD_YOUR_API_KEY_HERE";
    protected $_sOpenAiApiUrl = "https://api.openai.com/v1/completions";
    protected $_sOpenAiApiImageUrl = "https://api.openai.com/v1/images/generations";

    public $oCurl;

    private $_iInfinityLoopCount = 0;

    public function __construct()
    {
        $this->oCurl = curl_init();
    }

    public function initialize($requestType = "text" || "image")
    {
        $this->oCurl = curl_init();

        if ($requestType === 'image')
            curl_setopt($this->oCurl, CURLOPT_URL, $this->_sOpenAiApiImageUrl);
        if ($requestType === 'text')
            curl_setopt($this->oCurl, CURLOPT_URL, $this->_sOpenAiApiUrl);

        curl_setopt($this->oCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->oCurl, CURLOPT_POST, true);

        $headers = array(
            "Content-Type: application/json",
            "Authorization: Bearer $this->_API_KEY"
        );

        curl_setopt($this->oCurl, CURLOPT_HTTPHEADER, $headers);
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
    public function createTextRequest($sPrompt, $sModel = 'gpt-3.5-turbo-instruct', $sTemperature = 0.7, $iMaxTokens = 1000, $bHtml = false, $iLang = null): array
    {
        curl_reset($this->oCurl);
        $this->initialize('text');

        // CHECK MAX TOKENS
        $iMaxTokens = $this->_checkMaxTokens((int) $iMaxTokens);

        // CHECK HTML PROMPT
        if ($bHtml && ($iMaxTokens >= self::MIN_TOKENS) ) {
            $sPrompt = $sPrompt . PHP_EOL . $this->_getChatGptInstruction($iLang);
        }

        $data["model"] = $sModel;
        $data["prompt"] = $sPrompt;
        $data["temperature"] = (double) $sTemperature;
        $data["max_tokens"] = $iMaxTokens;

        curl_setopt($this->oCurl, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($this->oCurl);
        $response = json_decode($response, true);

        // CUSTOM ERROR CODES
        if (isset($response['error']['message']) && $response['error']['code'] == '') {
            if (strpos($response['error']['message'], 'Please reduce your prompt; or completion length.') !== false) {
                $response['error']['code'] = 900;
            } else {
                $response['error']['code'] = 999;
            }
        }

        $output['data'] = $response['choices'][0]['text'] ?? null;
        $output['continue'] = $response['choices'][0]['finish_reason'] == 'length';
        $output['error'] = $response['error']['code'] ?? null;

        return $output;
    }

    protected function _getChatGptInstruction($iLang): string
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
        $this->initialize('image');

        $data["prompt"] = $sPrompt;
        $data["n"] = $iNumberOfImages;
        $data["size"] = $sImageSize;

        curl_setopt($this->oCurl, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($this->oCurl);
        $response = json_decode($response, true);

        $output['data'] = $response['data'][0]['url'] ?? null;
        $output['error'] =  $response['error']['code'] ?? null;
        return $output;
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
