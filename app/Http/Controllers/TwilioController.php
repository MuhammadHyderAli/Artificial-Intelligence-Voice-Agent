<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\ConversationMessage;
use App\Models\Customer;
use App\Services\CallLogService;
use App\Services\OpenAIService;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TwilioController extends Controller
{
    public function __construct(
        protected TwilioService $twilioService,
        protected CallLogService $callLogService,
        protected OpenAIService $openAIService
    ) {}

    public function handleInbound(Request $request): Response
    {
        $callerNumber = $request->input('From', 'unknown');
        $callSid = $request->input('CallSid', 'test-' . time());

        $customer = null;
        if (str_starts_with($callerNumber, 'sip:')) {
            $customer = Customer::find(1);
        } else {
            $normalizedPhone = str_starts_with($callerNumber, '+') ? $callerNumber : '+' . $callerNumber;
            $customer = Customer::where('phone', $normalizedPhone)->first();
        }

        $callLog = $this->callLogService->createLog(
            customerId: $customer?->id,
            simulatedQuery: 'Inbound call from ' . $callerNumber,
            callSid: $callSid
        );

        session(['call_log_id' => $callLog->id]);
        session(['customer_id' => $customer?->id]);

        $twiml = $this->twilioService->buildGatherResponse(
            'Welcome! How can I help you today?',
            route('twilio.process-speech')
        );

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    public function processSpeech(Request $request): Response
    {
        $userSpeech = $request->input('SpeechResult', '');
        $lowerSpeech = strtolower($userSpeech);
        
        $callLogId = session('call_log_id');
        $customerId = session('customer_id');

        if (!$callLogId) {
            $latestLog = CallLog::latest()->first();
            $callLogId = $latestLog?->id;
            $customerId = $latestLog?->customer_id;
        }

        if (!$callLogId) {
            return response($this->twilioService->buildVoiceResponse('Call session lost. Goodbye.'), 200)
                ->header('Content-Type', 'text/xml');
        }

        $exitKeywords = ['no thanks', 'goodbye', 'bye', 'quit', 'no that is all', 'nothing else', 'no thank you'];
        foreach ($exitKeywords as $keyword) {
            if (str_contains($lowerSpeech, $keyword)) {
                $this->callLogService->addMessage($callLogId, 'user', $userSpeech);
                $this->callLogService->addMessage($callLogId, 'assistant', 'Exit detected. Hanging up.');

                $twiml = $this->twilioService->buildHangupResponse("No problem. Thanks for calling. Goodbye!");
                return response($twiml, 200)->header('Content-Type', 'text/xml');
            }
        }

        if (empty($userSpeech)) {
            $twiml = $this->twilioService->buildGatherResponse(
                "I'm sorry, I didn't catch that. Could you repeat it?",
                route('twilio.process-speech')
            );
            return response($twiml, 200)->header('Content-Type', 'text/xml');
        }

        $this->callLogService->addMessage($callLogId, 'user', $userSpeech);

        $customer = $customerId ? Customer::find($customerId) : null;
        $context = [
            'customer_name' => $customer?->name ?? 'Guest',
            'orders' => $customer?->orders()->latest()->take(3)->get()->toArray() ?? [],
            'tickets' => $customer?->tickets()->latest()->take(3)->get()->toArray() ?? [],
        ];

        $history = ConversationMessage::where('call_log_id', $callLogId)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        $aiResponse = $this->openAIService->generateResponse($userSpeech, $context, $history);
        $this->callLogService->addMessage($callLogId, 'assistant', $aiResponse);

        $twiml = $this->twilioService->buildContinueResponse(
            $aiResponse,
            route('twilio.process-speech')
        );

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    public function handleStatusCallback(Request $request): Response
    {
        $callSid = $request->input('CallSid');
        $callDuration = (int) $request->input('CallDuration', 0);

        CallLog::where('call_sid', $callSid)->update([
            'duration' => $callDuration,
            'status' => 'completed',
        ]);

        return response('OK', 200);
    }
}