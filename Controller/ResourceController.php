<?php

namespace Anh\DoctrineResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Anh\DoctrineResource\ResourceManager;

class ResourceController extends Controller
{
    protected $resourceManager;

    protected $optionsParser;

    protected $redirectHandler;

    public function __construct(
        ResourceManager $resourceManager,
        OptionsParser $optionsParser,
        RedirectHandler $redirectHandler
    ) {
        $this->resourceManager = $resourceManager;
        $this->optionsParser = $optionsParser;
        $this->redirectHandler = $redirectHandler;
    }

    public function createAction(Request $request)
    {
        $options = $this->getOptions($request, array(
            'redirect' => $this->getRouteName('list', $this->resourceManager->getResourceName()),
            'form' => $this->getFormType($this->resourceManager->getResourceName()),
            'view' => 'AnhDoctrineResource:Default:create.html.twig',
            'viewVars' => array()
        ));

        $resource = $this->resourceManager->createResource();
        $form = $this->createForm($options['form'], $resource);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $this->resourceManager->create($resource);
            $this->optionsParser->setResource($resource);

            return $this->redirectHandler
                ->setReferer($request->headers->get('referer'))
                ->redirectTo($options['redirect'])
            ;
        }

        return array(
            'view' => $options['view'],
            'data' => array(
                'resource' => $resource,
                'form' => $form->createView()
            ) + $options['viewVars']
        );
    }

    public function updateAction(Request $request)
    {
        $options = $this->getOptions($request, array(
            'criteria' => array(
                'id' => 'request.attributes.id'
            ),
            'sorting' => null,
            'redirect' => $this->getRouteName('list', $this->resourceManager->getResourceName()),
            'form' => $this->getFormType($this->resourceManager->getResourceName()),
            'view' => 'AnhDoctrineResource:Default:update.html.twig',
            'viewVars' => array()
        ));

        $resource = $this->resourceManager->getRepository()->findOneBy(
            $options['criteria'],
            $options['sorting']
        );

        $form = $this->createForm($options['form'], $resource);

        if (!$form->has('_redirect')) {
            $form->add($this->get('form.factory')->createNamed(
                '_redirect',
                'hidden',
                $request->headers->get('referer'),
                array(
                    'auto_initialize' => false,
                    'mapped' => false
                )
            ));
        }

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $this->resourceManager->update($resource);
            $this->optionsParser->setResource($resource);

            return $this->redirectHandler
                ->setReferer($form->get('_redirect')->getData())
                ->redirectTo($options['redirect'])
            ;
        }

        return array(
            'view' => $options['view'],
            'data' => array(
                'resource' => $resource,
                'form' => $form->createView()
            ) + $options['viewVars']
        );
    }

    public function deleteAction(Request $request)
    {
        $options = $this->getOptions($request, array(
            'criteria' => null,
            'resource' => 'request.request.id',
            'redirect' => $this->getRouteName('list', $this->resourceManager->getResourceName()),
        ));

        $resource = $options['resource'];

        if (!empty($options['criteria'])) {
            $resource = $this->resourceManager->getRepository()
                ->fetch($options['criteria'])
            ;
        }

        $this->resourceManager->delete($resource);

        return $this->redirectHandler
            ->setReferer($request->headers->get('referer'))
            ->redirectTo($options['redirect'])
        ;
    }

    public function listAction(Request $request)
    {
        $options = $this->getOptions($request, array(
            'criteria' => null,
            'sorting' => null,
            'limit' => null,
            'method' => null,
            'paginator' => null,
            'arguments' => array(),
            'view' => 'AnhDoctrineResource:Default:list.html.twig',
            'viewVars' => array()
        ));

        if (empty($options['method'])) {
            if (empty($options['paginator'])) {
                $options['method'] = 'fetch';
                $options['arguments'] = array(
                    $options['criteria'],
                    $options['sorting'],
                    $options['limit']
                );
            } else {
                $options['paginator'] = $this->optionsParser->process($options['paginator'] + array(
                    'page' => 'request.attributes.page',
                    'limit' => 10,
                    'route' => 'request.attributes._route',
                    'parameter' => 'page'
                ));

                $options['method'] = 'paginate';
                $options['arguments'] = array(
                    $options['paginator']['page'] ?: 1,
                    $options['paginator']['limit'],
                    $options['criteria'],
                    $options['sorting']
                );
            }
        }

        $resources = call_user_func_array(
            array($this->resourceManager->getRepository(), $options['method']),
            $options['arguments']
        );

        if (!empty($options['paginator']['route'])) {
            // set url
        }

        return array(
            'view' => $options['view'],
            'data' => array(
                'resources' => $resources
            ) + $options['viewVars']
        );
    }

    public function showAction(Request $request)
    {
        $options = $this->getOptions($request, array(
            'criteria' => array(
                'id' => 'request.attributes.id'
            ),
            'sorting' => null,
            'view' => 'AnhDoctrineResource:Default:show.html.twig',
            'viewVars' => array()
        ));

        $resource = $this->resourceManager->getRepository()->findOneBy(
            $options['criteria'],
            $options['sorting']
        );

        return array(
            'view' => $options['view'],
            'data' => array(
                'resource' => $resource
            ) + $options['viewVars']
        );
    }

    public function dummyAction(Request $request)
    {
        $options = $this->getOptions($request, array(
            'view' => null,
            'viewVars' => array()
        ));

        return array(
            'view' => $options['view'],
            'data' => $options['viewVars']
        );
    }

    protected function getOptions(Request $request, array $defaults = array())
    {
        $options = $request->attributes->get('_options', array()) + $defaults;

        return $this->optionsParser
            ->setRequest($request)
            ->parse($options)
        ;
    }

    protected function getRouteName($name, $resourceName)
    {
        return sprintf('%s_%s', str_replace('.', '_', $resourceName), $name);
    }

    protected function getFormType($resourceName)
    {
        return str_replace('.', '_', $resourceName);
    }
}
