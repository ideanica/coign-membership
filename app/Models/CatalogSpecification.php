<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogSpecification extends Model
{
    public $timestamps = false;

    protected $fillable = ['label', 'value', 'sort_order'];
}
