<?php
namespace ApiBundle\Service;

use Symfony\Component\HttpKernel\Event as Events;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\AbstractQuery;

use ApiBundle\Repository\AbstractRepository;
use ApiBundle\Repository\ApiCriteria;
use ApiBundle\Repository\ApiInput;
use ApiBundle\Repository\ApiQuery;
use ApiBundle\Repository\DatabaseNameWalker;

class KernelListener
{
    protected $uploadRelativePath;

    public function __construct($uploadRelativePath)
    {
        $this->uploadRelativePath = $uploadRelativePath;
    }

    public function onKernelController(Events\FilterControllerEvent $event)
    {
        $controller = $event->getController()[0];
        if ($controller instanceof \Symfony\Bundle\TwigBundle\Controller\ExceptionController) {
            return;
        }
        $user = $controller->get('security.context')->getToken()->getUser();
    }

    public function onKernelRequest(Events\GetResponseEvent $event)
    {
        // Used to decorate uploaded file paths to make full URLs.
        AbstractRepository::$uploadRoot = $event->getRequest()->getSchemeAndHttpHost().'/'.$this->uploadRelativePath;

        if ($event->getRequest()->isMethod('GET')) {
            $getParams = $event->getRequest()->query;
            $apiCriteria = new ApiCriteria();
            $filters = [];
            foreach ($getParams as $param => $value) {
                switch ($param) {
                    case 'pageSize':
                        $apiCriteria->pageSize = $value;
                        break;
                    case 'pageNumber':
                        $apiCriteria->pageNumber = $value;
                        break;
                    case 'sorting':
                        $apiCriteria->sorting = $value;
                        break;
                    default:
                        $apiCriteria->addUserFilter($param, $value);
                        break;
                }
            }
            $event->getRequest()->attributes->set('apiCriteria', $apiCriteria);
            return;
        }
        
        if ($event->getRequest()->isMethod('POST') || $event->getRequest()->isMethod('PUT')) {
            $apiInput = new ApiInput();
            try {
                $apiInput->files = $event->getRequest()->files->all();
                if ($event->getRequest()->getContentType() == 'form') {
                } else {
                    $apiInput->jsonBody = $event->getRequest()->getContent();
                }
            } catch (\Exception $e) {
                if (count($apiInput->files) == 0) {
                    $response = new Response('{"message":"'.$e->getMessage().'", "code":1000}', 400);
                    $event->setResponse($response);
                }
            }
            $event->getRequest()->attributes->set('apiInput', $apiInput);
            return;
        }
    }
}
