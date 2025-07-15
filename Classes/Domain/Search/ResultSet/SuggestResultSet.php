<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

use JsonSerializable;

class SuggestResultSet implements JsonSerializable
{

    public function __construct(protected array $suggestions, protected string $keyword)
    {
    }

    public function jsonSerialize(): array
    {
        $suggestions = [];
        foreach ($this->suggestions as $keywords => $value) {
            $suggestions[] = ['name' => $keywords, 'count' => $value];
        }

        return $suggestions;
    }
}
