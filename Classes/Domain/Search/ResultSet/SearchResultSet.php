<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet as SolrSearchResult;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Spellchecking\Suggestion;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;
use Netlogix\Nxsolrajax\Service\SearchResultSetConverterService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class SearchResultSet extends SolrSearchResult implements JsonSerializable
{
    protected SearchUriBuilder $searchUriBuilder;

    protected UriBuilder $uriBuilder;

    protected bool $forceAddFacetData = false;

    protected ?RequestInterface $request = null;

    public function __construct()
    {
        parent::__construct();

        $this->searchUriBuilder = GeneralUtility::makeInstance(SearchUriBuilder::class);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    }

    public function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
        $this->uriBuilder->setRequest($request);
        $this->searchUriBuilder->injectUriBuilder($this->uriBuilder);
    }

    public function jsonSerialize(): array
    {
        return GeneralUtility::makeInstance(SearchResultSetConverterService::class)
            ->setSearchUriBuilder($this->searchUriBuilder)
            ->toArray($this);
    }

    public function getSuggestion(): string
    {
        if (!$this->getHasSpellCheckingSuggestions()) {
            return '';
        }

        return current($this->spellCheckingSuggestions)->getSuggestion();
    }

    public function getResetUrl(): string
    {
        return $this->uriBuilder->reset()->build();
    }

    public function getSearchUrl(): string
    {
        $previousRequest = $this->getUsedSearchRequest();
        return $this->searchUriBuilder->getNewSearchUri($previousRequest, '{query}');
    }

    public function getSuggestUrl(): string
    {
        return $this->uriBuilder->reset()->setTargetPageType(1471261352)->build();
    }

    public function getSuggestionUrl(): string
    {
        if (!$this->getHasSpellCheckingSuggestions()) {
            return '';
        }

        /** @var Suggestion $suggestion */
        $suggestion = current($this->spellCheckingSuggestions)->getSuggestion();
        $previousRequest = $this->getUsedSearchRequest();
        return $this->searchUriBuilder->getNewSearchUri($previousRequest, $suggestion);
    }

    public function getFirstUrl(): string
    {
        $previousRequest = $this->getUsedSearchRequest();
        $page = $this->getPage();
        if ($page > 2) {
            return $this->searchUriBuilder->getResultPageUri($previousRequest, 1);
        }

        return '';
    }

    protected function getPage(): int
    {
        $page = $this->getUsedSearchRequest()->getPage();
        return in_array($page, [null, 0], true) ? 1 : $page;
    }

    public function getPrevUrl(): string
    {
        $previousRequest = $this->getUsedSearchRequest();
        $page = $this->getPage();
        if ($page > 1) {
            return $this->searchUriBuilder->getResultPageUri($previousRequest, $page - 1);
        }

        return '';
    }

    public function getNextUrl(): string
    {
        $previousRequest = $this->getUsedSearchRequest();
        $page = $this->getPage();
        $resultsPerPage = $this->getUsedResultsPerPage();
        $resultOffset = $this->getUsedSearch()->getResultOffset();
        $numberOfResults = $this->getAllResultCount();

        if ($numberOfResults - $resultsPerPage > $resultOffset) {
            return $this->searchUriBuilder->getResultPageUri($previousRequest, $page + 1);
        }

        return '';
    }

    public function getLastUrl(): string
    {
        $previousRequest = $this->getUsedSearchRequest();
        $resultsPerPage = $this->getUsedResultsPerPage();
        $resultOffset = $this->getUsedSearch()->getResultOffset();
        $numberOfResults = $this->getAllResultCount();

        if ($numberOfResults - 2 * $resultsPerPage > $resultOffset) {
            return $this->searchUriBuilder->getResultPageUri(
                $previousRequest,
                (int) ceil($numberOfResults / $resultsPerPage),
            );
        }

        return '';
    }

    public function shouldAddFacetData(): bool
    {
        return $this->forceAddFacetData || $this->getPage() === 1;
    }

    public function isGroupingEnabled(): bool
    {
        return $this->searchResults->getHasGroups();
    }

    public function forceAddFacetData(bool $forceAddFacetData = true): void
    {
        $this->forceAddFacetData = $forceAddFacetData;
    }
}
