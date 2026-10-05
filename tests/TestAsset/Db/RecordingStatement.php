<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Db;

use Override;
use PhpDb\Adapter\Driver\StatementInterface;
use PhpDb\Adapter\ParameterContainer;

/**
 * Prepared statement that logs its SQL on execution and returns fixed rows.
 */
final class RecordingStatement implements StatementInterface
{
    private ?string $sql = null;

    private ParameterContainer $parameters;

    /**
     * @param list<array<string, mixed>>|null $rows Null makes execute() return no result
     */
    public function __construct(
        private readonly SqlLog $log,
        private readonly ?array $rows,
    ) {
        $this->parameters = new ParameterContainer();
    }

    #[Override]
    public function execute(ParameterContainer|array|null $parameters = null): ?ArrayResult
    {
        $this->log->record((string) $this->sql, $this->parameters->getNamedArray());

        return null === $this->rows ? null : new ArrayResult($this->rows);
    }

    #[Override]
    public function getParameterContainer(): ParameterContainer
    {
        return $this->parameters;
    }

    #[Override]
    public function getResource(): null
    {
        return null;
    }

    #[Override]
    public function getSql(): ?string
    {
        return $this->sql;
    }

    #[Override]
    public function isPrepared(): bool
    {
        return true;
    }

    #[Override]
    public function prepare(?string $sql = null): self
    {
        return $this;
    }

    #[Override]
    public function setParameterContainer(ParameterContainer $parameterContainer): self
    {
        $this->parameters = $parameterContainer;

        return $this;
    }

    #[Override]
    public function setSql(?string $sql): self
    {
        $this->sql = $sql;

        return $this;
    }
}
