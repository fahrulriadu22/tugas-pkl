<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class AttendanceExport implements FromQuery, WithHeadings
{
    use Exportable;

    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Attendance::query()->with('user');

        if (!empty($this->filters['month'])) {
            $query->whereMonth('date', $this->filters['month']);
        }

        if (!empty($this->filters['year'])) {
            $query->whereYear('date', $this->filters['year']);
        }

        if (!empty($this->filters['employee_id'])) {
            $query->where('user_id', $this->filters['employee_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->select(
            'user_id',
            'date',
            'check_in',
            'check_out',
            'status'
        );
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Date',
            'Check In',
            'Check Out',
            'Status',
        ];
    }
}
