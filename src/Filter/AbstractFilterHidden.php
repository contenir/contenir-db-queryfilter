<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

/**
 * Abstract filter without form element.
 *
 * For filters that apply programmatically without user interface.
 * Values can be set via setInput() but no form element is rendered.
 */
abstract class AbstractFilterHidden extends AbstractFilter
{
    /**
     * Returns null as hidden filters have no form element.
     *
     * @return null
     */
    public function getElement(): ?array
    {
        return null;
    }

    /**
     * Returns null as hidden filters have no input validation.
     *
     * @return null
     */
    public function getInputFilterSpecification(): ?array
    {
        return null;
    }
}
