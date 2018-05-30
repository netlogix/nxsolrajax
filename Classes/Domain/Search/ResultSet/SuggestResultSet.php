<?php
namespace Netlogix\Nxsolrajax\Domain\Search\ResultSet;

class SuggestResultSet implements \JsonSerializable
{

    /**
     * @var array
     */
    protected $suggestions;

    /**
     * @var string
     */
    protected $keyword;

    /**
     * @param array $suggestions
     * @param string $keyword
     */
    public function __construct($suggestions, $keyword)
    {
        $this->suggestions = $suggestions;
        $this->keyword = $keyword;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $suggestions = [];
        foreach ((array)$this->suggestions as $keywords => $value) {
            $suggestions[] = ['name' => $keywords, 'count' => $value];
        }

        return $suggestions;
    }
}