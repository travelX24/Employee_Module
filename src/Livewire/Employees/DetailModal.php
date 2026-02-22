<?php

namespace Athka\Employees\Livewire\Employees;

use Athka\Employees\Models\Employee;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DetailModal extends Component
{
    public ?Employee $employee = null;
    public bool $show = false;
    public bool $readonly = true;

    #[On('open-employee-detail')]
    public function open($id, $readonly = true)
    {
        $companyId = (int) (Auth::user()?->saas_company_id ?? 0);

       $user = Auth::user();

        $allowed = null;

        // ✅ لو عندك restrictedBranchIds في User استخدمه
        if ($user && method_exists($user, 'restrictedBranchIds')) {
            $allowed = $user->restrictedBranchIds(); // null | [] | [ids]
        } else {
            // fallback القديم
            $allowed = DB::table('branch_user_access')
                ->where('user_id', Auth::id())
                ->where('saas_company_id', $companyId)
                ->pluck('branch_id')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            $allowed = ! empty($allowed) ? $allowed : null;
        }

        // ✅ مقيّد لكن بدون فروع => امنع
        if (is_array($allowed) && empty($allowed)) {
            abort(404);
        }

        $this->employee = Employee::query()
            ->where('saas_company_id', $companyId)
            ->when(is_array($allowed), fn ($q) => $q->whereIn('branch_id', $allowed))
            ->with(['department', 'jobTitle', 'documents', 'manager'])
            ->findOrFail($id);

        $this->readonly = $readonly;
        $this->show = true;
        $this->dispatch('open-view-employee-' . $id);
    }

    public function render()
    {
        return view('employees::livewire.employees.detail-modal');
    }
}




