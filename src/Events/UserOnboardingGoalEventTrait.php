<?php

namespace Crm\OnboardingModule\Events;

use Nette\Database\Table\IRow;

trait UserOnboardingGoalEventTrait
{
    private $userOnboardingGoal;

    public function __construct(
        IRow $userOnboardingGoal
    ) {
        $this->userOnboardingGoal = $userOnboardingGoal;
    }

    public function getUserOnboardingGoal(): IRow
    {
        return $this->userOnboardingGoal;
    }
}
