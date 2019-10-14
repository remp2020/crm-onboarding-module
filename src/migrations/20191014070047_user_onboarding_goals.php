<?php

use Phinx\Migration\AbstractMigration;

class UserOnboardingGoals extends AbstractMigration
{
    public function change()
    {
        $this->table('user_onboarding_goals')
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('onboarding_goal_id', 'integer', ['null' => false])
            ->addColumn('done', 'boolean', ['default' => 0])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])

            ->addForeignKey('user_id', 'users')
            ->addForeignKey('onboarding_goal_id', 'onboarding_goals')
            ->create();
    }
}
