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
        'expired_at' => 'datetime',
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

    public function actions()
    {
        return $this->hasMany(TaskAction::class);
    }

    public function scopeFilter($query, $filters)
    {
        return $query
            ->when(isset($filters['is_public']), fn ($q) => $q->where('is_public', $filters['is_public']))
            ->when(isset($filters['is_done']), fn ($q) => $q->where('is_done', $filters['is_done']))
            ->when(isset($filters['expired_before']), fn ($q) => $q->where('expired_at', '<=', $filters['expired_before']))
            ->when(isset($filters['expired_after']), fn ($q) => $q->where('expired_at', '>=', $filters['expired_after']))
            ->when(isset($filters['created_user_id']), fn ($q) => $q->where('created_user_id', $filters['created_user_id']))
            ->when(! empty($filters['created_user_ids']), fn ($q) => $q->whereIn('created_user_id', $filters['created_user_ids']))
            ->when(isset($filters['assigned_user_id']), function ($q) use ($filters) {
                $q->whereHas('assignedUsers', function ($q2) use ($filters) {
                    $q2->where('users.id', $filters['assigned_user_id']);
                });
            })
            ->when(! empty($filters['assigned_user_ids']), function ($q) use ($filters) {
                $q->whereHas('assignedUsers', function ($q2) use ($filters) {
                    $q2->whereIn('users.id', $filters['assigned_user_ids']);
                });
            });
    }
}
