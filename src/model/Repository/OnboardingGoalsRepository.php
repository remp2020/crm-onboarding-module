<?php

namespace Crm\OnboardingModule\Repository;

use Crm\ApplicationModule\Repository;

class OnboardingGoalsRepository extends Repository
{
    protected $tableName = 'onboarding_goals';

    public function all()
    {
        return $this->getTable()->order('created_at DESC');
    }
}
