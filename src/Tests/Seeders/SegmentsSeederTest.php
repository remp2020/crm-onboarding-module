<?php

namespace Crm\OnboardingModule\Tests\Seeders;

use Crm\OnboardingModule\Seeders\SegmentsSeeder;
use Nette\Database\Table\ActiveRow;
use PHPUnit\Framework\TestCase;

class SegmentsSeederTest extends TestCase
{
    public function testGenerateOnboardingGoalSegmentProperties()
    {
        $onboardingGoalID = 1;
        $onboardingGoalCode = 'test_segment_code';
        $onboardingGoalName = 'test_segment_name';
        $mockedSelection = $this->createMock('Nette\Database\Table\Selection');
        $onboardingGoal = new ActiveRow(
            [
                'id' => $onboardingGoalID,
                'code' => $onboardingGoalCode,
                'name' => $onboardingGoalName,
            ],
            $mockedSelection
        );

        $segmentProperties = SegmentsSeeder::generateOnboardingGoalSegmentProperties($onboardingGoal);

        $this->assertEquals('onboarding_' . $onboardingGoalCode, $segmentProperties['code']);
        $this->assertEquals('Targeting onboarding goal: ' . $onboardingGoalName, $segmentProperties['name']);
        $this->assertEquals('users', $segmentProperties['table_name']);
        $this->assertEquals('users.id,users.email', $segmentProperties['fields']);
        $this->assertEquals(1, $segmentProperties['version']);

        // check if generated segment is linked to this onboarding goal
        $this->assertStringContainsString("`onboarding_goals`.`code` = '{$onboardingGoalCode}'", $segmentProperties['query_string']);
    }
}
