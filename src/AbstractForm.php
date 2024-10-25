<?php

namespace Contenir\Db\QueryFilter;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class AbstractForm extends Form implements InputFilterProviderInterface
{
    protected FilterSet $filterSet;
    protected array $spec = [];

    public function setFilterSet(FilterSet $filterSet): self
    {
        $this->filterSet = $filterSet;
        return $this;
    }

    public function getFilterSet(): FilterSet
    {
        return $this->filterSet;
    }

    public function build()
    {
        foreach ($this->filterSet->getFilters() as $filter) {
            $name    = $filter->getFilterParam();
            $element = $filter->getElement();
            if ($element) {
                $this->add($element);
            }

            $spec = $filter->getInputFilterSpecification();
            if ($spec) {
                $this->spec[$name] = $spec;
            }
        }
    }

    public function getInputFilterSpecification(): array
    {
        return $this->spec;
    }
}
