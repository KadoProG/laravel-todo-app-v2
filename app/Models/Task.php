<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'description',
        'is_done',
        'parent_id',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<string>
     */
    protected $appends = ['isDone', 'parentId'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_done' => 'boolean',
        'parent_id' => 'integer',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_done' => false,
    ];

    // isDoneの値を保存するときにis_doneカラムに変換するミューテータ
    public function setIsDoneAttribute($value)
    {
        $this->attributes['is_done'] = $value;
    }

    // parentIdの値を保存するときにparent_idカラムに変換するミューテータ
    public function setParentIdAttribute($value)
    {
        $this->attributes['parent_id'] = $value;
    }

    // 子Taskを取得するリレーション
    public function children()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    // 親Taskを取得するリレーション
    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }
}
