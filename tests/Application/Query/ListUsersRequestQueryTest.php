<?php

namespace App\Tests\Application\Query;

use App\UI\Rest\Request\ListUsersRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ListUsersRequestQueryTest extends TestCase{
    private ValidatorInterface $validator;

    public function testCreateFromValidRequest(): void{
        // Arrange
        $request = new Request([
                                   'page'           => '2',
                                   'per_page'       => '15',
                                   'search'         => 'john',
                                   'sort_by'        => 'name',
                                   'sort_direction' => 'DESC'
                               ]);

        // Act
        $query = ListUsersRequest::fromRequest($request);

        // Assert
        $this->assertEquals(2, $query->page);
        $this->assertEquals(15, $query->perPage);
        $this->assertEquals('john', $query->searchTerm);
        $this->assertEquals('name', $query->sortBy);
        $this->assertEquals('DESC', $query->sortDirection);

        $violations = $this->validator->validate($query);
        $this->assertCount(0, $violations);
    }

    public function testDefaultValues(): void{
        // Arrange
        $request = new Request();

        // Act
        $query = ListUsersRequest::fromRequest($request);

        // Assert
        $this->assertEquals(1, $query->page);
        $this->assertEquals(10, $query->perPage);
        $this->assertNull($query->searchTerm);
        $this->assertEquals('id', $query->sortBy);
        $this->assertEquals('ASC', $query->sortDirection);
    }

    public function testValidationFailsWithInvalidPage(): void{
        // Arrange
        $request = new Request(
            [
                'page'           => '-1',
                'per_page'       => '-15',
                'sort_by'        => 'something',
                'sort_direction' => 'ESC'
            ]
        );

        // Act
        $query      = ListUsersRequest::fromRequest($request);
        $violations = $this->validator->validate($query);

        // Assert
        $this->assertCount(4, $violations);
        $this->assertEquals('page', $violations[0]->getPropertyPath());
        $this->assertEquals('perPage', $violations[1]->getPropertyPath());
        $this->assertEquals('sortBy', $violations[2]->getPropertyPath());
        $this->assertEquals('sortDirection', $violations[3]->getPropertyPath());
    }

    public function testValidationFailsWithTooLargePerPage(): void{
        // Arrange
        $request = new Request(['per_page' => '101']);

        // Act
        $query      = ListUsersRequest::fromRequest($request);
        $violations = $this->validator->validate($query);

        // Assert
        $this->assertCount(1, $violations);
        $this->assertEquals('perPage', $violations[0]->getPropertyPath());
    }

    public function testValidationFailsWithInvalidSortBy(): void{
        // Arrange
        $request = new Request(['sort_by' => 'invalid_field']);

        // Act
        $query      = ListUsersRequest::fromRequest($request);
        $violations = $this->validator->validate($query);

        // Assert
        $this->assertCount(1, $violations);
        $this->assertEquals('sortBy', $violations[0]->getPropertyPath());
    }

    public function testValidationFailsWithInvalidSortDirection(): void{
        // Arrange
        $request = new Request(['sort_direction' => 'INVALID']);

        // Act
        $query      = ListUsersRequest::fromRequest($request);
        $violations = $this->validator->validate($query);

        // Assert
        $this->assertCount(1, $violations);
        $this->assertEquals('sortDirection', $violations[0]->getPropertyPath());
    }

    protected function setUp(): void{
        $this->validator = Validation::createValidatorBuilder()
                                     ->addLoader(new AttributeLoader()) // Enable attribute support
                                     ->getValidator();
    }
}