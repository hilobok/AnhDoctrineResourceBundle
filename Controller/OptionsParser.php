<?php

namespace Anh\DoctrineResourceBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

class OptionsParser
{
    protected $request;

    protected $resource;

    protected $options;

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

    public function parse(array $options)
    {
        return $this->process($options);
    }

    public function process($option)
    {
        if (is_array($option)) {
            foreach ($option as $key => $value) {
                $option[$key] = $this->process($value);
            }

            return $option;
        }

        if (strpos($option, 'request.') === 0) {
            return $this->getFromRequest($option);
        }

        if (strpos($option, 'resource.') === 0) {
            return $this->getFromResource($option);
        }

        return $option;
    }

    protected function getFromRequest($name)
    {
        $name = substr($name, 8);

        if (strpos($name, '.') === false) {
            $name = sprintf('attributes.%s', $name);
        }

        list($bag, $name) = explode('.', $name, 2);

        if (!in_array($bag, array('query', 'request', 'attributes'), true)) {
            throw new \InvalidArgumentException(
                sprintf("Unknown ParameterBag '%s'.", $bag)
            );
        }

        return $this->request->{$bag}->get($name);
    }

    protected function getFromResource($name)
    {
        if (!$this->resource) {
            return $name;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        return $accessor->getValue($this->resource, substr($name, 9));
    }
}
