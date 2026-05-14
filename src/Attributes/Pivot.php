<?php

namespace Example\LaravelCrudKit\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Pivot extends RelationAttribute
{
}
