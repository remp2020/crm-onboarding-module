<?php

namespace Crm\OnboardingModule\Events;

use Crm\OnboardingModule\Seeders\SegmentsSeeder;
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

        $group = $this->segmentGroupsRepository->findByCode(SegmentsSeeder::ONBOARDING_GOAL_GROUP_CODE);
        if ($group === null) {
            throw new \Exception('Segments group [onboarding] does not exist. Cannot add segment.');
        }

        $segmentProperties = SegmentsSeeder::generateOnboardingGoalSegmentProperties($onboardingGoal);

        $this->segmentsRepository->add(
            $segmentProperties['name'],
            $segmentProperties['version'],
            $segmentProperties['code'],
            $segmentProperties['table_name'],
            $segmentProperties['fields'],
            $segmentProperties['query_string'],
            $group
        );
    }
}
