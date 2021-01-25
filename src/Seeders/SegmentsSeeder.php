<?php

namespace Crm\OnboardingModule\Seeders;

use Crm\ApplicationModule\Seeders\ISeeder;
use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Crm\SegmentModule\Repository\SegmentGroupsRepository;
use Crm\SegmentModule\Repository\SegmentsRepository;
use Crm\SegmentModule\Seeders\SegmentsTrait;
use Nette\Database\Table\ActiveRow;
use Symfony\Component\Console\Output\OutputInterface;

class SegmentsSeeder implements ISeeder
{
    use SegmentsTrait;

    private $onboardingGoalsRepository;

    private $segmentGroupsRepository;

    private $segmentsRepository;

    public function __construct(
        OnboardingGoalsRepository $onboardingGoalsRepository,
        SegmentGroupsRepository $segmentGroupsRepository,
        SegmentsRepository $segmentsRepository
    ) {
        $this->onboardingGoalsRepository = $onboardingGoalsRepository;
        $this->segmentGroupsRepository = $segmentGroupsRepository;
        $this->segmentsRepository = $segmentsRepository;
    }

    public function seed(OutputInterface $output)
    {
        $onboardingSegmentsGroup = $this->seedSegmentGroup(
            $output,
            'Onboarding',
            'onboarding',
            1300
        );

        foreach ($this->onboardingGoalsRepository->all() as $onboardingGoal) {
            $segmentCode = 'onboarding_' . $onboardingGoal->code;
            $segmentName = 'Onboarding: ' . $onboardingGoal->name;
            $segmentQuery = $this->generateGoalSegmentQuery($onboardingGoal);

            $this->seedOrUpdateSegment(
                $output,
                $segmentName,
                $segmentCode,
                $segmentQuery,
                $onboardingSegmentsGroup
            );
        }
    }

    public function generateGoalSegmentQuery(ActiveRow $onboardingGoal): string
    {
        return <<<SQL
SELECT %fields% FROM %table%
INNER JOIN `user_onboarding_goals`
    ON `user_onboarding_goals`.`user_id`=%table%.`id`
WHERE
    %where%
    AND %table%.`active` = 1
    AND `user_onboarding_goals`.`onboarding_goal_id` = {$onboardingGoal->id}
    AND `user_onboarding_goals`.`completed_at` IS NULL
GROUP BY %table%.`id`
SQL;
    }
}
