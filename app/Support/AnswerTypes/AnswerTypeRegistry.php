<?php

namespace App\Support\AnswerTypes;

use App\Enums\AnswerType;
use InvalidArgumentException;

class AnswerTypeRegistry
{
    /** @var array<string, class-string<AnswerTypeHandler>> */
    private array $map = [
        AnswerType::Boolean->value => BooleanAnswerType::class,
        AnswerType::Rating->value => RatingAnswerType::class,
        AnswerType::SelectSingle->value => SelectSingleAnswerType::class,
        AnswerType::SelectMultiple->value => SelectMultipleAnswerType::class,
        AnswerType::TextShort->value => TextShortAnswerType::class,
        AnswerType::TextLong->value => TextLongAnswerType::class,
        AnswerType::Number->value => NumberAnswerType::class,
        AnswerType::Date->value => DateAnswerType::class,
        AnswerType::File->value => FileAnswerType::class,
        AnswerType::SectionNote->value => TextLongAnswerType::class,
    ];

    public function for(AnswerType $type): AnswerTypeHandler
    {
        $class = $this->map[$type->value]
            ?? throw new InvalidArgumentException("Tipe jawaban tidak dikenal: {$type->value}");

        return app($class);
    }

    /** @return array<int, AnswerType> */
    public function supportedTypes(): array
    {
        return array_map(fn (string $value) => AnswerType::from($value), array_keys($this->map));
    }
}
