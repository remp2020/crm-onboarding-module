<?php

namespace Crm\OnboardingModule\Api;

use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\ApiModule\Api\ApiHandler;
use Crm\OnboardingModule\Repository\OnboardingGoalsRepository;
use Crm\OnboardingModule\Repository\UserOnboardingGoalsRepository;
use Crm\UsersModule\Repository\UsersRepository;
use JsonSchema\Validator;
use Nette\Http\Response;
use Nette\Utils\Json;
use Tracy\Debugger;

class OnboardingGoalCompletedHandler extends ApiHandler
{
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
        $request = file_get_contents("php://input");
        if (empty($request)) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'Empty request']);
            $response->setHttpCode(Response::S400_BAD_REQUEST);
            return $response;
        }
        $json = json_decode($request, true);
        if (empty($json)) {
            $response = new JsonResponse(['status' => 'error', 'message' => "Malformed JSON. (error: ".json_last_error().")"]);
            $response->setHttpCode(Response::S400_BAD_REQUEST);
            return $response;
        }

        $validator = $this->validateInput($request);
        if (!$validator->isValid()) {
            $data = ['status' => 'error', 'message' => 'Payload error', 'errors' => []];
            foreach ($validator->getErrors() as $error) {
                $data['errors'][] = $error['message'];
            }
            Debugger::log('Cannot parse - ' . $request . ' -> ' . implode(', ', $data['errors']));
            $response = new JsonResponse($data);
            $response->setHttpCode(Response::S400_BAD_REQUEST);
            return $response;
        }

        $goalCode = $json['goal_code'];
        $userId = $json['user_id'];

        $goal = $this->onboardingGoalsRepository->findBy('code', $goalCode);
        if (!$goal) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'goal not found']);
            $response->setHttpCode(Response::S404_NOT_FOUND);
            return $response;
        }

        $user = $this->usersRepository->find($userId);
        if (!$user) {
            $response = new JsonResponse(['status' => 'error', 'message' => 'user not found']);
            $response->setHttpCode(Response::S404_NOT_FOUND);
            return $response;
        }

        $this->userOnboardingGoalsRepository->complete($userId, $goal->id);

        $response = new JsonResponse(['status' => 'ok']);
        $response->setHttpCode(Response::S200_OK);

        return $response;
    }

    private function validateInput(string $input): Validator
    {
        $schema = Json::decode(file_get_contents(__DIR__ . '/goal_completed.schema.json'));
        $data = Json::decode($input);
        $validator = new Validator();
        $validator->validate($data, (object) $schema);
        return $validator;
    }
}
