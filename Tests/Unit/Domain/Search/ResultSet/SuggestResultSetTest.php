<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet;

use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SuggestResultSet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SuggestResultSetTest extends UnitTestCase
{
    #[Test]
    public function itCanBeSerializedToJSON(): void
    {
        $keyword = uniqid('keyword_');

        $suggestions = [
            'keyword_0' => random_int(1, 999),
            'keyword_1' => random_int(1, 999),
            'keyword_2' => random_int(1, 999),
        ];

        $subject = new SuggestResultSet($suggestions, $keyword);

        $jsonString = json_encode($subject);
        $this->assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        $this->assertIsArray($jsonData);

        $this->assertNotEmpty($jsonData);
        $this->assertCount(3, $jsonData);

        foreach ([0, 1, 2] as $itemPos) {
            $this->assertEquals($jsonData[$itemPos], [
                'count' => $suggestions['keyword_' . $itemPos],
                'name' => 'keyword_' . $itemPos,
            ]);
        }
    }
}
