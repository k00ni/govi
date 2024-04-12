<?php

namespace Tests;

use Test\TestCase;

use function App\cleanTitle;

class FunctionsTest extends TestCase
{
    public function testCleanTitle(): void
    {
        $this->assertEquals('Library of Congress Names', cleanTitle("Library of\n            Congress Names"));
    }
}
