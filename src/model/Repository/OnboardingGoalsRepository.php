<?php

namespace Crm\OnboardingModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\IRow;
use Nette\Utils\DateTime;

class OnboardingGoalsRepository extends Repository
{
    protected $tableName = 'onboarding_goals';

    const TYPE_SIMPLE = 'simple';

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
        return $this->insert($data);
    }

    final public function update(IRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    final public function all()
    {
        return $this->getTable()->order('created_at DESC');
    }

    final public static function availableTypes()
    {
        return [OnboardingGoalsRepository::TYPE_SIMPLE];
    }
}
