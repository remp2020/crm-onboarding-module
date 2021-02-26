<?php

use Phinx\Migration\AbstractMigration;

class AddUniqueIndexToOnboardingGoals extends AbstractMigration
{
    public function change()
    {
        $this->table('onboarding_goals')
            ->addIndex('code', ['unique' => true])
            ->update();
    }
}
