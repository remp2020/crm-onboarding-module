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

    public const ONBOARDING_GOAL_GROUP_CODE = 'onboarding';

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
            self::ONBOARDING_GOAL_GROUP_CODE,
            1300
        );

        foreach ($this->onboardingGoalsRepository->all() as $onboardingGoal) {
            $segmentProperties = self::generateOnboardingGoalSegmentProperties($onboardingGoal);
            $segment = $this->segmentsRepository->findByCode($segmentProperties['code']);

            // if segment exists, we need to unlock it before changes can be saved
            if ($segment) {
                $this->segmentsRepository->setLock($segment, false);
            }
            $segment = $this->seedOrUpdateSegment(
                $output,
                $segmentProperties['name'],
                $segmentProperties['code'],
                $segmentProperties['query_string'],
                $onboardingSegmentsGroup
            );
            $this->segmentsRepository->setLock($segment, true);
        }
    }

    /**
     * @param ActiveRow $onboardingGoal
     * @return array Returns array with Segment properties based on provided $onboardingGoal
     *
     * Returned array:
     *   [
     *      'code' => string,
     *      'name' => string,
     *      'query_string' => string,
     *      'table_name' => string,
     *      'fields' => string,
     *      'version' => int,
     *      'locked' => boolean,
     *   ]
     *
     * @throws \Exception If onboarding goal is missing.
     */
    final public static function generateOnboardingGoalSegmentProperties(ActiveRow $onboardingGoal): array
    {
        if (!isset($onboardingGoal->code) || empty(trim($onboardingGoal->code))) {
            $code = $onboardingGoal->code ?? '';
            throw new \Exception("Invalid code [{$code}] of onboarding goal with ID [{$onboardingGoal->id}].");
        }

        $query = <<<SQL
SELECT %fields% FROM %table%
INNER JOIN `user_onboarding_goals`
    ON `user_onboarding_goals`.`user_id`=%table%.`id`
INNER JOIN `onboarding_goals`
    ON `user_onboarding_goals`.`onboarding_goal_id` = `onboarding_goals`.`id`
WHERE
    %where%
    AND %table%.`active` = 1
    AND `onboarding_goals`.`code` = '{$onboardingGoal->code}'
    AND `user_onboarding_goals`.`completed_at` IS NULL
    AND `user_onboarding_goals`.`timedout_at` IS NULL
GROUP BY %table%.`id`
SQL;

        return [
            'code' => 'onboarding_' . $onboardingGoal->code,
            'name' => 'Targeting onboarding goal: ' . $onboardingGoal->name,
            'query_string' => $query,
            'table_name' => 'users',
            'fields' => 'users.id,users.email',
            'version' => 1,
        ];
    }
}
