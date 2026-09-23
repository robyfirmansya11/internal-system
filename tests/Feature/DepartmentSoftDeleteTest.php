<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_can_be_soft_deleted_and_restored(): void
    {
        $department = Department::create(['nama_department' => 'Temporary Department']);

        $department->delete();

        $this->assertSoftDeleted('departments', ['id' => $department->id]);
        $this->assertNull(Department::find($department->id));

        $trashedDepartment = Department::onlyTrashed()->findOrFail($department->id);
        $trashedDepartment->restore();

        $this->assertNotNull(Department::find($department->id));
        $this->assertNull(Department::find($department->id)->deleted_at);
    }
}
