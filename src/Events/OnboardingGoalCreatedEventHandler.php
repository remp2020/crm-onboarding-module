<?php

namespace Crm\OnboardingModule\Events;

use Crm\SegmentModule\Repository\SegmentGroupsRepository;
use Crm\SegmentModule\Repository\SegmentsRepository;
use League\Event\AbstractListener;
use League\Event\EventInterface;

class OnboardingGoalCreatedEventHandler extends AbstractListener
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
        if (!$event instanceof OnboardingGoalCreatedEvent) {
            throw new \Exception('unexpected type of event, OnboardingGoalCreatedEvent expected: ' . get_class($event));
        }
        $onboardingGoal = $event->getOnboardingGoal();
        if (!$onboardingGoal) {
            throw new \Exception('OnboardingGoalCreatedEvent without onboarding goal');
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

        $this->segmentsRepository->add(
            'Onboarding: ' . $onboardingGoal->name,
            1,
            'onboarding_' . $onboardingGoal->code,
            'users',
            'users.id,users.email',
            $query,
            $group
        );
    }
}
