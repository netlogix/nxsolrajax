<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Event\Search;

use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;

final class AfterGetSuggestionsEvent
{

    private string $query;
    private array $suggestions;
    private TypoScriptConfiguration $typoScriptConfiguration;

    public function __construct(string $query, array $suggestions, TypoScriptConfiguration $typoScriptConfiguration)
    {
        $this->query = $query;
        $this->suggestions = $suggestions;
        $this->typoScriptConfiguration = $typoScriptConfiguration;
    }

    public function getTypoScriptConfiguration(): TypoScriptConfiguration
    {
        return $this->typoScriptConfiguration;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    public function setSuggestions(array $suggestions): AfterGetSuggestionsEvent
    {
        $this->suggestions = $suggestions;
        return $this;
    }
}