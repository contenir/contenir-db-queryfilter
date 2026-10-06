<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Contenir\Db\Model\Exception\MappingException;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Metadata\AttributeMetadataFactory;
use ReflectionClass;
use ReflectionProperty;

/**
 * Reads a primary-key column's value from the entity given to
 * {@see AbstractQueryFilter::getPosition()}.
 *
 * A contenir-db-model 2 entity (a class carrying #[Table]) is read through
 * the property its mapping gives the column, so `resource_id` reads
 * `$resourceId`, whatever the property's visibility. Any other object, such
 * as a row cast from an array, and a mapped entity whose mapping does not
 * name the column, are read by the property named like the column.
 *
 * @internal
 */
final readonly class EntityKey
{
    /**
     * @param string $column Primary-key column name
     *
     * @throws MappingException If the entity carries #[Table] but is not a valid contenir-db-model entity.
     *
     * @mago-expect analysis:unhandled-thrown-type Reflects the entity and a property its mapping names.
     * @mago-expect analysis:string-member-selector The column names the property of an unmapped object.
     * @mago-expect analysis:ambiguous-object-property-access The column names the property of an unmapped object.
     */
    public static function read(object $entity, string $column): mixed
    {
        $class = new ReflectionClass($entity);
        if ([] !== $class->getAttributes(Table::class)) {
            $metadata = (new AttributeMetadataFactory())->getMetadataFor($class->getName());
            if ($metadata->hasColumn($column)) {
                $property = $metadata->getFieldForColumn($column)->propertyName;

                return (new ReflectionProperty($entity, $property))->getValue($entity);
            }
        }

        return $entity->{$column};
    }
}
