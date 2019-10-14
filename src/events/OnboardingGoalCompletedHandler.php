<?php

namespace Crm\OnboardingModule\Events;

use Crm\OnboardingModule\Repository\UserOnboardingGoalsRepository;
use League\Event\AbstractListener;
use League\Event\EventInterface;

class OnboardingGoalCompletedHandler extends AbstractListener
{
    private $userOnboardingGoalsRepository;

    public function __construct(
        UserOnboardingGoalsRepository $userOnboardingGoalsRepository
    ) {
        $this->userOnboardingGoalsRepository = $userOnboardingGoalsRepository;
    }

    public function handle(EventInterface $event)
    {
        if (!($event instanceof OnboardingGoalCompletedEvent)) {
            throw new \Exception('Incorrect event type received, got ' . get_class($event) . ' instead of expected OnboardingGoalCompletedEvent');
        }

        $goal = $event->getOnboardingGoal();
        $user = $event->getUser();

        $this->userOnboardingGoalsRepository->complete($user->id, $goal->id);
    }
}
