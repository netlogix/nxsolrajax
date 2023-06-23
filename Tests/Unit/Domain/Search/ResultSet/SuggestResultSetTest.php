<?php

declare(strict_types=1);

namespace Netlogix\Nxsolrajax\Tests\Unit\Domain\Search\ResultSet;

use Netlogix\Nxsolrajax\Domain\Search\ResultSet\SuggestResultSet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SuggestResultSetTest extends UnitTestCase
{
    #[Test]
    public function itCanBeSerializedToJSON(): void
    {
        $keyword = uniqid('keyword_');

        $suggestions = [
            'keyword_0' => rand(1, 999),
            'keyword_1' => rand(1, 999),
            'keyword_2' => rand(1, 999)
        ];

        $subject = new SuggestResultSet($suggestions, $keyword);

        $jsonString = json_encode($subject);
        self::assertIsString($jsonString);

        $jsonData = json_decode($jsonString, true);
        self::assertIsArray($jsonData);

        self::assertNotEmpty($jsonData);
        self::assertCount(3, $jsonData);

        foreach ([0, 1, 2] as $itemPos) {
            self::assertEquals(
                $jsonData[$itemPos],
                ['count' => $suggestions['keyword_' . $itemPos], 'name' => 'keyword_' . $itemPos]
            );
        }
    }
}
