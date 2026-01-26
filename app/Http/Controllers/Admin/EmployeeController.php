<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Ticket\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::orderBy('employee_name', 'asc')->get();

        return Inertia::render('Admin/Employees', [
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_name' => 'required|string|max:65',
            'employee_area' => 'required|string|max:65',
            'status' => 'required|boolean',
        ]);

        Employee::create([
            'employee_name' => $request->employee_name,
            'employee_area' => $request->employee_area,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Empleado creado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_name' => 'required|string|max:65',
            'employee_area' => 'required|string|max:65',
            'status' => 'required|boolean',
        ]);

        $employee = Employee::findOrFail($id);

        $employee->update([
            'employee_name' => $request->employee_name,
            'employee_area' => $request->employee_area,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Empleado actualizado correctamente.');
    }
}
