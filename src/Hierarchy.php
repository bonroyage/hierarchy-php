<?php namespace Vosburch;

use InvalidArgumentException;

class Hierarchy
{

    private $entries = [];
    private $branches = [];

    public function __construct(array $entries, array $branches)
    {
        foreach ($branches as $idx => $branch) {
            if (!is_scalar($branch)) {
                throw new InvalidArgumentException(sprintf("Branch at index '%s' is not a scalar value", $idx));
            }

            $this->branches[$branch] = [];
        }

        foreach ($entries as $id => $entry) {
            foreach ($branches as $branch) {
                if (!isset($entry[$branch])) {
                    $entry[$branch] = [];
                } elseif (!is_array($entry[$branch])) {
                    $entry[$branch] = [$entry[$branch]];
                }
            }

            $this->entries[$id] = $entry;
        }

        foreach ($this->entries as $id => $entry) {
            foreach ($branches as $branch) {
                $this->branches[$branch][$id] = $this->loopBranch($branch, $id, $id);
            }
        }
    }

    /**
     * Get all related entities of a set of IDs by a specific branch
     *
     * @param $branch
     * @param $ids
     * @return array
     */
    public function relatedBy($branch, array $ids)
    {
        if (!is_scalar($branch)) {
            throw new InvalidArgumentException("Branch is not a scalar value");
        }

        if (!array_key_exists($branch, $this->branches)) {
            throw new InvalidArgumentException(sprintf("Branch '%s' does not exist", $branch));
        }

        return array_values(array_unique($this->flatten($this->only($this->branches[$branch], $ids))));
    }

    /**
     * Get all related entities (including itself) of a set of IDs
     *
     * @param string[]|int[] $ids
     * @param array|null $branches
     * @return array
     */
    public function relatedTo(array $ids, ?array $branches = null)
    {
        if (is_null($branches)) {
            $branches = array_keys($this->branches);
        }

        return array_values(array_unique(array_reduce($branches, function (array $carry, $branch) use ($ids) {
            return array_merge($carry, $this->relatedBy($branch, $ids));
        }, $ids)));
    }

    /**
     * Loop over a branch to get all related IDs of that branch.
     * If you start with entity 1, it has relations in 'parent' of 2 and 3,
     * it will then look through the 'parent' relations of entities 2 and 3,
     * and keep going until it has retrieved every single 'parent'.
     *
     * This function will never return itself.
     *
     * @param $branch
     * @param $currentId
     * @param $originalId
     * @param array|null $data
     * @return array
     */
    private function loopBranch($branch, $currentId, $originalId, array $data = null)
    {

        $entry = $this->entries[$currentId];

        /*
         * Make sure we're not going into an endless loop. If the ID of a related
         * entity is equal to the originally passed ID there is no point looping
         * over that entity again.
         */
        $data[$currentId] = array_filter($entry[$branch], function ($relatedId) use ($originalId, $currentId) {
            return $relatedId !== $originalId && $relatedId !== $currentId;
        });

        foreach ($entry[$branch] as $relatedId) {
            if (!array_key_exists($relatedId, $data) && array_key_exists($relatedId, $this->entries)) {
                $data[$relatedId] = $this->loopBranch($branch, $relatedId, $originalId, $data);
            }
        }

        return $data;
    }

    /**
     * @param array $array
     * @param $depth
     * @return array
     * @see \Illuminate\Support\Arr
     */
    private function flatten(array $array, $depth = INF)
    {
        $result = [];

        foreach ($array as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1 ? array_values($item) : $this->flatten($item, $depth - 1);

                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $array
     * @param array $keys
     * @return array
     * @see \Illuminate\Support\Arr
     */
    private function only(array $array, array $keys)
    {
        return array_intersect_key($array, array_flip($keys));
    }

}