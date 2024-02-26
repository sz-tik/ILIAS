<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Tests\Refinery\String;

use ILIAS\Data\Factory as DataFactory;
use ILIAS\Refinery\String\Group as StringGroup;
use ILIAS\Refinery\String\HasMinLength;
use ILIAS\Refinery\String\HasMaxLength;
use PHPUnit\Framework\TestCase;
use ILIAS\Refinery\String\MakeClickable;
use ILIAS\Language\Language;

class GroupTest extends TestCase
{
    private StringGroup $group;

    protected function setUp(): void
    {
        $dataFactory = new DataFactory();
        $language = $this->getMockBuilder(\ILIAS\Language\Language::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->group = new StringGroup($dataFactory, $language);
    }

    public function testGreaterThanInstance(): void
    {
        $instance = $this->group->hasMaxLength(42);
        $this->assertInstanceOf(HasMaxLength::class, $instance);
    }

    public function testLowerThanInstance(): void
    {
        $instance = $this->group->hasMinLength(42);
        $this->assertInstanceOf(HasMinLength::class, $instance);
    }

    public function testMakeClickable(): void
    {
        $instance = $this->group->makeClickable();
        $this->assertInstanceOf(MakeClickable::class, $instance);
    }
}
