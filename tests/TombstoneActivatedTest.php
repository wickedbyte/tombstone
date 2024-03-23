<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WickedByte\Tests\Tombstone\Fixtures\StubsTombstoneActivation;
use WickedByte\Tombstone\RequestContext;
use WickedByte\Tombstone\StackFrame;
use WickedByte\Tombstone\TombstoneActivated;

#[CoversClass(TombstoneActivated::class)]
class TombstoneActivatedTest extends TestCase
{
    use StubsTombstoneActivation;

    #[Test]
    public function idIsUniqueToCallerAndMessage(): void
    {
        $tombstone_0 = self::stubTombstoneActivation(
            '2024-01-01 Test Message',
            caller: new StackFrame(
                function: __FUNCTION__,
                class: self::class,
                type: StackFrame::TYPE_OBJECT,
            ),
        );

        $tombstone_1 = self::stubTombstoneActivation(
            '2024-01-01 Test Message',
            caller: new StackFrame(
                function: __FUNCTION__,
                class: self::class,
                type: StackFrame::TYPE_OBJECT,
            ),
        );

        $tombstone_2 = self::stubTombstoneActivation(
            '2024-01-02 Test Message',
            caller: new StackFrame(
                function: __FUNCTION__,
                class: self::class,
                type: StackFrame::TYPE_OBJECT,
            ),
        );

        $tombstone_3 = self::stubTombstoneActivation(
            '2024-01-01 Test Message',
            caller: new StackFrame(
                function: 'Different',
                class: self::class,
                type: StackFrame::TYPE_OBJECT,
            ),
        );

        self::assertSame($tombstone_0->id, $tombstone_1->id);
        self::assertNotSame($tombstone_0->id, $tombstone_2->id);
        self::assertNotSame($tombstone_0->id, $tombstone_3->id);
    }

    #[Test]
    public function referenceIsUniqueToCaller(): void
    {
        $tombstone_0 = self::stubTombstoneActivation(
            '2024-01-01 Test Message',
            caller: new StackFrame(
                function: __FUNCTION__,
                class: self::class,
                type: StackFrame::TYPE_OBJECT,
            ),
        );

        $tombstone_1 = self::stubTombstoneActivation(
            '2024-01-01 Test Message',
            caller: new StackFrame(
                function: __FUNCTION__,
                class: self::class,
                type: StackFrame::TYPE_OBJECT,
            ),
        );

        $tombstone_2 = self::stubTombstoneActivation(
            '2024-01-02 Test Message',
            caller: new StackFrame(
                function: __FUNCTION__,
                class: self::class,
                type: StackFrame::TYPE_OBJECT,
            ),
        );

        $tombstone_3 = self::stubTombstoneActivation(
            '2024-01-01 Test Message',
            caller: new StackFrame(
                function: 'Different',
                class: self::class,
                type: StackFrame::TYPE_OBJECT,
            ),
        );

        self::assertSame($tombstone_0->reference, $tombstone_1->reference);
        self::assertSame($tombstone_0->reference, $tombstone_2->reference);
        self::assertNotSame($tombstone_0->id, $tombstone_3->id);
    }

    #[Test]
    public function propagationPropertyIsMutable(): void
    {
        $tombstone = self::stubTombstoneActivation();

        self::assertTrue($tombstone->propagate);
        self::assertFalse($tombstone->isPropagationStopped());

        $tombstone->propagate = false;
        self::assertFalse($tombstone->propagate);
        self::assertTrue($tombstone->isPropagationStopped());

        $tombstone->propagate = true;
        self::assertTrue($tombstone->propagate);
        self::assertFalse($tombstone->isPropagationStopped());
    }


    #[Test]
    public function toArrayHasExpectedRepresentation(): void
    {
        $timestamp = new \DateTimeImmutable('2024-04-03T13:32:23Z');
        $tombstone = self::stubTombstoneActivation(
            '2024-01-01 Test Message',
            caller: new StackFrame(
                function: __FUNCTION__,
                class: self::class,
                type: StackFrame::TYPE_OBJECT,
            ),
            trace: [
                new StackFrame(
                    file: 'file_value_0',
                    line: 42,
                    function: 'function_value_0',
                    class: 'class_value_0',
                    type: StackFrame::TYPE_OBJECT,
                ),
                new StackFrame(
                    file: 'file_value_1',
                    line: 43,
                    function: 'function_value_1',
                    class: 'class_value_1',
                    type: StackFrame::TYPE_OBJECT,
                ),
                new StackFrame(
                    file: 'file_value_2',
                    line: 44,
                    function: 'function_value_2',
                    class: 'class_value_2',
                    type: StackFrame::TYPE_OBJECT,
                ),
            ],
            extra: [
                'extra_key' => 'extra_value',
            ],
            context: new RequestContext(),
            timestamp: $timestamp,
        );

        $expected = [
            'id' => $tombstone->id,
            'message' => '2024-01-01 Test Message',
            'reference' => self::class . '->' . __FUNCTION__,
            'timestamp' => '2024-04-03T13:32:23+00:00',
            'stack_trace' => [
                [
                    'file' => 'file_value_0',
                    'line' => 42,
                    'class' => 'class_value_0',
                    'type' => '->',
                    'function' => 'function_value_0',
                ],
                [
                    'file' => 'file_value_1',
                    'line' => 43,
                    'class' => 'class_value_1',
                    'type' => '->',
                    'function' => 'function_value_1',
                ],
                [
                    'file' => 'file_value_2',
                    'line' => 44,
                    'class' => 'class_value_2',
                    'type' => '->',
                    'function' => 'function_value_2',
                ],
            ],
            'request_context' => [
                'method' => null,
                'query' => null,
                'path' => null,
                'host' => null,
                'user_agent' => null,
                'ip_address' => null,
                'sapi' => \PHP_SAPI,
            ],
        ];

        self::assertSame($expected, $tombstone->toArray());
        self::assertSame($expected, $tombstone->jsonSerialize());
    }
}
