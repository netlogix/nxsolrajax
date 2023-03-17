<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\Option as SolrOption;
use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetItemUrlEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

use function strtolower;

class Option extends SolrOption implements JsonSerializable
{

    function jsonSerialize(): array
    {
        return [
            'label' => $this->getLabel(),
            'name' => $this->getValue(),
            'count' => $this->getDocumentCount(),
            'selected' => $this->getSelected(),
            'links' => [
                'self' => $this->getUrl(),
                'reset' => $this->getResetUrl(),
            ],
        ];
    }

    public function getUrl(): string
    {
        $settings = $this->getFacet()->getConfiguration();

        // this uses the same event implementation as getResetUrl() due to historical reasons
        $url = $this->sendLinkGenerationEvent();
        if ($url) {
            return $url;
        }

        $searchUriBuilder = GeneralUtility::makeInstance(ObjectManager::class)->get(SearchUriBuilder::class);
        $previousRequest = $this->getFacet()->getResultSet()->getUsedSearchRequest();

        $keepAllOptionsOnSelection = (int)$settings['keepAllOptionsOnSelection'];
        $operator = strtolower($settings['operator'] ?? '') ?: 'and';
        switch (true) {
            case ($keepAllOptionsOnSelection == 1 && $operator == 'or'):
            case ($keepAllOptionsOnSelection == 0):
                return $searchUriBuilder->getAddFacetValueUri(
                    $previousRequest,
                    $this->getFacet()->getName(),
                    $this->getUriValue()
                );
            case ($keepAllOptionsOnSelection == 1 && $operator == 'and'):
                return $searchUriBuilder->getSetFacetValueUri(
                    $previousRequest,
                    $this->getFacet()->getName(),
                    $this->getUriValue()
                );
        }

        return '';
    }

    private function sendLinkGenerationEvent(): string
    {
        // link generation works slightly differently here compared to FacetUrlTrait
        // the event is duplicated here to get a consistent external event interface
        $event = new GenerateFacetItemUrlEvent($this, '');

        /** @var GenerateFacetItemUrlEvent $event */
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);

        return $event->getUrl();
    }

    public function getResetUrl(): string
    {
        $settings = $this->getFacet()->getConfiguration();

        // this uses the same event implementation as getUrl() due to historical reasons
        $url = $this->sendLinkGenerationEvent();
        if ($url) {
            return $url;
        }

        $searchUriBuilder = GeneralUtility::makeInstance(ObjectManager::class)->get(SearchUriBuilder::class);
        $previousRequest = $this->getFacet()->getResultSet()->getUsedSearchRequest();

        $keepAllOptionsOnSelection = (int)$settings['keepAllOptionsOnSelection'];
        $operator = strtolower($settings['operator'] ?? '') ?: 'and';
        switch (true) {
            case ($keepAllOptionsOnSelection == 1 && $operator == 'or'):
            case ($keepAllOptionsOnSelection == 0):
                return $searchUriBuilder->getRemoveFacetValueUri(
                    $previousRequest,
                    $this->getFacet()->getName(),
                    $this->getUriValue()
                );
            case ($keepAllOptionsOnSelection == 1 && $operator == 'and'):
                return $searchUriBuilder->getRemoveFacetUri(
                    $previousRequest,
                    $this->getFacet()->getName()
                );
        }

        return '';
    }
}
