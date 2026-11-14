<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RequestResponseLogger
{
    public const STARTED_AT_ATTRIBUTE = '_request_response_log_started_at';

    private const LOGGED_ATTRIBUTE = '_request_response_log_recorded';

    private const SENSITIVE_FIELD_PATTERN = '/password|passwd|token|secret|authorization|cookie|api[_-]?key|credential|otp|pin/i';

    public function record(Request $request, Response $response, ?Throwable $exception = null): void
    {
        if ($request->attributes->get(self::LOGGED_ATTRIBUTE)) {
            return;
        }

        try {
            $startedAt = $request->attributes->get(self::STARTED_AT_ATTRIBUTE);
            $durationMs = is_numeric($startedAt)
                ? max(0, (int) round((microtime(true) - (float) $startedAt) * 1000))
                : null;

            DB::table('request_response_logs')->insert([
                'method' => $request->method(),
                'path' => $request->path(),
                'request_data' => $this->encode($this->sanitize($request->all())),
                'response_status' => $response->getStatusCode(),
                'response_data' => $this->responseData($response),
                'exception_class' => $exception ? get_class($exception) : null,
                'exception_message' => $exception ? $this->redactText($exception->getMessage()) : null,
                'ip_address' => $request->ip(),
                'duration_ms' => $durationMs,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $request->attributes->set(self::LOGGED_ATTRIBUTE, true);
        } catch (Throwable $loggingException) {
            Log::error('Failed to persist request/response log', [
                'exception_class' => get_class($loggingException),
                'path' => $request->path(),
            ]);
        }
    }

    private function responseData(Response $response): ?string
    {
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->encode($this->sanitize($decoded));
        }

        return $this->redactText($content);
    }

    private function sanitize(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return '[uploaded file]';
        }

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                if (is_string($key) && preg_match(self::SENSITIVE_FIELD_PATTERN, $key)) {
                    $sanitized[$key] = '[REDACTED]';

                    continue;
                }

                $sanitized[$key] = $this->sanitize($item);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            return '[object '.get_class($value).']';
        }

        if (is_string($value)) {
            return $this->redactText($value);
        }

        return $value;
    }

    private function encode(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
    }

    private function redactText(string $text): string
    {
        $text = preg_replace(
            '/(["\']?(?:password|passwd|token|secret|authorization|cookie|api[_-]?key|credential|otp|pin)["\']?\s*[:=]\s*["\']?)[^"\',\s}&]+/i',
            '$1[REDACTED]',
            $text
        ) ?? '[exception message redacted]';

        return preg_replace('/\bBearer\s+\S+/i', 'Bearer [REDACTED]', $text) ?? '[exception message redacted]';
    }
}
