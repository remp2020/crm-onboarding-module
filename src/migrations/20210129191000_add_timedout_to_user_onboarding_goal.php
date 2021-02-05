<?php

use Phinx\Migration\AbstractMigration;

class AddTimedoutToUserOnboardingGoal extends AbstractMigration
{
    public function change()
    {
        $this->table('user_onboarding_goals')
            ->addColumn('timedout_at', 'datetime', ['null' => true, 'after' => 'completed_at'])
            ->addIndex('timedout_at')
            ->update();
    }
}
