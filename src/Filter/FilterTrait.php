<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use RuntimeException;

trait FilterTrait
{
    protected $filterParam;
    protected $filterDefault;
    protected $filterRequired = false;
    protected $filterLabel;
    protected $filterAttributes = [];

    public function getFilterValue()
    {
        return $this->filterSet->getInput()[$this->filterParam] ?? $this->filterDefault;
    }

    public function getFilterParam()
    {
        if ($this->filterParam === null) {
            throw new RuntimeException(
                sprintf(
                    'No param has been named for the filter %s',
                    get_class($this)
                )
            );
        }

        return $this->filterParam;
    }

    public function getFilterDefault()
    {
        return $this->filterDefault;
    }

    public function getFilterLabel()
    {
        return $this->filterLabel;
    }

    public function getFilterRequired()
    {
        return $this->filterRequired;
    }

    public function getElement()
    {
        return null;
    }

    public function getInputFilterSpecification()
    {
        return null;
    }
}
