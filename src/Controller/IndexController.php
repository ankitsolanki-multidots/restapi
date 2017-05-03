<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace restapi\Controller;

class IndexController extends ApiController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function indexAction()
    {
        $this->httpStatusCode = 404;
    	$this->apiResponse = array('error'=>'not found');
        return $this->createResponse();
    }
}
