<?php

namespace Crm\OnboardingModule\Events;

use Crm\SegmentModule\Repository\SegmentGroupsRepository;
use Crm\SegmentModule\Repository\SegmentsRepository;
use League\Event\AbstractListener;
use League\Event\EventInterface;

class OnboardingGoalUpdatedEventHandler extends AbstractListener
{
    private $segmentGroupsRepository;

    private $segmentsRepository;

    public function __construct(
        SegmentGroupsRepository $segmentGroupsRepository,
        SegmentsRepository $segmentsRepository
    ) {
        $this->segmentGroupsRepository = $segmentGroupsRepository;
        $this->segmentsRepository = $segmentsRepository;
    }

    public function handle(EventInterface $event)
    {
        if (!$event instanceof OnboardingGoalUpdatedEvent) {
            throw new \Exception('unexpected type of event, OnboardingGoalUpdatedEvent expected: ' . get_class($event));
        }
        $onboardingGoal = $event->getOnboardingGoal();
        if (!$onboardingGoal) {
            throw new \Exception('OnboardingGoalUpdatedEvent without onboarding goal');
        }
        $group = $this->segmentGroupsRepository->findByCode('onboarding');
        if ($group === null) {
            throw new \Exception('Segments group [onboarding] does not exist. Cannot add segment.');
        }

        $query = <<<SQL
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

        $segmentCode = 'onboarding_' . $onboardingGoal->code;
        $name = 'Onboarding: ' . $onboardingGoal->name;
        $table = 'users';
        $fields = 'users.id,users.email';

        $this->segmentsRepository->upsert($segmentCode, $name, $query, $table, $fields, $group);
    }
}
