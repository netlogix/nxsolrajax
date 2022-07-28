<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

use JsonSerializable;

class SuggestResultSet implements JsonSerializable
{

    protected array $suggestions = [];

    protected string $keyword = '';

    public function __construct(array $suggestions, string $keyword)
    {
        $this->suggestions = $suggestions;
        $this->keyword = $keyword;
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
