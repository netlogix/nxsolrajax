<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Event\Search;

use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use Netlogix\Nxsolrajax\Event\Search\AfterGetSuggestionsEvent;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AfterGetSuggestionsEventTest extends UnitTestCase
{

    /**
     * @test
     * @return void
     */
    public function itExposesQuery()
    {
        $query = uniqid('query_');

        $subject = new AfterGetSuggestionsEvent($query, [], new TypoScriptConfiguration([]));

        self::assertSame($query, $subject->getQuery());
    }

    /**
     * @test
     * @return void
     */
    public function itExposesSuggestions()
    {
        $suggestions = [uniqid('suggestion_')];

        $subject = new AfterGetSuggestionsEvent(uniqid('query_'), $suggestions, new TypoScriptConfiguration([]));

        self::assertSame($suggestions, $subject->getSuggestions());
    }

    /**
     * @test
     * @return void
     */
    public function itAllowsModificationOfSuggestions()
    {
        $suggestions = [];

        $subject = new AfterGetSuggestionsEvent(uniqid('query_'), $suggestions, new TypoScriptConfiguration([]));

        self::assertEmpty($subject->getSuggestions());

        $subject->setSuggestions([uniqid('suggestion_')]);

        self::assertNotEmpty($subject->getSuggestions());
    }

    /**
     * @test
     * @return void
     */
    public function itExposesConfiguration()
    {
        $configuration = new TypoScriptConfiguration([]);

        $subject = new AfterGetSuggestionsEvent(uniqid('query_'), [], $configuration);

        self::assertSame($configuration, $subject->getTypoScriptConfiguration());
    }

}