<?php

namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solr\Domain\Search\Uri\SearchUriBuilder;
use JsonSerializable;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\LinkHelper\SelfLinkHelperInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Node extends \ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node implements JsonSerializable
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
        return $searchUriBuilder->getSetFacetValueUri(
            $previousRequest,
            $this->getFacet()->getName(),
            $this->getUriValue()
        );
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        if ($this->getSelected()) {
            return true;
        }
        /** @var Node $childNode */
        foreach ($this->childNodes as $childNode) {
            if ($childNode->isActive()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return [
            'id' => $this->getKey(),
            'label' => $this->getLabel(),
            'name' => $this->getValue(),
            'count' => $this->getDocumentCount(),
            'selected' => $this->getSelected(),
            'active' => $this->isActive(),
            'options' => array_values($this->childNodes->getArrayCopy()),
            'links' => [
                'self' => $this->getUrl(),
            ]
        ];
    }

}
