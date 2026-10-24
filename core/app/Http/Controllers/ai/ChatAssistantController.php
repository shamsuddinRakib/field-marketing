<?php

namespace App\Http\Controllers\ai;

use App\Ai\Agents\MarketingRepresentativeDataAssistant;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;

class ChatAssistantController extends Controller
{
    public function ask(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'conversation_id' => 'nullable|string|max:64',
        ]);

        try {
            $agentResponse = (new MarketingRepresentativeDataAssistant())
                ->continueOrStart($data['conversation_id'] ?? null, auth()->user())
                ->prompt($data['message']);

            return response()->json([
                'ok' => true,
                'message' => (string) $agentResponse,
                'conversation_id' => $agentResponse->conversationId,
            ]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'message' => 'Something went wrong!'. $e->getMessage()]);
        }
    }
}
