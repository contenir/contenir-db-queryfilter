<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter;

use Contenir\Db\QueryFilter\AbstractForm;
use Contenir\Db\QueryFilter\Filter\AbstractFilterHidden;
use Contenir\Db\QueryFilter\Filter\AbstractFilterSelect;
use Contenir\Db\QueryFilter\Filter\AbstractFilterText;
use Contenir\Db\QueryFilter\FilterSet;
use Contenir\Db\QueryFilter\Form;
use Laminas\Db\Sql\Select;
use Laminas\Filter\ToNull;
use Laminas\Form\Element;
use Laminas\InputFilter\InputFilterProviderInterface;
use PHPUnit\Framework\TestCase;

use function array_column;

/**
 * Tests for AbstractForm class.
 */
class AbstractFormTest extends TestCase
{
    private function createTextFilter(): AbstractFilterText
    {
        return new class extends AbstractFilterText {
            protected ?string $filterParam = 'search';
            protected ?string $filterLabel = 'Search';

            public function filter(Select $query): void
            {
                // No-op for testing
            }
        };
    }

    private function createSelectFilter(): AbstractFilterSelect
    {
        return new class extends AbstractFilterSelect {
            protected ?string $filterParam = 'category';
            protected ?string $filterLabel = 'Category';
            protected bool $filterRequired = true;

            public function filter(Select $query): void
            {
                // No-op for testing
            }

            public function getValueOptions(): array
            {
                return [
                    ''      => 'All Categories',
                    'books' => 'Books',
                    'music' => 'Music',
                ];
            }
        };
    }

    private function createHiddenFilter(): AbstractFilterHidden
    {
        return new class extends AbstractFilterHidden {
            protected ?string $filterParam = 'hidden_field';

            public function filter(Select $query): void
            {
                // No-op for testing
            }
        };
    }

    public function testCanCreateForm(): void
    {
        $form = new Form();
        $this->assertInstanceOf(AbstractForm::class, $form);
    }

    public function testSetFilterSetReturnsSelf(): void
    {
        $form      = new Form();
        $filterSet = new FilterSet();

        $result = $form->setFilterSet($filterSet);

        $this->assertSame($form, $result);
    }

    public function testGetFilterSetReturnsSetFilterSet(): void
    {
        $form      = new Form();
        $filterSet = new FilterSet();

        $form->setFilterSet($filterSet);

        $this->assertSame($filterSet, $form->getFilterSet());
    }

    public function testBuildAddsTextFilterElement(): void
    {
        $filterSet = new FilterSet([
            $this->createTextFilter(),
        ]);

        $form = new Form();
        $form->setFilterSet($filterSet);
        $form->build();

        $this->assertTrue($form->has('search'));
        $element = $form->get('search');
        $this->assertInstanceOf(Element\Text::class, $element);
        $this->assertEquals('Search', $element->getLabel());
    }

    public function testBuildAddsSelectFilterElement(): void
    {
        $filterSet = new FilterSet([
            $this->createSelectFilter(),
        ]);

        $form = new Form();
        $form->setFilterSet($filterSet);
        $form->build();

        $this->assertTrue($form->has('category'));
        $element = $form->get('category');
        $this->assertInstanceOf(Element\Select::class, $element);
        $this->assertEquals('Category', $element->getLabel());
    }

    public function testBuildDoesNotAddHiddenFilterElement(): void
    {
        $filterSet = new FilterSet([
            $this->createHiddenFilter(),
        ]);

        $form = new Form();
        $form->setFilterSet($filterSet);
        $form->build();

        $this->assertFalse($form->has('hidden_field'));
    }

    public function testBuildAddsMultipleElements(): void
    {
        $filterSet = new FilterSet([
            $this->createTextFilter(),
            $this->createSelectFilter(),
            $this->createHiddenFilter(),
        ]);

        $form = new Form();
        $form->setFilterSet($filterSet);
        $form->build();

        $this->assertTrue($form->has('search'));
        $this->assertTrue($form->has('category'));
        $this->assertFalse($form->has('hidden_field'));
    }

    public function testGetInputFilterSpecificationReturnsEmptyArrayBeforeBuild(): void
    {
        $form = new Form();
        $spec = $form->getInputFilterSpecification();

        $this->assertIsArray($spec);
        $this->assertEmpty($spec);
    }

    public function testGetInputFilterSpecificationReturnsSpecsAfterBuild(): void
    {
        $filterSet = new FilterSet([
            $this->createTextFilter(),
        ]);

        $form = new Form();
        $form->setFilterSet($filterSet);
        $form->build();

        $spec = $form->getInputFilterSpecification();

        $this->assertIsArray($spec);
        $this->assertArrayHasKey('search', $spec);
        $this->assertFalse($spec['search']['required']);
        $this->assertIsArray($spec['search']['filters']);
    }

    public function testInputFilterSpecificationContainsToNullFilter(): void
    {
        $filterSet = new FilterSet([
            $this->createTextFilter(),
        ]);

        $form = new Form();
        $form->setFilterSet($filterSet);
        $form->build();

        $spec = $form->getInputFilterSpecification();

        $this->assertArrayHasKey('search', $spec);
        $this->assertArrayHasKey('filters', $spec['search']);

        $filterNames = array_column($spec['search']['filters'], 'name');
        $this->assertContains(ToNull::class, $filterNames);
    }

    public function testSelectFilterInputSpecificationRespectsRequiredFlag(): void
    {
        $filterSet = new FilterSet([
            $this->createSelectFilter(),
        ]);

        $form = new Form();
        $form->setFilterSet($filterSet);
        $form->build();

        $spec = $form->getInputFilterSpecification();

        $this->assertArrayHasKey('category', $spec);
        $this->assertTrue($spec['category']['required']);
    }

    public function testHiddenFilterDoesNotAddInputFilterSpec(): void
    {
        $filterSet = new FilterSet([
            $this->createHiddenFilter(),
        ]);

        $form = new Form();
        $form->setFilterSet($filterSet);
        $form->build();

        $spec = $form->getInputFilterSpecification();

        $this->assertArrayNotHasKey('hidden_field', $spec);
    }

    public function testSelectElementHasValueOptions(): void
    {
        $filterSet = new FilterSet([
            $this->createSelectFilter(),
        ]);

        $form = new Form();
        $form->setFilterSet($filterSet);
        $form->build();

        /** @var Element\Select $element */
        $element      = $form->get('category');
        $valueOptions = $element->getValueOptions();

        $this->assertIsArray($valueOptions);
        $this->assertArrayHasKey('books', $valueOptions);
        $this->assertArrayHasKey('music', $valueOptions);
        $this->assertEquals('Books', $valueOptions['books']);
    }

    public function testFormImplementsInputFilterProviderInterface(): void
    {
        $form = new Form();
        $this->assertInstanceOf(InputFilterProviderInterface::class, $form);
    }
}
