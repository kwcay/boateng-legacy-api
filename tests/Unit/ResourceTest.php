<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Language;
use App\Models\Definition;

class ResourceTest extends TestCase
{
    /**
     * Tests that hidden attributes never show up in responses.
     *
     * @param  array $resourceAttributes
     * @param  array $hiddenAttributes
     * @dataProvider getTestHiddenAttributesDataProvider()
     */
    public function testHiddenAttributes($resourceAttributes, $hiddenAttributes)
    {
        $this->markTestIncomplete('TODO: Fix factories.');

        $this->assertEmpty(array_intersect($resourceAttributes, $hiddenAttributes));
    }

    /**
     * @return array
     */
    public function getTestHiddenAttributesDataProvider()
    {
        return [
            'Definition' => [
                factory(Definition::class)->make()->toArray(),
                ['id']
            ],
            'Language' => [
                factory(Language::class)->make()->toArray(),
                ['id', 'name']
            ],
        ];
    }
}
