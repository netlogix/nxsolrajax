<?php

namespace Netlogix\Nxsolrajax;

/*
 * This file is part of the Netlogix.Nxsolrajax package.
 */

use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;

/**
 * @deprecated this is deprecated in favour of AfterGetSuggestionsEvent
 */
interface SugesstionResultModifier
{

    /**
     * @param string $query
     * @param array $suggestions
     * @param TypoScriptConfiguration $typoScriptConfiguration
     * @return array
     * @deprecated this is deprecated in favour of AfterGetSuggestionsEvent
     */
    public function modifySuggestions(
        string $query,
        array $suggestions,
        TypoScriptConfiguration $typoScriptConfiguration
    ): array;
}
