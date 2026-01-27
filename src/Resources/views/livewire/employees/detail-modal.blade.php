<div>
    @if($show && $employee)
        @include('employees::livewire.employees.components.view-employee-modal', [
            'employee' => $employee,
            'readonly' => true,
        ])
    @endif
</div>




