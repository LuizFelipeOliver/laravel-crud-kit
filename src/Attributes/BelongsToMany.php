<?php

namespace Example\LaravelCrudKit\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class BelongsToMany extends RelationAttribute
{
}
