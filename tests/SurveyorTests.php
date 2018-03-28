<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
namespace Livy\Climber;

use \PHPUnit\Framework\TestCase;

// phpcs:enable

class SurveyorTest extends TestCase
{
    const ROUTES = [
        ['/(?:stories\/bedtime\/[\w_-]*)/', 'https://example.com/stories/bedtime/'],
        ['/(?:stories(?:|\/[\w_-]*))/', 'https://example.com/stories/'],
    ];

    public function testMatching()
    {
        $Surveyor = new Surveyor($this::ROUTES);
        $this->assertEquals(
            'https://example.com/stories/bedtime/',
            $Surveyor->evaluateUrl('https://example.com/stories/bedtime/goodnight-moon'),
            'Did not match child correctly.'
        );
        $this->assertEquals(
            'https://example.com/stories/',
            $Surveyor->evaluateUrl('https://example.com/stories/library'),
            'Did not match alternate child correctly.'
        );
    }

    public function testNotMatching()
    {
        $Surveyor = new Surveyor($this::ROUTES);
        $this->assertNull(
            $Surveyor->evaluateUrl('https://example.com/'),
            'Incorrectly matching base URL.'
        );
        $this->assertNull(
            $Surveyor->evaluateUrl('https://example.com/storie'),
            'Incorrectly matching partial URL.'
        );
    }
}