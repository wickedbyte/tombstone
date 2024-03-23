<?php

declare(strict_types=1);

namespace WickedByte\Tombstone;

use Psr\EventDispatcher\StoppableEventInterface;

class TombstoneActivated implements StoppableEventInterface, \JsonSerializable
{
    public readonly string $id;

    public readonly string $reference;

    /**
     * @param array<string, mixed> $extra
     * @param array<int, StackFrame> $trace
     */
    public function __construct(
        public readonly string $message,
        public readonly StackFrame $caller,
        public readonly array $trace = [],
        public readonly array $extra = [],
        public readonly RequestContext $context = new RequestContext(),
        public readonly \DateTimeImmutable $timestamp = new \DateTimeImmutable(),
        public bool $propagate = true,
    ) {
        $this->reference = $this->caller->class . $this->caller->type . $this->caller->function;
        $this->id = \hash('xxh3', $this->reference . $message);
    }

    /**
     * Allows this object to natively act as stoppable event when used with a
     * PSR-14 dispatcher, using the same mechanism as the internal "stop propagation"
     * behavior.
     *
     * @link https://www.php-fig.org/psr/psr-14/
     * @see \WickedByte\Tombstone\Handlers\PsrEventDispatcherHandler
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagate === false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'message' => $this->message,
            'reference' => $this->reference,
            'timestamp' => $this->timestamp->format(\DATE_RFC3339),
            'stack_trace' => \array_map(static fn(StackFrame $frame): array => $frame->toArray(), $this->trace),
            'request_context' => $this->context->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
