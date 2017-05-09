<?php
namespace restapi\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Firebase\JWT\JWT;
use Zend\EventManager\EventManagerInterface;

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
     * set Event Manager to check Authorization
     * @param \Zend\EventManager\EventManagerInterface $events
     */
    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);
        $events->attach('dispatch', array($this, 'checkAuthorization'), 10);
    }
    
    /**
     * This Function call from eventmanager to check authntication and token validation
     * @param type $event
     * 
     */
    public function checkAuthorization($event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $isAuthorizationRequired = $event->getRouteMatch()->getParam('isAuthorizationRequired');
        $config = $event->getApplication()->getServiceManager()->get('Config');
        $event->setParam('config', $config);
        $responseStatusKey = $config['ApiRequest']['responseFormat']['statusKey'];
        if (!$isAuthorizationRequired) {
            return;
        }
        $jwtToken = $this->findJwtToken($request);
        if ($jwtToken) {
            $tokenValue = $this->decodeJwtToken($jwtToken);
            if (is_object($tokenValue)) {
                return;
            }
            $response->setStatusCode(400);
            $jsonModelArr = [$responseStatusKey => $config['ApiRequest']['responseFormat']['statusNokText'], $config['ApiRequest']['responseFormat']['resultKey'] => [$config['ApiRequest']['responseFormat']['errorKey'] => $tokenValue]];
        } else {
            $response->setStatusCode(401);
            $jsonModelArr = [$responseStatusKey => $config['ApiRequest']['responseFormat']['statusNokText'], $config['ApiRequest']['responseFormat']['resultKey'] => [$config['ApiRequest']['responseFormat']['errorKey'] => $config['ApiRequest']['responseFormat']['authenticationRequireText']]];
        }
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $view = new JsonModel($jsonModelArr);
        $response->setContent($view->serialize());
        return $response;
    }
    
    /**
     * Check Requenst object have Authorization token or not 
     * @param type $request
     * @return type String
     */
    public function findJwtToken($request)
    {
        $jwtToken = $request->getHeaders("Authorization") ? $request->getHeaders("Authorization")->getFieldValue() : '';
        if ($jwtToken) {
            $jwtToken = trim(trim($jwtToken, "Bearer"), " ");
            return $jwtToken;
        }
        if ($request->isGet()) {
            $jwtToken = $request->getQuery('token');
        }
        if ($request->isPost()) {
            $jwtToken = $request->getPost('token');
        }
        return $jwtToken;
    }

    /**
     * contain user information for createing JWT Token
     * @param $payload Array|Object
     * @return String
     */
    protected function encodeJwtToken($payload) 
    {
        if (!is_array($payload)) {
            return false;
        }
        $config = $this->getEvent()->getParam('config', false);
        $cypherKey = $config['ApiRequest']['jwtAuth']['cypherKey'];
        $tokenAlgorithm = $config['ApiRequest']['jwtAuth']['tokenAlgorithm'];
        return JWT::encode($payload, $cypherKey, $tokenAlgorithm);
    }

    /**
     * contain encoded token for user.
     * @param $token String 
     * @return Array|Object or Error Message
     */
    protected function decodeJwtToken($token)
    {
        if (!$token) {
            return false;
        }
        $config = $this->getEvent()->getParam('config', false);
        $cypherKey = $config['ApiRequest']['jwtAuth']['cypherKey'];
        $tokenAlgorithm = $config['ApiRequest']['jwtAuth']['tokenAlgorithm'];
        try {
            $decodeToken = JWT::decode($token, $cypherKey, [$tokenAlgorithm]);
        } catch(\Exception $e){
            return $e->getMessage();
        }
        return $decodeToken;
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
            $this->httpStatusCode = 500;
            $response->setStatusCode($this->httpStatusCode);
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
