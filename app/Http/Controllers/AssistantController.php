<?php

namespace App\Http\Controllers;

use App\Ai\Agents\AppointmentAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class AssistantController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('assistant/index', [
            'conversations' => DB::table('agent_conversations')
                ->where('user_id', $request->user()->id)
                ->orderByDesc('updated_at')
                ->limit(30)
                ->get(['id', 'title', 'updated_at']),
        ]);
    }

    public function show(Request $request, string $conversation): JsonResponse
    {
        $ownsConversation = DB::table('agent_conversations')
            ->where('id', $conversation)
            ->where('user_id', $request->user()->id)
            ->exists();

        abort_unless($ownsConversation, 404);

        $messages = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversation)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id')
            ->get(['role', 'content'])
            ->filter(fn ($message) => filled($message->content))
            ->map(fn ($message) => ['role' => $message->role, 'content' => $message->content])
            ->values();

        return response()->json([
            'conversation_id' => $conversation,
            'messages' => $messages,
        ]);
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
