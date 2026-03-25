<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }
    public function buildGatherResponse(string $promptMessage, string $actionUrl): string
    {
        $promptMessage = htmlspecialchars($promptMessage, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $actionUrl = htmlspecialchars($actionUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response>"
            . "<Gather input=\"speech\" action=\"{$actionUrl}\" timeout=\"5\" speechTimeout=\"auto\">"
            . "<Say voice=\"Polly.Joanna\">{$promptMessage}</Say>"
            . "</Gather>"
            . "<Say voice=\"Polly.Joanna\">We did not receive any input. Goodbye.</Say>"
            . "</Response>";
    }
    public function buildContinueResponse(string $aiMessage, string $actionUrl): string
    {
        $aiMessage = htmlspecialchars($aiMessage, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $actionUrl = htmlspecialchars($actionUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $goodbye = htmlspecialchars('Thank you for calling. Goodbye.', ENT_XML1 | ENT_QUOTES, 'UTF-8');
        
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response>"
            . "<Gather input=\"speech\" action=\"{$actionUrl}\" timeout=\"5\" speechTimeout=\"auto\">"
            . "<Say voice=\"Polly.Joanna\">{$aiMessage}</Say>"
            . "</Gather>"
            . "<Say voice=\"Polly.Joanna\">We did not receive any input. {$goodbye}</Say>"
            . "<Hangup/>"
            . "</Response>";
    }
    public function buildVoiceResponse(string $message): string
    {
        $message = htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response><Say voice=\"Polly.Joanna\">{$message}</Say></Response>";
    }
    public function buildHangupResponse(string $message): string
    {
        $message = htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response>"
            . "<Say voice=\"Polly.Joanna\">{$message}</Say>"
            . "<Hangup/>"
            . "</Response>";
    }
}