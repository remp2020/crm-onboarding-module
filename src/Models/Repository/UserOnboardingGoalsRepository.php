<?php

namespace Crm\OnboardingModule\Repository;

use Crm\ApplicationModule\Repository;
use Crm\OnboardingModule\Events\UserOnboardingGoalCompletedEvent;
use Crm\OnboardingModule\Events\UserOnboardingGoalCreatedEvent;
use Crm\OnboardingModule\Events\UserOnboardingGoalTimedoutEvent;
use League\Event\Emitter;
use Nette\Database\Explorer;
use Nette\Database\ResultSet;
use Nette\Database\Row;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\DateTime;

class UserOnboardingGoalsRepository extends Repository
{
    protected $tableName = 'user_onboarding_goals';

    private $emitter;

    public function __construct(
        Explorer $database,
        Emitter $emitter
    ) {
        parent::__construct($database);
        $this->emitter = $emitter;
    }

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

    final public function add(int $userId, int $onboardingGoalId, ?DateTime $completedAt = null, ?DateTime $timedoutAt = null)
    {
        // do not allow to create more than one not-completed / not-timed-out entries
        $userOnboardingGoal = $this->userActiveOnboardingGoal($userId, $onboardingGoalId);
        if ($userOnboardingGoal === null) {
            // no active entry; insert
            $data = [
                'user_id' => $userId,
                'onboarding_goal_id' => $onboardingGoalId,
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
            ];

            $userOnboardingGoal = $this->insert($data);
            $this->emitter->emit(new UserOnboardingGoalCreatedEvent($userOnboardingGoal));
        }

        $data = [];
        if ($completedAt) {
            $data['completed_at'] = $completedAt;
        }
        if ($timedoutAt) {
            $data['timedout_at'] = $timedoutAt;
        }
        // update also in case completed & timedout are empty; we want to "touch" updated_at field
        $this->update($userOnboardingGoal, $data);

        return $userOnboardingGoal;
    }

    final public function complete($userId, $onboardingGoalId, $completedAt = null)
    {
        if (!$completedAt) {
            $completedAt = new DateTime();
        }

        $userOnboardingGoal = $this->userLastGoal($userId, $onboardingGoalId);

        // no goal or last goal is timed out; create new completed
        if ($userOnboardingGoal === null || $userOnboardingGoal->timedout_at !== null) {
            return $this->add($userId, $onboardingGoalId, $completedAt);
        }

        // if last goal is active (not completed / not timed out) or completed; update completed_at
        return $this->update($userOnboardingGoal, ['completed_at' => $completedAt]);
    }

    final public function timeout($userId, $onboardingGoalId, $timedoutAt = null)
    {
        if (!$timedoutAt) {
            $timedoutAt = new DateTime();
        }

        $userOnboardingGoal = $this->userLastGoal($userId, $onboardingGoalId);

        // no goal or last goal is completed; create new timed out
        if ($userOnboardingGoal === null || $userOnboardingGoal->completed_at !== null) {
            return $this->add($userId, $onboardingGoalId, null, $timedoutAt);
        }

        // if last goal is active (not completed / not timed out) or timed out; update timedout_at
        return $this->update($userOnboardingGoal, ['timedout_at' => $timedoutAt]);
    }

    /**
     * @throws UserOnboardingGoalsRepositoryDuplicateException
     */
    final public function userActiveOnboardingGoal(int $userId, int $onboardingGoalId): ?ActiveRow
    {
        $userOnboardingGoals = $this->getTable()->where([
            'user_id' => $userId,
            'onboarding_goal_id' => $onboardingGoalId,
            'completed_at IS NULL',
            'timedout_at IS NULL',
        ])->fetchAll();

        // too many active entries
        if (count($userOnboardingGoals) > 1) {
            throw new UserOnboardingGoalsRepositoryDuplicateException(
                "User [{$userId}] has more active entries for onboarding goal [{$onboardingGoalId}]."
            );
        }

        if (count($userOnboardingGoals) === 0) {
            return null;
        }

        return reset($userOnboardingGoals);
    }

    final public function userLastGoal(int $userId, int $onboardingGoalId): ?ActiveRow
    {
        $lastUserGoal = $this->getTable()
            ->where(['user_id' => $userId])
            ->where('onboarding_goal_id = ?', $onboardingGoalId)
            ->order('created_at DESC')
            ->fetch();
        return $lastUserGoal ?: null;
    }

    final public function userCompletedGoals(int $userId, array $onboardingGoalIds): Selection
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

    final public function update(ActiveRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();

        $result = parent::update($row, $data);

        if (isset($data['completed_at'])) {
            $this->emitter->emit(new UserOnboardingGoalCompletedEvent($row));
        } elseif (isset($data['timedout_at'])) {
            $this->emitter->emit(new UserOnboardingGoalTimedoutEvent($row));
        } elseif (count($data) === 1 && isset($data['updated_at'])) {
            // only updated_at was changed -> user onboarding goal was "touched" to indicate that it is still alive
            $this->emitter->emit(new UserOnboardingGoalCreatedEvent($row));
        }

        return $result;
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
     * @return array|Row[]|ResultSet
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
     * @return array|Row[]|ResultSet
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
