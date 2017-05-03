<?php
namespace restapi\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Firebase\JWT\JWT;

class ApiController extends AbstractRestfulController
{
    /**
     * @var Integer $httpStatusCode Define Api Response code.
     */
    public $httpStatusCode = 200;

    /**
     * @var array $apiResponse Define response for api
     */
    public $apiResponse;

    /**
     * contain user information for createing JWT Token
     * @param $payload Array|Object
     * @return String
     */
    protected function encodeJwtToken($payload) 
    {
        $config = $this->getEvent()->getParam('config', false);
        $cypherKey = $config['ApiRequest']['jwtAuth']['cypherKey'];
        $tokenAlgorithm = $config['ApiRequest']['jwtAuth']['tokenAlgorithm'];
        return JWT::encode($payload, $cypherKey, $tokenAlgorithm);
    }

    /**
     * contain encoded token for user.
     * @param $token String 
     * @return Array|Object
     */
    protected function decodeJwtToken($token)
    {
        $config = $this->getEvent()->getParam('config', false);
        $cypherKey = $config['ApiRequest']['jwtAuth']['cypherKey'];
        $tokenAlgorithm = $config['ApiRequest']['jwtAuth']['tokenAlgorithm'];
        return JWT::decode($token, $cypherKey, [$tokenAlgorithm]);
    }

    /**
     * Create Response for api Assign require data for response and check is valid response or give error
     * @return \Zend\View\Model\JsonModel 
     * 
     */
    public function createResponse()
    {
        $config = $this->getEvent()->getParam('config', false);
        $event = $this->getEvent();
        $response = $event->getResponse();

        if (is_array($this->apiResponse)) {
            $response->setStatusCode($this->httpStatusCode);
        } else {
            $response->setStatusCode(500);
            $errorKey = $config['ApiRequest']['responseFormat']['errorKey'];
            $defaultErrorText = $config['ApiRequest']['responseFormat']['defaultErrorText'];
            $this->apiResponse[$errorKey] = $defaultErrorText;
        }
        $statusKey = $config['ApiRequest']['responseFormat']['statusKey'];
        if ($this->httpStatusCode == 200) {
            $sendResponse[$statusKey] = $config['ApiRequest']['responseFormat']['statusOkText']; 
        } else {
            $sendResponse[$statusKey] = $config['ApiRequest']['responseFormat']['statusNokText']; 
        }
        $sendResponse[$config['ApiRequest']['responseFormat']['resultKey']] = $this->apiResponse;
        return new JsonModel($sendResponse);
    }
}
