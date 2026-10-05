<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Filter;

use Contenir\Db\QueryFilter\Filter\AbstractFilterRadio;
use Laminas\Db\Sql\Select;
use Override;

/**
 * Required radio filter with a default: status = value.
 */
final class StatusFilter extends AbstractFilterRadio
{
    protected ?string $filterParam = 'status';

    protected ?string $filterLabel = 'Status';

    protected bool $filterRequired = true;

    protected string|iterable|null $filterDefault = 'live';

    #[Override]
    public function filter(Select $query): void
    {
        $query->where->equalTo('status', $this->getFilterValue());
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function getValueOptions(): array
    {
        return ['live' => 'Live', 'draft' => 'Draft'];
    }
}
