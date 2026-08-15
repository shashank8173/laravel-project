<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyRelationship extends Model
{
    protected $table = 'hrm_family_relationship_member';

    public $timestamps = false;

    protected $fillable = ['name'];
}
