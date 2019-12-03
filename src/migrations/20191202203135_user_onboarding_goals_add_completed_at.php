<?php

use Phinx\Migration\AbstractMigration;

class UserOnboardingGoalsAddCompletedAt extends AbstractMigration
{
    public function change()
    {
        $this->table('user_onboarding_goals')
            ->removeColumn('done')
            ->addColumn('completed_at', 'datetime', ['null' => true])
            ->update();
    }
}
