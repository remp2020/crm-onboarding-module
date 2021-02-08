<?php

use Phinx\Migration\AbstractMigration;

class RemoveUniqueIndexFromUserOnboardingGoal extends AbstractMigration
{
    public function up()
    {
        $this->table('user_onboarding_goals')
            ->addIndex('user_id')
            ->removeIndex(['user_id', 'onboarding_goal_id'])
            ->update();
    }

    public function down()
    {
        $this->table('user_onboarding_goals')
            ->removeIndex(['user_id'])
            ->addIndex(['user_id', 'onboarding_goal_id'], ['unique' => true])
            ->update();
    }
}
