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

    /**
     * @return TypoScriptConfiguration
     */
    public function getTypoScriptConfiguration(): TypoScriptConfiguration
    {
        return $this->typoScriptConfiguration;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * @param array $suggestions
     * @return AfterGetSuggestionsEvent
     */
    public function setSuggestions(array $suggestions): AfterGetSuggestionsEvent
    {
        $this->suggestions = $suggestions;
        return $this;
    }
}