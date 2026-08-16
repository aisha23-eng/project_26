<?php

namespace App\Http\Controllers;

use App\Ai\Agents\FinanceAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use Laravel\Ai\Streaming\Events\ToolResult;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssistantController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Assistant');
    }

    /**
     * Stream an agent response as Server-Sent Events.
     *
     * Production notes for SSE streaming:
     * - nginx: disable buffering via `proxy_buffering off;` (or the
     *   `X-Accel-Buffering: no` header emitted here will be honoured when
     *   `fastcgi_buffering` is also disabled).
     * - Apache: ensure `mod_deflate` / `mod_gzip` do not buffer the stream;
     *   consider disabling compression for text/event-stream.
     * - The application uses `php artisan serve` / standard PHP-FPM. Do NOT
     *   enable Laravel Octane's `output_buffering` or add middleware that
     *   buffers the response body, as it would defeat per-frame flushing.
     */
    public function stream(Request $request): StreamedResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'message' => ['required', 'string', 'max:8000'],
            'history' => ['present', 'array', 'max:50'],
            'history.*.role' => ['required', 'string', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:8000'],
        ]);

        $history = array_map(
            fn (array $message): array => ['role' => $message['role'], 'content' => $message['content']],
            $data['history'],
        );

        $agent = new FinanceAssistant($user, $history);

        return response()->stream(function () use ($agent, $data, $user): void {
            $this->prepareStream();

            try {
                $stream = $agent->stream(
                    $data['message'],
                    provider: env('AI_PROVIDER', config('ai.default')),
                    model: env('AI_MODEL'),
                    timeout: 120,
                );

                foreach ($stream as $event) {
                    if (connection_aborted()) {
                        return;
                    }

                    if ($event instanceof TextDelta) {
                        $this->sendFrame([
                            'type' => 'text',
                            'delta' => $event->delta,
                        ]);
                    } elseif ($event instanceof ToolCall) {
                        $this->sendFrame([
                            'type' => 'tool_call',
                            'id' => $event->toolCall->id,
                            'name' => $event->toolCall->name,
                            'arguments' => $event->toolCall->arguments,
                        ]);
                    } elseif ($event instanceof ToolResult) {
                        $this->sendFrame([
                            'type' => 'tool_result',
                            'id' => $event->toolResult->id,
                            'name' => $event->toolResult->name,
                            'summary' => $this->extractSummary($event->toolResult->result),
                            'ok' => $event->successful && $event->error === null,
                        ]);
                    }
                }
            } catch (RateLimitedException $e) {
                Log::warning('Assistant stream rate limited', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                $this->sendFrame([
                    'type' => 'error',
                    'message' => 'وصلت إلى حد الطلبات لدى مزوّد الذكاء الاصطناعي حالياً. أعد المحاولة بعد قليل، أو فعّل مزوّداً مدفوعاً في ملف الإعدادات.',
                ]);
            } catch (\Throwable $e) {
                Log::error('Assistant stream failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->sendFrame([
                    'type' => 'error',
                    'message' => 'تعذّر الحصول على ردّ من مزوّد الذكاء الاصطناعي في هذه اللحظة. حاول مرة أخرى.',
                ]);
            }

            $this->sendFrame(['type' => 'done']);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Disable output buffering so frames flush immediately.
     */
    private function prepareStream(): void
    {
        if (ob_get_level() > 0) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        }
    }

    /**
     * @param  array<string, mixed>  $frame
     */
    private function sendFrame(array $frame): void
    {
        echo 'data: '.json_encode($frame, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }

    /**
     * Extract the summary from a tool result payload.
     */
    private function extractSummary(mixed $result): string
    {
        if (is_string($result)) {
            $decoded = json_decode($result, true);

            if (is_array($decoded) && isset($decoded['summary'])) {
                return (string) $decoded['summary'];
            }

            return $result;
        }

        if (is_array($result) && isset($result['summary'])) {
            return (string) $result['summary'];
        }

        return (string) ($result ?? '');
    }
}
