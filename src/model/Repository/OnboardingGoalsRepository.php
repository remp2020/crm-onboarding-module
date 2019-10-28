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

    public function add($data)
    {
        $data['created_at'] = new DateTime();
        $data['updated_at'] = new DateTime();
        return $this->insert($data);
    }

    public function update(IRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    public function all()
    {
        return $this->getTable()->order('created_at DESC');
    }

    public static function inputForTypeSelect()
    {
        return [
            OnboardingGoalsRepository::TYPE_SIMPLE => OnboardingGoalsRepository::TYPE_SIMPLE
        ];
    }
}
