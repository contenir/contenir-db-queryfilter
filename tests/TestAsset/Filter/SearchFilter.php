<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Filter;

use Contenir\Db\QueryFilter\Filter\AbstractFilterText;
use Override;
use PhpDb\Sql\Select;

use function is_string;

/**
 * Text filter: name LIKE %value%.
 */
final class SearchFilter extends AbstractFilterText
{
    protected ?string $filterParam = 'search';

    protected ?string $filterLabel = 'Search';

    /** @var array<string, mixed>|null */
    protected ?array $filterAttributes = ['placeholder' => 'Find a product'];

    #[Override]
    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if (! is_string($value) || '' === $value) {
            return;
        }

        $query->where->like('name', "%{$value}%");
    }
}
