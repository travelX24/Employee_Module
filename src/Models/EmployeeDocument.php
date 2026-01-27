<?php

namespace Athka\Employees\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDocument extends Model
{
    use SoftDeletes;

    protected $table = 'employee_documents';

    protected $fillable = [
        'employee_id',
        'type',
        'title',
        'file_path',
        'issued_at',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}




