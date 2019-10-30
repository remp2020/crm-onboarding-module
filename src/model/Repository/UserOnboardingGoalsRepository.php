<?php

namespace Crm\OnboardingModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\IRow;
use Nette\Utils\DateTime;

class UserOnboardingGoalsRepository extends Repository
{
    protected $tableName = 'user_onboarding_goals';

    public function add($userId, $onboardingGoalId, bool $done = false)
    {
        $data = [
            'user_id' => $userId,
            'onboarding_goal_id' => $onboardingGoalId,
            'done' => $done,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ];
        return $this->insert($data);
    }

    public function complete($userId, $onboardingGoalId)
    {
        $goal = $this->getTable()->where([
            'user_id' => $userId,
            'onboarding_goal_id' => $onboardingGoalId
        ])->fetch();

        if (!$goal) {
            return $this->add($userId, $onboardingGoalId, true);
        }

        return $this->update($goal, ['done' => true]);
    }

    public function update(IRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }
}
