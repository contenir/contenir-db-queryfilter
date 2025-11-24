<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

/**
 * Abstract filter for immutable/fixed filtering.
 *
 * For filters that always apply regardless of user input.
 * Has no query parameter and cannot be modified by users.
 */
abstract class AbstractFilterImmutable extends AbstractFilterHidden
{
    /**
     * Returns null as immutable filters have no query parameter.
     */
    public function getFilterParam(): ?string
    {
        return null;
    }
}
