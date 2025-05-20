<?php

namespace Crm\OnboardingModule\Events;

use Nette\Database\Table\ActiveRow;

trait UserOnboardingGoalEventTrait
{
    private $userOnboardingGoal;

    public function __construct(
        ActiveRow $userOnboardingGoal,
    ) {
        $this->userOnboardingGoal = $userOnboardingGoal;
    }

    public function getUserOnboardingGoal(): ActiveRow
    {
        return $this->userOnboardingGoal;
    }
}
