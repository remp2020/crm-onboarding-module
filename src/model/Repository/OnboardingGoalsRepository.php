<?php

namespace Crm\OnboardingModule\Repository;

use Crm\ApplicationModule\Repository;
use Crm\OnboardingModule\Events\OnboardingGoalCreatedEvent;
use Crm\OnboardingModule\Events\OnboardingGoalUpdatedEvent;
use League\Event\Emitter;
use Nette\Database\Context;
use Nette\Database\Table\IRow;
use Nette\Utils\DateTime;

class OnboardingGoalsRepository extends Repository
{
    public const TYPE_SIMPLE = 'simple';

    protected $tableName = 'onboarding_goals';

    private $emitter;

    public function __construct(
        Context $database,
        Emitter $emitter
    ) {
        parent::__construct($database);
        $this->emitter = $emitter;
    }

    // TODO: add segment goal type (user completes goal when he appears in segment)

    final public function add($code, $name, $type)
    {
        $data = [
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'created_at' => new DateTime(),
            'updated_at' => new DateTime(),
        ];
        $onboardingGoal = $this->insert($data);
        $this->emitter->emit(new OnboardingGoalCreatedEvent($onboardingGoal));
        return $onboardingGoal;
    }

    final public function update(IRow &$row, $data)
    {
        $originalCode = $row['code'];

        $data['updated_at'] = new DateTime();
        $updated = parent::update($row, $data);

        $this->emitter->emit(new OnboardingGoalUpdatedEvent($originalCode, $row));
        return $updated;
    }

    final public function all()
    {
        return $this->getTable();
    }

    final public static function availableTypes()
    {
        return [OnboardingGoalsRepository::TYPE_SIMPLE];
    }
}
