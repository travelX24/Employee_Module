<?php

namespace Athka\Employees\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class EmployeeStatusLog extends Model
{
    protected $table = 'employee_status_logs';

    protected $fillable = [
        'saas_company_id',
        'employee_id',
        'performer_id',
        'action_type',
        'effective_date',
        'reason',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performer_id');
    }
}
