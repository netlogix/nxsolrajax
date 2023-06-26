<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Event\Search;

use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use Netlogix\Nxsolrajax\Event\Search\AfterGetSuggestionsEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AfterGetSuggestionsEventTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function itExposesQuery(): void
    {
        $query = uniqid('query_');

        $subject = new AfterGetSuggestionsEvent($query, [], new TypoScriptConfiguration([]));

        self::assertSame($query, $subject->query);
    }

    #[Test]
    public function itExposesSuggestions(): void
    {
        $suggestions = [uniqid('suggestion_')];

        $subject = new AfterGetSuggestionsEvent(uniqid('query_'), $suggestions, new TypoScriptConfiguration([]));

        self::assertSame($suggestions, $subject->getSuggestions());
    }

    #[Test]
    public function itAllowsModificationOfSuggestions(): void
    {
        $suggestions = [];

        $subject = new AfterGetSuggestionsEvent(uniqid('query_'), $suggestions, new TypoScriptConfiguration([]));

        self::assertEmpty($subject->getSuggestions());

        $subject->setSuggestions([uniqid('suggestion_')]);

        self::assertNotEmpty($subject->getSuggestions());
    }

    #[Test]
    public function itExposesConfiguration(): void
    {
        $configuration = new TypoScriptConfiguration([]);

        $subject = new AfterGetSuggestionsEvent(uniqid('query_'), [], $configuration);

        self::assertSame($configuration, $subject->typoScriptConfiguration);
    }

}
