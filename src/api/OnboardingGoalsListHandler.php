<?php

namespace Crm\OnboardingModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Nette\Http\Response;

class OnboardingGoalsListHandler extends ApiHandler
{
    private $onboardingGoalsRepository;

    public function __construct(OnboardingGoalsRepository $onboardingGoalsRepository)
    {
        $this->onboardingGoalsRepository = $onboardingGoalsRepository;
    }

    public function params()
    {
        return [];
    }

    /**
     * @param ApiAuthorizationInterface $authorization
     * @return \Nette\Application\IResponse
     */
    public function handle(ApiAuthorizationInterface $authorization)
    {
        $goals = [];

        foreach ($this->onboardingGoalsRepository->getTable()->order('name') as $goal) {
            $goals[] = [
                'name' => $goal->name,
                'code' => $goal->code,
                'type' => $goal->type
            ];
        }

        $response = new JsonResponse(['status' => 'ok', 'goals' => $goals]);
        $response->setHttpCode(Response::S200_OK);

        return $response;
    }
}
