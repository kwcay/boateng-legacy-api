<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Definition;
use App\Models\Definitions\Word;
use App\Models\Definitions\Expression;
use App\Models\Definitions\Story;

class DefinitionTest extends TestCase
{
    /**
     *
     */
    public function testTypes()
    {
        $this->assertEquals(Definition::TYPE_WORD, Definition::getTypeConstant((new Word)->type));
        $this->assertEquals(Definition::TYPE_EXPRESSION, Definition::getTypeConstant((new Expression)->type));

    }
}
