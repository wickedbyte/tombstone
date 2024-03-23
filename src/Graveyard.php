<?php

declare(strict_types=1);

namespace WickedByte\Tombstone;

class Graveyard
{
    private static GraveyardConfiguration|null $config = null;

    public static function config(GraveyardConfiguration|null $config = null): GraveyardConfiguration
    {
        return match ($config) {
            null => self::$config ??= new GraveyardConfiguration(),
            default => self::$config = $config,
        };
    }

    public static function reset(): void
    {
        self::$config = null;
    }

    /**
     * @param array<string, mixed> $extra
     */
    public static function tombstone(
        string $message = '',
        array $extra = [],
    ): void {
        try {
            $trace = self::trace();
            $caller = new StackFrame(
                file: $trace[0]->file ?? '',
                line: $trace[0]->line ?? 0,
                function: $trace[1]->function ?? '',
                class: $trace[1]->class ?? '',
                type: $trace[1]->class ?? '',
            );
            $context = self::config()->context_provider->context();

            $tombstone = new TombstoneActivated($message, $caller, $trace, $extra, $context);
        } catch (\Throwable $e) {
            self::config()->logger?->error('tombstone activation failed', [
                'exception' => $e,
                'tombstone' => [
                    'message' => $message,
                ],
            ]);

            if (self::config()->rethrow_exceptions) {
                throw $e;
            }

            return;
        }

        foreach (self::config()->handlers as $handler) {
            try {
                $handler($tombstone);
            } catch (\Throwable $e) {
                self::config()->logger?->error('tombstone handler failed: ' . $tombstone->reference, [
                    'exception' => $e,
                    'handler' => $handler::class,
                    'tombstone' => $tombstone->toArray(),
                ]);

                if (self::config()->rethrow_exceptions) {
                    throw $e;
                }
            }

            if ($tombstone->propagate === false) {
                return;
            }
        }
    }

    /** @return array<int, StackFrame> */
    private static function trace(): array
    {
        $trace = [];
        foreach (\debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, self::config()->trace_depth + 5) as $frame) {
            $stack_frame = new StackFrame(...$frame);
            if ($stack_frame->class === self::class && $stack_frame->function !== 'tombstone') {
                continue;
            }

            if ($stack_frame->class === null && $stack_frame->function === 'tombstone') {
                continue;
            }

            $trace[] = $stack_frame;
            if (\count($trace) === self::config()->trace_depth) {
                break;
            }
        }

        return $trace;
    }
}
