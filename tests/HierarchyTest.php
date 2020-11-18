<?php

use PHPUnit\Framework\TestCase;
use Vosburch\Hierarchy;

class HierarchyTest extends TestCase
{

    public function testInstance()
    {
        $entries = [
            4 => [],
            5 => [],
            6 => ['ancestor' => 8],
            7 => ['ancestor' => 8],
            8 => ['descendants' => [6, 7], 'ancestor' => 1],

            // These 3 form an endless loop, calling any 1 of these 3 should always return the other 2
            'loop1' => ['loop-relation' => ['loop2', 'loop3']],
            'loop2' => ['loop-relation' => ['loop3', 'loop1']],
            'loop3' => ['loop-relation' => ['loop2']],
        ];

        $hierarchy = new Hierarchy($entries, ['ancestor', 'descendants', 'loop-relation']);
        $this->assertInstanceOf(Hierarchy::class, $hierarchy);

        return $hierarchy;
    }

    /**
     * @depends testInstance
     *
     * @param Hierarchy $hierarchy
     */
    public function testAncestors(Hierarchy $hierarchy)
    {
        $this->assertEqualsCanonicalizing([], $hierarchy->relatedBy('ancestor', [1]));
        $this->assertEqualsCanonicalizing([], $hierarchy->relatedBy('ancestor', [4]));
        $this->assertEqualsCanonicalizing([], $hierarchy->relatedBy('ancestor', [5]));
        $this->assertEqualsCanonicalizing([1, 8], $hierarchy->relatedBy('ancestor', [6]));
        $this->assertEqualsCanonicalizing([1, 8], $hierarchy->relatedBy('ancestor', [7]));
        $this->assertEqualsCanonicalizing([1], $hierarchy->relatedBy('ancestor', [8]));
    }

    /**
     * @depends testInstance
     *
     * @param Hierarchy $hierarchy
     */
    public function testDescendants(Hierarchy $hierarchy)
    {
        $this->assertEqualsCanonicalizing([], $hierarchy->relatedBy('descendants', [1]));
        $this->assertEqualsCanonicalizing([], $hierarchy->relatedBy('descendants', [4]));
        $this->assertEqualsCanonicalizing([], $hierarchy->relatedBy('descendants', [5]));
        $this->assertEqualsCanonicalizing([], $hierarchy->relatedBy('descendants', [6]));
        $this->assertEqualsCanonicalizing([], $hierarchy->relatedBy('descendants', [7]));
        $this->assertEqualsCanonicalizing([6, 7], $hierarchy->relatedBy('descendants', [8]));
    }

    /**
     * @depends testInstance
     *
     * @param Hierarchy $hierarchy
     */
    public function testLoopRelation(Hierarchy $hierarchy)
    {
        $this->assertEqualsCanonicalizing([], $hierarchy->relatedBy('loop-relation', [1]));

        $this->assertEqualsCanonicalizing(['loop2', 'loop3'], $hierarchy->relatedBy('loop-relation', ['loop1']));
        $this->assertEqualsCanonicalizing(['loop1', 'loop3'], $hierarchy->relatedBy('loop-relation', ['loop2']));
        $this->assertEqualsCanonicalizing(['loop1', 'loop2'], $hierarchy->relatedBy('loop-relation', ['loop3']));
    }

    /**
     * @depends testInstance
     *
     * @param Hierarchy $hierarchy
     */
    public function testNonScalarBranch(Hierarchy $hierarchy)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Branch is not a scalar value");
        $hierarchy->relatedBy(['branch-1', 'branch-2'], [1]);
    }

    /**
     * @depends testInstance
     *
     * @param Hierarchy $hierarchy
     */
    public function testNonExistentBranch(Hierarchy $hierarchy)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Branch 'fake-branch' does not exist");
        $hierarchy->relatedBy('fake-branch', [1]);
    }

    /**
     * @depends testInstance
     *
     * @param Hierarchy $hierarchy
     */
    public function testRelatedTo(Hierarchy $hierarchy)
    {
        $this->assertEqualsCanonicalizing([1], $hierarchy->relatedTo([1]));
        $this->assertEqualsCanonicalizing([4], $hierarchy->relatedTo([4]));
        $this->assertEqualsCanonicalizing([5], $hierarchy->relatedTo([5]));
        $this->assertEqualsCanonicalizing([1, 6, 8], $hierarchy->relatedTo([6]));
        $this->assertEqualsCanonicalizing([1, 7, 8], $hierarchy->relatedTo([7]));
        $this->assertEqualsCanonicalizing([1, 6, 7, 8], $hierarchy->relatedTo([8]));

        $this->assertEqualsCanonicalizing(['loop1', 'loop2', 'loop3'], $hierarchy->relatedTo(['loop1']));
        $this->assertEqualsCanonicalizing(['loop1', 'loop2', 'loop3'], $hierarchy->relatedTo(['loop2']));
        $this->assertEqualsCanonicalizing(['loop1', 'loop2', 'loop3'], $hierarchy->relatedTo(['loop3']));
    }

}