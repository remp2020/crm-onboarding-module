<?php

namespace Crm\OnboardingModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Nette\Utils\DateTime;

class UserOnboardingGoalsRepository extends Repository
{
    protected $tableName = 'user_onboarding_goals';

    final public function all($userId = null, bool $onlyCompleted = false): Selection
    {
        $where = [];

        if ($userId) {
            $where['user_id'] = $userId;
        }

        $q = $this->getTable()
            ->where($where)
            ->order('created_at DESC');

        if ($onlyCompleted) {
            $q->where('completed_at IS NOT NULL');
        }

        return $q;
    }

    final public function add($userId, $onboardingGoalId, ?DateTime $completedAt = null, ?DateTime $timedoutAt = null)
    {
        $data = [
            'user_id' => $userId,
            'onboarding_goal_id' => $onboardingGoalId,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ];

        if ($completedAt) {
            $data['completed_at'] = $completedAt;
        }

        if ($timedoutAt) {
            $data['timedout_at'] = $timedoutAt;
        }

        return $this->insert($data);
    }

    final public function complete($userId, $onboardingGoalId, $completedAt = null)
    {
        $goal = $this->getTable()->where([
            'user_id' => $userId,
            'onboarding_goal_id' => $onboardingGoalId,
        ])->fetch();

        if (!$completedAt) {
            $completedAt = new DateTime();
        }

        if (!$goal) {
            return $this->add($userId, $onboardingGoalId, $completedAt);
        }

        // do not complete timed out goal
        if ($goal->timedout_at !== null) {
            return false;
        }

        return $this->update($goal, ['completed_at' => $completedAt]);
    }

    final public function timeout($userId, $onboardingGoalId, $timedoutAt = null)
    {
        $goal = $this->getTable()->where([
            'user_id' => $userId,
            'onboarding_goal_id' => $onboardingGoalId,
        ])->fetch();

        if (!$timedoutAt) {
            $timedoutAt = new DateTime();
        }

        if (!$goal) {
            return $this->add($userId, $onboardingGoalId, null, $timedoutAt);
        }

        // do not timeout completed goal
        if ($goal->completed_at !== null) {
            return false;
        }

        return $this->update($goal, ['timedout_at' => $timedoutAt]);
    }

    final public function userCompletedGoals($userId, array $onboardingGoalIds): Selection
    {
        return $this->getTable()
            ->where(['user_id' => $userId])
            ->where('completed_at IS NOT NULL')
            ->where('onboarding_goal_id IN (?)', $onboardingGoalIds);
    }

    final public function completedGoalsCountSince(\DateTime $from): array
    {
        return $this->getTable()
            ->select('COUNT(*) AS total, onboarding_goal_id')
            ->where('created_at >= ?', $from)
            ->where('completed_at IS NOT NULL')
            ->group('onboarding_goal_id')
            ->fetchPairs('onboarding_goal_id', 'total');
    }

    final public function update(IRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    /**
     * Returns distribution between 'days_from_registration_range' and 'had_subscription' for users who completed the given goal
     *
     * Columns in results:
     * 'days_from_registration_range' specifies how long it took user to finish goal after his/her registration.
     * 'had_subscription' specifies if user had subscription when goal was completed.
     * 'total' specifies how many users belong to that particular group.
     *
     * @param $onboardingGoalId int|string Distribution is computed for given onboarding goal ID.
     *
     * @return array|\Nette\Database\IRow[]|\Nette\Database\ResultSet
     */
    final public function userRegistrationAndSubscriptionOwnershipDistributionForGoal($onboardingGoalId)
    {
        $sql=<<<SQL
SELECT CASE
  WHEN days_from_registration < 1 THEN '0'
  WHEN days_from_registration = 1 THEN '1'
  WHEN days_from_registration = 2 THEN '2'
  WHEN days_from_registration < 7 THEN '3-6'
  WHEN days_from_registration < 31 THEN '7-30'
  ELSE '31+'
END AS days_from_registration_range, tt. had_subscription, SUM(tt.total) AS total
FROM
    (SELECT TIMESTAMPDIFF(DAY, users.created_at, t.completed_at) days_from_registration, t.had_subscription, count(*) AS total
    FROM users
    JOIN
        (SELECT uog.user_id, uog.completed_at, CASE WHEN COUNT(s.id) > 0 THEN 1 ELSE 0 END AS had_subscription
        FROM user_onboarding_goals uog
        LEFT JOIN subscriptions s
        ON (uog.user_id = s.user_id AND uog.completed_at >= s.start_time AND uog.completed_at <= end_time)
        WHERE uog.onboarding_goal_id = ? and uog.completed_at IS NOT NULL
        GROUP BY uog.user_id, uog.completed_at) t
    ON users.id = t.user_id
    GROUP BY days_from_registration, had_subscription) tt
GROUP BY days_from_registration_range, had_subscription
SQL;
        return $this->getDatabase()->query($sql, $onboardingGoalId)->fetchAll();
    }

    /**
     * Returns distribution of 'first_payment_in_days_range' (after goal completion) for non-subscribers who completed the given goal
     *
     * Columns in results:
     * 'first_payment_in_days_range' specifies how long it took user to make first payment after goal was finished
     * 'total' specifies how many users belong to that particular group.
     *
     * @param $onboardingGoalId int|string Distribution is computed for given onboarding goal ID.
     *
     * @return array|\Nette\Database\IRow[]|\Nette\Database\ResultSet
     */
    final public function nonSubscribersAndFirstFollowingPaymentInDaysDistributionForGoal($onboardingGoalId)
    {
        $sql=<<<SQL
SELECT CASE
  WHEN first_payment_in_days IS NULL THEN '-'
  WHEN first_payment_in_days < 1 THEN '0'
  WHEN first_payment_in_days = 1 THEN '1'
  WHEN first_payment_in_days = 2 THEN '2'
  WHEN first_payment_in_days < 7 THEN '3-6'
  WHEN first_payment_in_days < 31 THEN '7-30'
  ELSE '31+'
END AS first_payment_in_days_range, COUNT(user_id) AS total FROM
    (SELECT t.user_id, MIN(TIMESTAMPDIFF(DAY, t.completed_at, p.paid_at)) AS first_payment_in_days FROM
        (SELECT uog.user_id, uog.completed_at
        FROM user_onboarding_goals uog
        LEFT JOIN subscriptions s
        ON (uog.user_id = s.user_id AND uog.completed_at >= s.start_time AND uog.completed_at <= end_time)
        WHERE uog.onboarding_goal_id = ? and uog.completed_at IS NOT NULL
        GROUP BY uog.user_id, uog.completed_at
        HAVING COUNT(s.id)=0) t
    LEFT JOIN payments p ON t.user_id = p.user_id
    AND p.subscription_id IS NOT NULL
    AND p.paid_at > t.completed_at
    GROUP BY t.user_id) tt
GROUP BY first_payment_in_days_range
SQL;
        return $this->getDatabase()->query($sql, $onboardingGoalId)->fetchAll();
    }
}
