<?php

use Phinx\Migration\AbstractMigration;

class UserOnboardingGoalsAddCreatedAtIndex extends AbstractMigration
{
    public function change()
    {
        $this->table('user_onboarding_goals')
            ->addIndex('created_at')
            ->save();
    }
}
