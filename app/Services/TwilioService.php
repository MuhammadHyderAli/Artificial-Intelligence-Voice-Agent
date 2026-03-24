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

    /**
     * TwiML with <Gather input="speech" action="$actionUrl" timeout="5" speechTimeout="auto">, <Say> inside, then fallback.
     */
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

    /**
     * Say AI message, then Gather "Is there anything else I can help you with?", then Say goodbye and Hangup.
     */
    public function buildContinueResponse(string $aiMessage, string $actionUrl): string
    {
        $aiMessage = htmlspecialchars($aiMessage, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $actionUrl = htmlspecialchars($actionUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $followUp = htmlspecialchars('Is there anything else I can help you with?', ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $goodbye = htmlspecialchars('Thank you for calling. Goodbye.', ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response>"
            . "<Say voice=\"Polly.Joanna\">{$aiMessage}</Say>"
            . "<Gather input=\"speech\" action=\"{$actionUrl}\" timeout=\"5\">"
            . "<Say voice=\"Polly.Joanna\">{$followUp}</Say>"
            . "</Gather>"
            . "<Say voice=\"Polly.Joanna\">{$goodbye}</Say>"
            . "<Hangup/>"
            . "</Response>";
    }

    /**
     * Simple TwiML <Say voice="Polly.Joanna">$message</Say>
     */
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
