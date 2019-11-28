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
        return $this->getTable()
        ->group('onboarding_goal_id')
        ->where('created_at >= ?', $from)
        ->select('COUNT(*) AS total, onboarding_goal_id')
        ->fetchPairs('onboarding_goal_id', 'total');
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

    /**
     * Returns distribution between 'days_from_registration_range' and 'had_subscription' for users who completed given goal
     *
     * Columns in results:
     * 'days_from_registration_range' specifies how long it took user to finish goal after his/her registration.
     * 'had_subscription' specifies if user had subscription when goal was completed.
     * 'total' specifies how many users belong to that particular group.
     *
     * @param $onboardingGoalId int|string Distribution is computed for given onboarding goal ID.
     *
     * @return array|\Nette\Database\IRow[]
     */
    public function userRegistrationAndSubscriptionOwnershipDistributionForGoal($onboardingGoalId)
    {
        $sql=<<<SQL
SELECT 
CASE 
  WHEN days_from_registration < 1 THEN "0"
  WHEN days_from_registration = 1 THEN "1"
  WHEN days_from_registration = 2 THEN "2"
  WHEN days_from_registration < 7 THEN "3-6"
  WHEN days_from_registration < 31 THEN "7-30"
  ELSE "31+"
END AS days_from_registration_range, tt. had_subscription, SUM(tt.total) AS total 
FROM 
    (SELECT TIMESTAMPDIFF(DAY, users.created_at, t.completed_at) days_from_registration, t.had_subscription, count(*) AS total 
    FROM users 
    JOIN 
        (SELECT uog.user_id, uog.created_at AS completed_at, CASE WHEN COUNT(s.id) > 0 THEN 1 ELSE 0 END AS had_subscription 
        FROM user_onboarding_goals uog 
        LEFT JOIN subscriptions s 
        ON (uog.user_id = s.user_id AND uog.created_at >= s.start_time AND uog.created_at <= end_time)
        WHERE uog.onboarding_goal_id = ?
        GROUP BY uog.user_id, completed_at) t 
    ON users.id = t.user_id
    GROUP BY days_from_registration, had_subscription) tt
GROUP BY days_from_registration_range, had_subscription
SQL;
        return $this->getDatabase()->query($sql, $onboardingGoalId)->fetchAll();
    }
}
