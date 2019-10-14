<?php

namespace Crm\OnboardingModule\Events;

use League\Event\AbstractEvent;
use Nette\Database\Table\ActiveRow;

class OnboardingGoalCompletedEvent extends AbstractEvent
{
    private $user;

    private $onboardingGoal;

    public function __construct(ActiveRow $user, ActiveRow $onboardingGoal)
    {
        $this->user = $user;
        $this->onboardingGoal = $onboardingGoal;
    }

    public function getUser(): ActiveRow
    {
        return $this->user;
    }

    public function getOnboardingGoal(): ActiveRow
    {
        return $this->onboardingGoal;
    }
}
