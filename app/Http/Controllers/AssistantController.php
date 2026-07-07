<?php

namespace App\Http\Controllers;

use App\Ai\Agents\AppointmentAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class AssistantController extends Controller
{
    public function index()
    {
        return Inertia::render('assistant/index');
    }

    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'string'],
        ]);

        try {
            $assistant = new AppointmentAssistant;
            $conversationId = $validated['conversation_id'] ?? null;

            if ($conversationId) {
                $assistant->continue($conversationId, as: $request->user());
            } else {
                $assistant->forUser($request->user());
            }

            $response = $assistant->prompt($validated['message']);

            return response()->json([
                'reply' => (string) $response,
                'conversation_id' => $response->conversationId,
            ]);
        } catch (RateLimitedException $exception) {
            report($exception);

            return response()->json([
                'message' => 'The assistant is temporarily unavailable because the AI provider rate limit was reached. Please wait a minute and try again.',
            ], 429);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The assistant could not process your message. Please try again.',
            ], 500);
        }
    }
}
