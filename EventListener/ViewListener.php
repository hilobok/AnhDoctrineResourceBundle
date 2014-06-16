<?php

namespace Anh\DoctrineResourceBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class ViewListener
{
    public function __construct($templating, $serializer = null)
    {
        $this->templating = $templating;
        $this->serializer = $serializer;
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $controllerResult = $event->getControllerResult() + array(
            'view' => null,
            'data' => array()
        );

        if (is_string($controllerResult['view'])) {
            $response = $this->templating->renderResponse(
                $controllerResult['view'],
                $controllerResult['data'],
                $event->hasResponse() ? $event->getResponse() : null
            );

            return $event->setResponse($response);
        }

        if (is_array($controllerResult['view'])) {
            if (!$this->serializer) {
                throw new \RuntimeException(
                    'Serializer service is not enabled.'
                );
            }

            $view = $controllerResult['view'] + array(
                'format' => 'json',
                'vars' => array()
            );

            $data = empty($view['vars']) ? $controllerResult['data'] :
                array_intersect_key(
                    (array) $controllerResult['data'],
                    array_flip((array) $view['vars'])
                )
            ;

            $content = $this->serializer->serialize($data, $view['format']);

            switch ($view['format']) {
                case 'json':
                    $response = new JsonResponse($content);
                    break;

                case 'xml':
                    $response = new Response(
                        sprintf("<?xml version='1.0' encoding='UTF-8'?>\n%s", $content)
                    );
                    $response->headers->set('Content-Type', 'text/xml');
                    break;

                default:
                    throw new \InvalidArgumentException(
                        sprintf("Have no idea how to create reponse for '%s' format.", $view['format'])
                    );
            }

            $event->setResponse($response);
        }
    }
}
