<?php

namespace Crm\OnboardingModule\Events;

use Crm\OnboardingModule\Seeders\SegmentsSeeder;
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
        $group = $this->segmentGroupsRepository->findByCode(SegmentsSeeder::ONBOARDING_GOAL_GROUP_CODE);
        if ($group === null) {
            throw new \Exception('Segments group [onboarding] does not exist. Cannot add segment.');
        }

        $segmentProperties = SegmentsSeeder::generateOnboardingGoalSegmentProperties($onboardingGoal);

        $this->segmentsRepository->upsert(
            $segmentProperties['code'],
            $segmentProperties['name'],
            $segmentProperties['query_string'],
            $segmentProperties['table_name'],
            $segmentProperties['fields'],
            $group
        );
    }
}
