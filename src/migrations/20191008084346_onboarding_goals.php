<?php

use Phinx\Migration\AbstractMigration;

class OnboardingGoals extends AbstractMigration
{
    public function change()
    {
        $this->table('onboarding_goals')
            ->addColumn('code', 'string', ['null' => false])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('type', 'string', ['null' => true])

            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])

            ->create();
    }
}
