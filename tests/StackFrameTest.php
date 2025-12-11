<?php

declare(strict_types=1);

namespace WickedByte\Tests\Tombstone;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use WickedByte\Tombstone\StackFrame;

#[CoversClass(StackFrame::class)]
final class StackFrameTest extends TestCase
{
    #[Test]
    #[TestWith([StackFrame::TYPE_FUNCTION])]
    #[TestWith([StackFrame::TYPE_STATIC])]
    #[TestWith([StackFrame::TYPE_OBJECT])]
    public function toArrayHasExpectedRepresentation(string|null $type): void
    {
        $stack_frame = new StackFrame(
            file: 'file_value',
            line: 42,
            function: 'function_value',
            class: 'class_value',
            type: $type,
        );

        self::assertSame([
            'file' => 'file_value',
            'line' => 42,
            'class' => 'class_value',
            'type' => $type,
            'function' => 'function_value',
        ], $stack_frame->toArray());

        self::assertSame([
            'file' => 'file_value',
            'line' => 42,
            'class' => 'class_value',
            'type' => $type,
            'function' => 'function_value',
        ], $stack_frame->jsonSerialize());
    }

    #[Test]
    public function toArrayHasExpectedRepresentationNullCase(): void
    {
        self::assertSame([
            'file' => '',
            'line' => 0,
            'class' => null,
            'type' => null,
            'function' => '',
        ], (new StackFrame())->toArray());
    }
}
