<?php

namespace Crm\OnboardingModule\Api;

use Crm\ApiModule\Models\Api\ApiHandler;
use Crm\ApiModule\Models\Api\JsonValidationTrait;
use Crm\OnboardingModule\Repositories\OnboardingGoalsRepository;
use Crm\OnboardingModule\Repositories\UserOnboardingGoalsRepository;
use Crm\UsersModule\Repositories\UsersRepository;
use Nette\Http\Response;
use Tomaj\NetteApi\Response\JsonApiResponse;
use Tomaj\NetteApi\Response\ResponseInterface;

class OnboardingGoalCompletedHandler extends ApiHandler
{
    use JsonValidationTrait;

    private $onboardingGoalsRepository;

    private $userOnboardingGoalsRepository;

    private $usersRepository;

    public function __construct(
        OnboardingGoalsRepository $onboardingGoalsRepository,
        UsersRepository $usersRepository,
        UserOnboardingGoalsRepository $userOnboardingGoalsRepository
    ) {
        $this->onboardingGoalsRepository = $onboardingGoalsRepository;
        $this->userOnboardingGoalsRepository = $userOnboardingGoalsRepository;
        $this->usersRepository = $usersRepository;
    }

    public function params(): array
    {
        return [];
    }


    public function handle(array $params): ResponseInterface
    {
        $result = $this->validateInput(__DIR__ . '/goal_completed.schema.json');
        if ($result->hasErrorResponse()) {
            return $result->getErrorResponse();
        }

        $json = $result->getParsedObject();
        $goalCode = $json->goal_code;
        $userId = $json->user_id;

        $goal = $this->onboardingGoalsRepository->findBy('code', $goalCode);
        if (!$goal) {
            $response = new JsonApiResponse(Response::S404_NOT_FOUND, ['status' => 'error', 'message' => "goal '$goalCode' not found"]);
            return $response;
        }

        $user = $this->usersRepository->find($userId);
        if (!$user) {
            $response = new JsonApiResponse(Response::S404_NOT_FOUND, ['status' => 'error', 'message' => "user with ID '$userId' not found"]);
            return $response;
        }

        $this->userOnboardingGoalsRepository->complete($userId, $goal->id);

        $response = new JsonApiResponse(Response::S200_OK, ['status' => 'ok']);

        return $response;
    }
}
