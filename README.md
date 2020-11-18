# Hierarchy

Requires at least PHP 7.1

## Usage
The constructor takes two arguments:
- `$entities` is an array of all the entities that contain the relations. The keys of this array are returned as the relational values and are used to query.
- `$branches` is an array of the keys in the entity arrays that contain the IDs for other entities. 
 
All relations are resolved upon initialisation.
 
```php
new Vosburch\Hierarchy( array $entities, array $branches );
```

To find all the related entities based on a specific branch, use the `relatedBy` method. Pass the branch key as the first argument and an array of IDs as the second. It will then return an array of all the related IDs.
```php
$hierarchy->relatedBy( string|int $branch, array $ids ): array;
```

To find all related entities based on all branches, use the `relatedTo` method. Pass an array of entity IDs as the first argument and optionally an array of branches as the second argument. 

This method will also give the initial IDs as part of the return value in addition to all IDs that these IDs are related to through the provided branches. 

If `$branches` is null, then all branches passed in the constructor will be used.

```php
$hierarchy->relatedTo( array $ids [, ?array $branches = null] ): array;
```

### Example

```php
$hierarchy = new Vosburch\Hierarchy([
    1 => ['children' => [2, 3]],
    2 => ['parents' => [1], 'children' => [4]],
    3 => ['parents' => [1]],
    4 => ['parents' => [2]],
], ['parents', 'children']);

$hierarchy->relatedBy('parents', [4]); // [1, 2]
$hierarchy->relatedBy('parents', [3]); // [1]
$hierarchy->relatedBy('children', [1]); // [2, 3, 4]

$hierarchy->relatedTo([1]); // [1, 2, 3, 4]
$hierarchy->relatedTo([2]); // [1, 2, 4]
```

### Infinite loops

There's a safety built in to prevent infinite loops from occurring. Once an entity has been resolved before, it will not resolve it again.

```php
$hierarchy = new Vosburch\Hierarchy([
    'loop1' => ['related' => ['loop2', 'loop3']],
    'loop2' => ['related' => ['loop1', 'loop3']],
    'loop3' => ['related' => ['loop1', 'loop2']],
], ['related']);

$hierarchy->relatedBy('related', ['loop1']); // ['loop2', 'loop3']

$hierarchy->relatedTo(['loop1']); // ['loop1', 'loop2', 'loop3']
```