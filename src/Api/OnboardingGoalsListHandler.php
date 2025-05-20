<?php

namespace Crm\OnboardingModule\Api;

use Crm\ApiModule\Models\Api\ApiHandler;
use Crm\OnboardingModule\Repositories\OnboardingGoalsRepository;
use Nette\Http\Response;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class OnboardingGoalsListHandler extends ApiHandler
{
    private $onboardingGoalsRepository;

    public function __construct(OnboardingGoalsRepository $onboardingGoalsRepository)
    {
        $this->onboardingGoalsRepository = $onboardingGoalsRepository;
    }

    public function params(): array
    {
        return [];
    }


    public function handle(array $params): ResponseInterface
    {
        $goals = [];

        foreach ($this->onboardingGoalsRepository->getTable()->order('name') as $goal) {
            $goals[] = [
                'name' => $goal->name,
                'code' => $goal->code,
                'type' => $goal->type,
            ];
        }

        $response = new JsonApiResponse(Response::S200_OK, ['status' => 'ok', 'goals' => $goals]);

        return $response;
    }
}
