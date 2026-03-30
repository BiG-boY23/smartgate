@extends('layouts.app')

@section('title', 'Manage Academic Data')
@section('subtitle', 'Manage colleges, departments, and courses to ensure registration forms remain current.')

@section('content')
<div style="display: flex; flex-direction: column; gap: 2rem;">

    {{-- ── Filter & Search Control Bar ── --}}
    <div class="stat-card-premium no-print" style="padding: 1.5rem; display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: center; border-radius: 20px;">
        <div style="flex: 1; min-width: 250px;">
            <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.5rem;">Quick Search</label>
            <div style="position: relative;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" id="academicSearch" placeholder="Search colleges, departments, or programs..." 
                       style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; font-weight: 700; color: #1e293b; outline: none; transition: 0.3s;"
                       onkeyup="filterAcademicData()">
            </div>
        </div>

        <div style="width: 220px;">
            <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.5rem;">Sort Grid By</label>
            <select id="academicSort" onchange="filterAcademicData()" style="width: 100%; padding: 0.75rem; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; font-weight: 700; color: #1e293b; outline: none; cursor: pointer;">
                <option value="name-asc">Name (A-Z)</option>
                <option value="name-desc">Name (Z-A)</option>
                <option value="newest">Newest Added</option>
            </select>
        </div>
    </div>
    
    <!-- Section 1: Colleges / Departments -->
    <div class="table-container">
        <div class="section-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-buildings" style="font-size: 1.5rem; color: #741b1b;"></i>
                <h3>Universities / Colleges / Departments</h3>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <div style="position: relative; width: 220px;">
                    <i class="ph ph-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
                    <input type="text" data-type="college" placeholder="Search colleges..." 
                           style="width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.85rem;"
                           onkeyup="filterSectionTable(this)">
                </div>
                <button class="btn btn-primary" onclick="showAddCollegeModal()">
                    <i class="ph ph-plus"></i> Add College
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>College Name</th>
                        <th>Code</th>
                        <th>Courses Registered</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($colleges as $college)
                    <tr data-name="{{ strtolower($college->name) }}" data-code="{{ strtolower($college->code) }}" data-date="{{ $college->created_at->timestamp }}">
                        <td style="font-weight: 800; color: #1e293b;">{{ $college->name }}</td>
                        <td><span class="badge" style="background: #f1f5f9; color: #475569;">{{ $college->code ?? 'N/A' }}</span></td>
                        <td>{{ $college->courses_count }} Programs</td>
                        <td style="text-align: right;">
                            <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" 
                                    onclick="showEditCollegeModal({{ json_encode($college) }})">
                                <i class="ph ph-pencil-simple"></i> Edit
                            </button>
                            <button type="button" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; color: #dc2626; border-color: #fee2e2;" 
                                    onclick="confirmDeleteCollege({{ $college->id }})">
                                <i class="ph ph-trash"></i>
                            </button>
                            <form id="delete-college-{{ $college->id }}" action="{{ route('admin.manage.academic.college.destroy', $college->id) }}" method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align: center; color: #94a3b8;">No colleges configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Courses / Programs -->
    <div class="table-container">
        <div class="section-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-graduation-cap" style="font-size: 1.5rem; color: #741b1b;"></i>
                <h3>Degree Programs / Courses</h3>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <div style="position: relative; width: 220px;">
                    <i class="ph ph-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
                    <input type="text" data-type="course" placeholder="Search courses..." 
                           style="width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.85rem;"
                           onkeyup="filterSectionTable(this)">
                </div>
                <button class="btn btn-primary" onclick="showAddCourseModal()">
                    <i class="ph ph-plus"></i> Add Course
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Course / Program Name</th>
                        <th>Code</th>
                        <th>Parent College</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $course)
                    <tr data-name="{{ strtolower($course->name) }}" data-code="{{ strtolower($course->code ?? '') }}" data-parent="{{ strtolower($course->college->name) }}" data-date="{{ $course->created_at->timestamp }}">
                        <td style="font-weight: 800; color: #1e293b;">{{ $course->name }}</td>
                        <td><span class="badge" style="background: #f1f5f9; color: #475569;">{{ $course->code ?? 'N/A' }}</span></td>
                        <td>{{ $course->college->name }}</td>
                        <td style="text-align: right;">
                            <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" 
                                    onclick="showEditCourseModal({{ json_encode($course) }})">
                                <i class="ph ph-pencil-simple"></i> Edit
                            </button>
                            <button type="button" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; color: #dc2626; border-color: #fee2e2;"
                                    onclick="confirmDeleteCourse({{ $course->id }})">
                                <i class="ph ph-trash"></i>
                            </button>
                            <form id="delete-course-{{ $course->id }}" action="{{ route('admin.manage.academic.course.destroy', $course->id) }}" method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align: center; color: #94a3b8;">No courses configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    /** ── Unified Filtering & Sorting Engine ── **/
    function filterAcademicData() {
        const query = document.getElementById('academicSearch').value.toLowerCase();
        const sort = document.getElementById('academicSort').value;
        const allRows = document.querySelectorAll('tbody tr[data-name]');

        allRows.forEach(row => {
            const name = row.getAttribute('data-name');
            const code = row.getAttribute('data-code');
            const parent = row.getAttribute('data-parent') || ''; // Only for courses
            
            const matches = name.includes(query) || code.includes(query) || parent.includes(query);
            row.style.display = matches ? '' : 'none';
        });

        // Trigger sort for each table
        sortTables(sort);
    }

    function filterSectionTable(input) {
        const query = input.value.toLowerCase();
        const type = input.getAttribute('data-type');
        const rows = input.closest('.table-container').querySelectorAll('tbody tr[data-name]');

        rows.forEach(row => {
            const name = row.getAttribute('data-name');
            const code = row.getAttribute('data-code');
            const matches = name.includes(query) || code.includes(query);
            row.style.display = matches ? '' : 'none';
        });
    }

    function sortTables(criteria) {
        const tables = document.querySelectorAll('.table-wrapper table');
        
        tables.forEach(table => {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr[data-name]'));

            rows.sort((a, b) => {
                const nameA = a.getAttribute('data-name');
                const nameB = b.getAttribute('data-name');
                const dateA = parseInt(a.getAttribute('data-date'));
                const dateB = parseInt(b.getAttribute('data-date'));

                if (criteria === 'name-asc') return nameA.localeCompare(nameB);
                if (criteria === 'name-desc') return nameB.localeCompare(nameA);
                if (criteria === 'newest') return dateB - dateA;
                return 0;
            });

            rows.forEach(row => tbody.appendChild(row));
        });
    }

    function showAddCollegeModal() {
        Swal.fire({
            title: 'Add New College/Dept',
            html: `
                <form id="addCollegeForm" action="{{ route('admin.manage.academic.college.store') }}" method="POST" style="text-align: left;">
                    @csrf
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">College Name</label>
                        <input type="text" name="name" class="swal2-input custom-swal-input" placeholder="e.g. College of Computing" required>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">College Code</label>
                        <input type="text" name="code" class="swal2-input custom-swal-input" placeholder="e.g. COC">
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Add College',
            confirmButtonColor: '#741b1b',
            preConfirm: () => {
                const form = document.getElementById('addCollegeForm');
                if (!form.checkValidity()) { form.reportValidity(); return false; }
                form.submit();
            }
        });
    }

    function showEditCollegeModal(college) {
        Swal.fire({
            title: 'Edit College',
            html: `
                <form id="editCollegeForm" action="{{ url('admin/manage/academic/college') }}/${college.id}" method="POST" style="text-align: left;">
                    @csrf
                    @method('PUT')
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">College Name</label>
                        <input type="text" name="name" class="swal2-input custom-swal-input" value="${college.name}" required>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">College Code</label>
                        <input type="text" name="code" class="swal2-input custom-swal-input" value="${college.code || ''}">
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update College',
            confirmButtonColor: '#741b1b',
            preConfirm: () => {
                const form = document.getElementById('editCollegeForm');
                if (!form.checkValidity()) { form.reportValidity(); return false; }
                form.submit();
            }
        });
    }

    function showAddCourseModal() {
        Swal.fire({
            title: 'Add New Course Program',
            html: `
                <form id="addCourseForm" action="{{ route('admin.manage.academic.course.store') }}" method="POST" style="text-align: left;">
                    @csrf
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Select Parent College</label>
                        <select name="college_id" class="swal2-select custom-swal-input" required>
                            @foreach($colleges as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Course Name</label>
                        <input type="text" name="name" class="swal2-input custom-swal-input" placeholder="e.g. BS in Information Technology" required>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Course Code</label>
                        <input type="text" name="code" class="swal2-input custom-swal-input" placeholder="e.g. BSIT">
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Add Course',
            confirmButtonColor: '#741b1b',
            preConfirm: () => {
                const form = document.getElementById('addCourseForm');
                if (!form.checkValidity()) { form.reportValidity(); return false; }
                form.submit();
            }
        });
    }

    function showEditCourseModal(course) {
        Swal.fire({
            title: 'Edit Course Program',
            html: `
                <form id="editCourseForm" action="{{ url('admin/manage/academic/course') }}/${course.id}" method="POST" style="text-align: left;">
                    @csrf
                    @method('PUT')
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Select Parent College</label>
                        <select name="college_id" class="swal2-select custom-swal-input" required>
                            @foreach($colleges as $c)
                                <option value="{{ $c->id }}" ${course.college_id == {{ $c->id }} ? 'selected' : ''}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Course Name</label>
                        <input type="text" name="name" class="swal2-input custom-swal-input" value="${course.name}" required>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label class="form-label">Course Code</label>
                        <input type="text" name="code" class="swal2-input custom-swal-input" value="${course.code || ''}">
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update Course',
            confirmButtonColor: '#741b1b',
            preConfirm: () => {
                const form = document.getElementById('editCourseForm');
                if (!form.checkValidity()) { form.reportValidity(); return false; }
                form.submit();
            }
        });
    }

    function confirmDeleteCollege(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Deleting this college will also delete all associated programs and courses. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#741b1b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, purge it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-college-' + id).submit();
            }
        });
    }

    function confirmDeleteCourse(id) {
        Swal.fire({
            title: 'Remove Program?',
            text: "Are you sure you want to delete this course program from the registry?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#741b1b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, remove it'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-course-' + id).submit();
            }
        });
    }
</script>

<style>
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .section-header h3 { margin: 0; font-size: 1.1rem; font-weight: 800; color: #1e293b; }
    .badge { padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
    .custom-swal-input { width: 100% !important; margin: 0 !important; height: 38px !important; font-size: 0.9rem !important; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.3rem; color: #475569; }
</style>
@endsection
