<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class EmployeeSalaryController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    /**
     * Display a listing of employees with their salaries.
     */
    public function index()
    {
        $employees = User::orderBy('name')->paginate(10);
        
        return view('employees.salary-index', compact('employees'));
    }

    /**
     * Show the form for editing an employee's salary.
     */
    public function edit($userId)
    {
        $employee = User::findOrFail($userId);
        
        return view('employees.salary-setup', compact('employee'));
    }

    /**
     * Update the specified employee's salary.
     */
    public function update(Request $request, $userId)
    {
        $employee = User::findOrFail($userId);

        $validated = $request->validate([
            'monthly_salary' => 'required|numeric|min:0',
        ]);

        $employee->update([
            'monthly_salary' => $validated['monthly_salary'],
        ]);
        // daily_rate is auto-calculated in the User model boot method

        return redirect()->route('employees.salary.index')
            ->with('success', 'Employee salary updated successfully!');
    }
}
