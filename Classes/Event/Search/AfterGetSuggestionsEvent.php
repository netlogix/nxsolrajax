<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Event\Search;

use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;

final class AfterGetSuggestionsEvent
{

    public function __construct(
        public readonly string $query,
        private array $suggestions,
        public readonly TypoScriptConfiguration $typoScriptConfiguration
    ) {
    }

    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    public function setSuggestions(array $suggestions): void
    {
        $this->suggestions = $suggestions;
    }
}
