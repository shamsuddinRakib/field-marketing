<?php

namespace App\Models\backend;

use Illuminate\Database\Eloquent\Model;

class CourseClass extends Model
{
    protected $table = 'course_class';
    protected $fillable = [
        'name',
        'level',
        'slug',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_image',
    ];
    
    
}
