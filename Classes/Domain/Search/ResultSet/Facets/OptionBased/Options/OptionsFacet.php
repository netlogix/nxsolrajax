<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet\Facets\OptionBased\Options;

use ApacheSolrForTypo3\Solrfluid\Domain\Search\Uri\SearchUriBuilder;
use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SearchResultSet;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class OptionsFacet extends \ApacheSolrForTypo3\Solrfluid\Domain\Search\ResultSet\Facets\OptionBased\Options\OptionsFacet implements \JsonSerializable
{

    /**
     * @var SearchUriBuilder
     */
    protected $searchUriBuilder;

    /**
     * @inheritdoc
     */
    public function __construct(SearchResultSet $resultSet, $name, $field, $label = '', array $configuration = [])
    {
        parent::__construct($resultSet, $name, $field, $label, $configuration);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->searchUriBuilder = $objectManager->get(SearchUriBuilder::class);
    }

    /**
     * @return string
     */
    public function getResetUrl()
    {
        $previousRequest = $this->getResultSet()->getUsedSearchRequest();
        return $this->searchUriBuilder->getRemoveFacetUri($previousRequest, $this->getName());
    }

    /**
     * @return array
     */
    function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'type' => self::TYPE_OPTIONS,
            'label' => $this->getLabel(),
            'used' => $this->getIsUsed(),
            'options' => $this->getOptions()->getArrayCopy(),
            'links' => [
                'reset' => $this->getResetUrl(),
            ]
        ];
    }

}
