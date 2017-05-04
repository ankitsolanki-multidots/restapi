<?php

namespace restapi;

use Zend\Mvc\MvcEvent;
use Zend\Mvc\ModuleRouteListener;
use Zend\View\Model\JsonModel;

class Module
{
    const VERSION = '3.0.3-dev';

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
    
    /**
     * When Request Came Then first call This function and then inside controller.
     * @param \Zend\Mvc\MvcEvent $e
     */
    
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager   = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, array($this,'boforeDispatch'), 100);
        // Set CORS headers to allow all requests
        $headers = $e->getResponse()->getHeaders();
        $headers->addHeaderLine('Access-Control-Allow-Origin: *');
        $headers->addHeaderLine('Access-Control-Allow-Methods: PUT, GET, POST, PATCH, DELETE, OPTIONS');
        $headers->addHeaderLine('Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept');
    }
    
    /**
     * This Method is call from above onBootstrap function to check Route need authentication or not
     * 
     * @param \Zend\Mvc\MvcEvent $event
     * @return Object Response
     */
    public function boforeDispatch(MvcEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $auth = $event->getRouteMatch()->getParam('isauth');
        $config = $event->getApplication()->getServiceManager()->get('Config');
        $event->setParam('config', $config);
        if ($auth) {
            $token = $event->getRequest()->getHeaders("Authorization")?$event->getRequest()->getHeaders("Authorization"):$request->isGet()?$request->getQuery('token'):$request->isPOST()?:$request->getPost('token');
            
            if (!$token) {
                
                $response->setStatusCode(401);
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                $view = new JsonModel([ $config['ApiRequest']['responseFormat']['statusKey'] => $config['ApiRequest']['responseFormat']['statusNokText'], $config['ApiRequest']['responseFormat']['resultKey'] => [ $config['ApiRequest']['responseFormat']['errorKey'] => $config['ApiRequest']['responseFormat']['authenticationRequireText'] ]]);
                $response->setContent($view->serialize());
                return $response;
            }
        }
    }
}
