<?php

namespace App\Http\Controllers;

use App\Ai\Agents\CustomerSupportAgent;
use App\Models\CallLog;
use App\Models\Customer;
use App\Services\CallLogService;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TwilioController extends Controller
{
    public function __construct(
        protected TwilioService $twilioService,
        protected CallLogService $callLogService
    ) {}

    public function handleInbound(Request $request): Response
    {
        $callerNumber = $request->input('From', 'unknown');
        $callSid = $request->input('CallSid', 'test-' . time());
        $customer = Customer::find(1); 
        
        if (!$customer) {
            $customer = Customer::first();
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

        $agent = new CustomerSupportAgent($customer, $callLogId);
        
        $aiResponse = $agent->prompt($userSpeech);

        $this->callLogService->addMessage($callLogId, 'assistant', (string) $aiResponse);

        $twiml = $this->twilioService->buildContinueResponse(
            (string) $aiResponse,
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