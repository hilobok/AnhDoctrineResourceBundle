<?php

namespace Anh\DoctrineResourceBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\HttpFoundation\Request;
use Anh\DoctrineResourceBundle\FilterFormBuilder;

class OptionsParser extends ContainerAware
{
    protected $langauge;

    protected $resolver;

    protected $request;

    protected $resource;

    protected $resourceName;

    protected $filterFormBuilder;

    public function __construct()
    {
        $this->language = new ExpressionLanguage();
        $this->resolver = new OptionsResolver();
        $this->configureOptions($this->resolver);
    }

    public function parse(array $options = array(), array $defaults = array())
    {
        $this->resolver->setDefaults(
            $this->process($defaults)
        );

        return $this->resolver->resolve(
            $this->process($options)
        );
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    public function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    public function setFilterFormBuilder(FilterFormBuilder $filterFormBuilder)
    {
        $this->filterFormBuilder = $filterFormBuilder;

        return $this;
    }

    public function process($option)
    {
        if (is_array($option)) {
            foreach ($option as $key => $value) {
                $option[$key] = $this->process($value);
            }

            return $option;
        }

        if (!is_string($option)) {
            return $option;
        }

        if (strpos($option, 'request.') !== false && $this->request) {
            return $this->language->evaluate($option, array(
                'request' => $this->request
            ));
        }

        if (strpos($option, 'resource.') !== false && $this->resource) {
            return $this->language->evaluate($option, array(
                'resource' => $this->resource
            ));
        }

        if (strpos($option, 'container.') !== false && $this->container) {
            return $this->language->evaluate($option, array(
                'container' => $this->container
            ));
        }

        return $option;
    }

    protected function configureOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'filter' => null,
            'criteria' => null,
            'sorting' => null,
            'offset' => null,
            'page' => null,
            'limit' => function (Options $options) {
                return is_null($options['page']) ? null : 10;
            },

            'method' => function (Options $options) {
                return is_null($options['page']) ? 'fetch' : 'paginate';
            },
            'arguments' => function (Options $options) {
                switch ($options['method']) {
                    case 'fetch':
                        return array(
                            $options['criteria'],
                            $options['sorting'],
                            $options['limit'],
                            $options['offset'],
                        );

                    case 'paginate':
                        return array(
                            $options['page'],
                            $options['limit'],
                            $options['criteria'],
                            $options['sorting'],
                        );

                    case 'fetchOne':
                    case 'findOneBy':
                        return array(
                            $options['criteria'],
                            $options['sorting'],
                        );

                    default:
                        return array();
                }
            },

            'redirect' => 'list',

            'form' => function (Options $options) {
                return str_replace('.', '_', $this->resourceName);
            },
            'form_options' => array(),

            'view' => null,
            'data' => array(),
        ));

        $resolver->setNormalizers(array(
            'filter' => function (Options $options, $value) {
                if (empty($value)) {
                    return null;
                }

                if (is_string($value)) {
                    if (!class_exists($value)) {
                        throw new \Exception(
                            sprintf("Filter '%s' not exists", $value)
                        );
                    }

                    $filter = array(
                        'instance' => new $value,
                        'parameters' => array(),
                    );
                }

                if (is_object($value)) {
                    $filter = array(
                        'instance' => $value,
                        'parameters' => array(),
                    );
                }

                if (is_array($value)) {
                    $filter = $value + array(
                        'parameters' => array(),
                    );
                }

                if (isset($filter['instance']) && isset($filter['parameters'])) {
                    $filter['form'] = $this->filterFormBuilder->build(
                        $filter['instance'],
                        $filter['parameters']
                    );

                    return $filter;
                }

                throw new \Exception('Unable to normalize filter.');
            },

            'criteria' => function (Options $options, $value) {
                if (isset($options['filter']['instance'])) {
                    $filter = $options['filter']['instance'];
                    $parameters = $options['filter']['parameters'];

                    if ($options['filter']['form']->handleRequest($this->request)->isValid()) {
                        $criteria = $filter->buildCriteria($parameters);

                        if (!empty($criteria)) {
                            $value = array_merge((array) $value, $criteria);
                        }
                    }
                }

                return $value;
            },

            'sorting' => function (Options $options, $value) {
                if (isset($options['filter']['instance'])) {
                    $parameters = $options['filter']['parameters'];
                    $value = $options['filter']['instance']->buildSorting($parameters);
                }

                return $value;
            },

            'data' => function (Options $options, $value) {
                if (isset($options['filter']['form'])) {
                    $value = array_merge(
                        (array) $value,
                        array('filter' => $options['filter']['form']->createView())
                    );
                }

                return $value;
            },

            'redirect' => function (Options $options, $value) {
                if (!is_array($value)) {
                    $value = array(
                        'route' => $value,
                        'parameters' => array(),
                    );
                }

                if (in_array($value['route'], array('create', 'update', 'list', 'show'), true)) {
                    $value['route'] = sprintf(
                        '%s_%s',
                        str_replace('.', '_', $this->resourceName),
                        $value['route']
                    );
                }

                return $value + array('parameters' => array());
            }
        ));

        $resolver->setAllowedValues(array(
            'redirect' => function ($value) {
                return is_null($value) || is_string($value) || (is_array($value) && isset($value['route']));
            },
            'view' => function ($value) {
                return is_null($value) || is_string($value) || (is_array($value) && isset($value['format']));
            }
        ));
    }
}
