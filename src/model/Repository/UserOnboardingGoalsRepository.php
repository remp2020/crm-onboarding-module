<?php

namespace Crm\OnboardingModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Nette\Utils\DateTime;

class UserOnboardingGoalsRepository extends Repository
{
    protected $tableName = 'user_onboarding_goals';

    public function all($userId = null, ?bool $done = null): Selection
    {
        $where = [];

        if ($userId) {
            $where['user_id'] = $userId;
        }
        if ($done !== null) {
            $where['done'] = $done;
        }

        return $this->getTable()
            ->where($where)
            ->order('created_at DESC');
    }

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

    public function completedGoalsCountSince(\DateTime $from): array
    {
        $q = $this->getTable()
        ->group('onboarding_goal_id')
        ->where('created_at >= ?', $from)
        ->select('COUNT(*) AS total, onboarding_goal_id');

        $goalCounts = [];
        foreach ($q as $row) {
            $goalCounts[$row->onboarding_goal_id] = $row->total;
        }
        return $goalCounts;
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
