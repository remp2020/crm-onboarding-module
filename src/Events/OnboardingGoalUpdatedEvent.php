<?php

namespace Crm\OnboardingModule\Events;

use League\Event\AbstractEvent;
use Nette\Database\Table\ActiveRow;

class OnboardingGoalUpdatedEvent extends AbstractEvent
{
    private $onboardingGoal;

    /**
     * @param ActiveRow $onboardingGoal Updated onboarding goal
     */
    public function __construct(ActiveRow $onboardingGoal)
    {
        $this->onboardingGoal = $onboardingGoal;
    }

    public function getOnboardingGoal(): ActiveRow
    {
        return $this->onboardingGoal;
    }
}
