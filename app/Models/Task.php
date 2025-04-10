<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory,  SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'description',
        'is_public',
        'is_done',
        'expired_at',
        'created_user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_done' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_done' => false,
        'is_public' => false,
    ];

    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'task_assigned_users', 'task_id', 'user_id')->withTimestamps();
    }
}
