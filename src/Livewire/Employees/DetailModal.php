<?php

namespace Athka\Employees\Livewire\Employees;

use Athka\Employees\Models\Employee;
use Livewire\Component;
use Livewire\Attributes\On;

class DetailModal extends Component
{
    public ?Employee $employee = null;
    public bool $show = false;
    public bool $readonly = true;

    #[On('open-employee-detail')]
    public function open($id, $readonly = true)
    {
        $this->employee = Employee::with(['department', 'jobTitle', 'documents', 'manager'])->findOrFail($id);
        $this->readonly = $readonly;
        $this->show = true;
        $this->dispatch('open-view-employee-' . $id);
    }

    public function render()
    {
        return view('employees::livewire.employees.detail-modal');
    }
}




