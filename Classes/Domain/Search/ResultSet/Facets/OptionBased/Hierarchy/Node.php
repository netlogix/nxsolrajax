<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy;

use ApacheSolrForTypo3\Solrfluid\Domain\Search\Uri\SearchUriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class Node extends \ApacheSolrForTypo3\Solrfluid\Domain\Search\ResultSet\Facets\OptionBased\Hierarchy\Node implements \JsonSerializable
{

    /**
     * @return string
     */
    public function getUrl()
    {
        $previousRequest = $this->getFacet()->getResultSet()->getUsedSearchRequest();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $searchUriBuilder = $objectManager->get(SearchUriBuilder::class);

        return $searchUriBuilder->getSetFacetValueUri(
            $previousRequest,
            $this->getFacet()->getName(),
            $this->getUriValue()
        );
    }

    /**
     * @return bool
     */
    public function isActive() {
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