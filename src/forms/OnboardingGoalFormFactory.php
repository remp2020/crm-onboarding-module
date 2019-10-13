<?php

namespace Crm\OnboardingModule\Forms;

use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Form;
use Tomaj\Form\Renderer\BootstrapRenderer;

class OnboardingGoalFormFactory
{
    private $translator;

    private $onboardingGoalsRepository;

    /** @var Callable */
    public $onUpdate;

    /** @var Callable */
    public $onSave;

    public function __construct(
        OnboardingGoalsRepository $onboardingGoalsRepository,
        Translator $translator
    ) {
        $this->translator = $translator;
        $this->onboardingGoalsRepository = $onboardingGoalsRepository;
    }

    public function create($id)
    {
        $defaults = [];
        if (isset($id)) {
            $onboardingGoal = $this->onboardingGoalsRepository->find($id);
            $defaults = $onboardingGoal->toArray();
        }

        $form = new Form;
        $form->setRenderer(new BootstrapRenderer());
        $form->setTranslator($this->translator);
        $form->addProtection();

        $form->addText('name', 'onboarding.data.goals.fields.name');
        $form->addText('code', 'onboarding.data.goals.fields.code');

        $form->addSelect(
            'type',
            'onboarding.data.goals.fields.type',
            [
                OnboardingGoalsRepository::TYPE_SIMPLE => OnboardingGoalsRepository::TYPE_SIMPLE
            ]
        );

        $form->addHidden('onboarding_goal_id', $id);

        $form->setDefaults($defaults);

        $form->addSubmit('send', 'system.save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-save"></i> ' . $this->translator->translate('system.save'));

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        $id = $values['onboarding_goal_id'];
        unset($values['onboarding_goal_id']);

        if ($id) {
            $goal = $this->onboardingGoalsRepository->find($id);
            $this->onboardingGoalsRepository->update($goal, $values);
            $this->onUpdate->__invoke($goal);
        } else {
            $goal = $this->onboardingGoalsRepository->add($values);
            $this->onSave->__invoke($goal);
        }
    }
}
