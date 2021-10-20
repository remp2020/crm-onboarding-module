<?php

namespace Crm\OnboardingModule\Events;

use League\Event\AbstractEvent;
use Nette\Database\Table\ActiveRow;

class OnboardingGoalCreatedEvent extends AbstractEvent
{
    private $onboardingGoal;

    public function __construct(ActiveRow $onboardingGoal)
    {
        $this->onboardingGoal = $onboardingGoal;
    }

    public function getOnboardingGoal(): ActiveRow
    {
        return $this->onboardingGoal;
    }
}
