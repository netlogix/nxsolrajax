<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\Option as SolrOption;
use JsonSerializable;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetItemUrlEvent;
use Netlogix\Nxsolrajax\Event\Url\GenerateFacetResetUrlEvent;
use Netlogix\Nxsolrajax\Traits\SearchUriBuilderTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function strtolower;

class Option extends SolrOption implements JsonSerializable
{
    use SearchUriBuilderTrait;

    public function jsonSerialize(): array
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
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new GenerateFacetItemUrlEvent($this, ''),
        );

        $url = $event->getUrl();
        if ($url !== '') {
            return $url;
        }

        $previousRequest = $this->getFacet()->getResultSet()->getUsedSearchRequest();

        $settings = $this->getFacet()->getConfiguration();
        $keepAllOptionsOnSelection = (int) ($settings['keepAllOptionsOnSelection'] ?? 0);
        $operator = in_array(strtolower($settings['operator'] ?? ''), ['', '0'], true)
            ? 'and'
            : strtolower($settings['operator'] ?? '');
        return match (true) {
            $keepAllOptionsOnSelection === 1 && $operator === 'or',
            $keepAllOptionsOnSelection === 0
                => $this->searchUriBuilder->getAddFacetValueUri(
                $previousRequest,
                $this->getFacet()->getName(),
                $this->getUriValue(),
            ),
            $keepAllOptionsOnSelection === 1 && $operator === 'and'
                => $this->searchUriBuilder->getSetFacetValueUri(
                $previousRequest,
                $this->getFacet()->getName(),
                $this->getUriValue(),
            ),
            default => '',
        };
    }

    public function getResetUrl(): string
    {
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new GenerateFacetResetUrlEvent($this->facet, ''),
        );

        $url = $event->getUrl();
        if ($url !== '') {
            return $url;
        }

        $previousRequest = $this->getFacet()->getResultSet()->getUsedSearchRequest();

        $settings = $this->getFacet()->getConfiguration();
        $keepAllOptionsOnSelection = (int) ($settings['keepAllOptionsOnSelection'] ?? 0);
        $operator = in_array(strtolower($settings['operator'] ?? ''), ['', '0'], true)
            ? 'and'
            : strtolower($settings['operator'] ?? '');
        return match (true) {
            $keepAllOptionsOnSelection === 1 && $operator === 'or',
            $keepAllOptionsOnSelection === 0
                => $this->searchUriBuilder->getRemoveFacetValueUri(
                $previousRequest,
                $this->getFacet()->getName(),
                $this->getUriValue(),
            ),
            $keepAllOptionsOnSelection === 1 && $operator === 'and' => $this->searchUriBuilder->getRemoveFacetUri(
                $previousRequest,
                $this->getFacet()->getName(),
            ),
            default => '',
        };
    }
}
