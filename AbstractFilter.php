<?php

namespace Anh\DoctrineResourceBundle;

abstract class AbstractFilter implements FilterInterface
{
    protected $fields;

    protected $sorting;

    public function __set($name, $value)
    {
        $this->fields[$name] = $value;
    }

    public function __get($name)
    {
        return isset($this->fields[$name]) ? $this->fields[$name] : null;
    }

    public function buildCriteria(array $parameters = array())
    {
        $criteria = array();

        foreach ($this->getDefinition($parameters) as $field => $definition) {
            $definition += array(
                'field' => $field,
                'operator' => '==',
                'mapped' => true,
                'empty_data' => null,
            );

            $value = $this->$field;

            if ($definition['mapped'] && $value !== $definition['empty_data']) {
                $criteria = array_merge($criteria, is_callable($definition['operator'])
                    ? call_user_func_array($definition['operator'], array($value, $this->fields))
                    : [ sprintf('%%%s', $definition['field']) => [ $definition['operator'] => $value ] ]
                );
            }
        }

        return $criteria;
    }

    public function buildSorting(array $parameters = array())
    {
        return $this->getSorting($parameters)->getSorting();
    }

    public function getSorting(array $parameters = array())
    {
        if ($this->sorting === null) {
            $this->sorting = new FilterSorting(
                $this->getSortFields($parameters),
                $this->getSortOrders($parameters)
            );
        }

        return $this->sorting;
    }

    abstract public function getDefinition(array $parameters = array());
    abstract public function getSortFields(array $parameters = array());

    public function getSortOrders(array $parameters = array())
    {
        return array(
            'desc' => '▽', // ▼
            'asc' => '△', // ▲
        );
    }
}
