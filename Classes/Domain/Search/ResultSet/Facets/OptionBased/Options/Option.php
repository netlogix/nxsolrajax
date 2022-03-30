<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\SelfLinkHelperInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Option extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Options\Option implements JsonSerializable
{

    /**
     * @return string
     */
    public function getUrl()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $settings = $this->getFacet()->getConfiguration();
        if (isset($settings['linkHelper']) && is_a($settings['linkHelper'], SelfLinkHelperInterface::class, true)) {
            /** @var SelfLinkHelperInterface $linkHelper */
            $linkHelper = $objectManager->get($settings['linkHelper']);
            if ($linkHelper->canHandleSelfLink($this)) {
                return $linkHelper->renderSelfLink($this);
            }
        }

        $searchUriBuilder = $objectManager->get(SearchUriBuilder::class);
        $previousRequest = $this->getFacet()->getResultSet()->getUsedSearchRequest();

        if ($settings['keepAllOptionsOnSelection'] == 1) {
            return $searchUriBuilder->getAddFacetValueUri(
                $previousRequest,
                $this->getFacet()->getName(),
                $this->getUriValue()
            );
        } else {
            return $searchUriBuilder->getSetFacetValueUri(
                $previousRequest,
                $this->getFacet()->getName(),
                $this->getUriValue()
            );
        }
    }

    /**
     * @return string
     */
    public function getResetUrl()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $settings = $this->getFacet()->getConfiguration();
        if (isset($settings['linkHelper']) && is_a($settings['linkHelper'], SelfLinkHelperInterface::class, true)) {
            /** @var SelfLinkHelperInterface $linkHelper */
            $linkHelper = $objectManager->get($settings['linkHelper']);
            if ($linkHelper->canHandleSelfLink($this)) {
                return $linkHelper->renderSelfLink($this);
            }
        }

        $searchUriBuilder = $objectManager->get(SearchUriBuilder::class);
        $previousRequest = $this->getFacet()->getResultSet()->getUsedSearchRequest();

        if ($settings['keepAllOptionsOnSelection'] == 1) {
            return $searchUriBuilder->getRemoveFacetValueUri(
                $previousRequest,
                $this->getFacet()->getName(),
                $this->getUriValue()
            );
        } else {
            return $searchUriBuilder->getRemoveFacetUri(
                $previousRequest,
                $this->getFacet()->getName()
            );
        }
    }

    /**
     * @return array
     */
    function jsonSerialize()
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
}
