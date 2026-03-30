@extends('layouts.app')

@section('title', 'Manage Fleet Assets')
@section('subtitle', 'Manage vehicle categories, brands, and models with full chain-link relationships.')

@section('content')
<div style="display: flex; flex-direction: column; gap: 2rem;">

    {{-- ── Filter & Search Control Bar ── --}}
    <div class="stat-card-premium no-print" style="padding: 1.5rem; display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: center; border-radius: 20px;">
        <div style="flex: 1; min-width: 250px;">
            <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.5rem;">Quick Search</label>
            <div style="position: relative;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" id="fleetSearch" placeholder="Search brands, models, or categories..." 
                       style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; font-weight: 700; color: #1e293b; outline: none; transition: 0.3s;"
                       onkeyup="filterFleetData()">
            </div>
        </div>
        
        <div style="width: 180px;">
            <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.5rem;">Filter by Category</label>
            <select id="categoryFilter" onchange="filterFleetData()" style="width: 100%; padding: 0.75rem; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; font-weight: 700; color: #1e293b; outline: none; cursor: pointer;">
                <option value="all">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div style="width: 180px;">
            <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.5rem;">Sort Tables By</label>
            <select id="fleetSort" onchange="filterFleetData()" style="width: 100%; padding: 0.75rem; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; font-weight: 700; color: #1e293b; outline: none; cursor: pointer;">
                <option value="name-asc">Name (A-Z)</option>
                <option value="name-desc">Name (Z-A)</option>
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
            </select>
        </div>
    </div>
    <div class="table-container">
        <div class="section-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-tag" style="font-size: 1.5rem; color: #741b1b;"></i>
                <h3>Vehicle Categories</h3>
                <span class="chain-pill">Step 1</span>
            </div>
            <button class="btn btn-primary" onclick="showAddCategoryModal()">
                <i class="ph ph-plus"></i> Add Category
            </button>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Icon</th>
                        <th>Brands Linked</th>
                        <th>Status</th>
                        <th style="text-align: right;">Visibility Toggle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                    <tr data-name="{{ strtolower($cat->name) }}" data-category="{{ strtolower($cat->name) }}" data-date="{{ $cat->created_at->timestamp }}">
                        <td style="font-weight: 800;">{{ $cat->name }}</td>
                        <td><i class="ph ph-{{ $cat->icon ?? 'car' }}" style="font-size: 1.2rem;"></i></td>
                        <td>
                            <span class="badge" style="background: #ede9fe; color: #5b21b6;">
                                {{ $cat->brands()->count() }} Brands
                            </span>
                        </td>
                        <td>
                            @if($cat->is_active)
                                <span class="badge" style="background: #dcfce7; color: #166534;">Active</span>
                            @else
                                <span class="badge" style="background: #f1f5f9; color: #94a3b8;">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <form action="{{ route('admin.manage.fleet.category.toggle', $cat->id) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-{{ $cat->is_active ? 'outline' : 'primary' }}" style="padding: 0.4rem 1rem; font-size: 0.75rem;">
                                    {{ $cat->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8;">No categories configured. Add one to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== SECTION 2: Vehicle Brands ===== --}}
    <div class="table-container">
        <div class="section-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-factory" style="font-size: 1.5rem; color: #741b1b;"></i>
                <h3>Vehicle Manufacturers / Brands</h3>
                <span class="chain-pill">Step 2 — linked to Categories</span>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <div style="position: relative; width: 220px;">
                    <i class="ph ph-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
                    <input type="text" data-type="brand" placeholder="Search brands..." 
                           style="width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.85rem;"
                           onkeyup="filterTable(this)">
                </div>
                <button class="btn btn-primary" onclick="showAddBrandModal()">
                    <i class="ph ph-plus"></i> Add Brand
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Brand Name</th>
                        <th>Category</th>
                        <th>Models Linked</th>
                        <th>Date Added</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brands as $brand)
                    <tr data-name="{{ strtolower($brand->name) }}" data-category="{{ strtolower($brand->categories->pluck('name')->join(' ')) }}" data-date="{{ $brand->created_at->timestamp }}">
                        <td style="font-weight: 800; color: #1e293b;">{{ $brand->name }}</td>
                        <td>
                            @if($brand->categories->count() > 0)
                                @foreach($brand->categories as $c)
                                    <span class="badge" style="background: #ede9fe; color: #5b21b6; margin-right: 4px; margin-bottom: 4px; display: inline-flex; align-items: center;">
                                        <i class="ph ph-{{ $c->icon ?? 'tag' }}" style="font-size: 0.8rem; margin-right:3px;"></i>
                                        {{ $c->name }}
                                    </span>
                                @endforeach
                            @else
                                <span class="badge" style="background: #f1f5f9; color: #94a3b8;">Uncategorized</span>
                            @endif
                        </td>
                        <td>{{ $brand->models_count }} Models</td>
                        <td>{{ $brand->created_at->format('M d, Y') }}</td>
                        <td style="text-align: right;">
                            <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;"
                                    onclick="showEditBrandModal({{ json_encode($brand) }})">
                                <i class="ph ph-pencil-simple"></i> Edit
                            </button>
                            <button type="button" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; color: #dc2626; border-color: #fee2e2;"
                                    onclick="confirmDeleteBrand({{ $brand->id }})">
                                <i class="ph ph-trash"></i>
                            </button>
                            <form id="delete-brand-{{ $brand->id }}" action="{{ route('admin.manage.fleet.brand.destroy', $brand->id) }}" method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8;">No brands configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== SECTION 3: Vehicle Models ===== --}}
    <div class="table-container">
        <div class="section-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-car-profile" style="font-size: 1.5rem; color: #741b1b;"></i>
                <h3>Specific Models Repository</h3>
                <span class="chain-pill">Step 3 — linked to Brands</span>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <div style="position: relative; width: 220px;">
                    <i class="ph ph-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
                    <input type="text" data-type="model" placeholder="Search models..." 
                           style="width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 0.85rem;"
                           onkeyup="filterTable(this)">
                </div>
                <button class="btn btn-primary" onclick="showAddModelModal()">
                    <i class="ph ph-plus"></i> Add Model
                </button>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Model Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Created At</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($models as $model)
                    <tr data-name="{{ strtolower($model->name) }}" data-category="{{ strtolower($model->brand->categories->pluck('name')->join(' ')) }}" data-date="{{ $model->created_at->timestamp }}">
                        <td style="font-weight: 800; color: #1e293b;">{{ $model->name }}</td>
                        <td>
                            <span class="badge" style="background: #f1f5f9; color: #475569;">{{ $model->brand->name }}</span>
                        </td>
                        <td>
                            @foreach($model->brand->categories as $c)
                                <span class="badge" style="background: #ede9fe; color: #5b21b6; margin-right: 4px;">{{ $c->name }}</span>
                            @endforeach
                            @if($model->brand->categories->isEmpty())
                                <span class="badge" style="background: #f1f5f9; color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td>{{ $model->created_at->format('M d, Y') }}</td>
                        <td style="text-align: right;">
                            <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;"
                                    onclick="showEditModelModal({{ json_encode($model) }})">
                                <i class="ph ph-pencil-simple"></i> Edit
                            </button>
                            <button type="button" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; color: #dc2626; border-color: #fee2e2;"
                                    onclick="confirmDeleteModel({{ $model->id }})">
                                <i class="ph ph-trash"></i>
                            </button>
                            <form id="delete-model-{{ $model->id }}" action="{{ route('admin.manage.fleet.model.destroy', $model->id) }}" method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8;">No models configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Hidden PHP data for JS --}}
<script>
    const ALL_BRANDS = @json($brands->map(fn($b) => ['id' => $b->id, 'name' => $b->name, 'category_ids' => $b->categories->pluck('id')]));
    const ALL_MODELS = @json($models->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'vehicle_brand_id' => $m->vehicle_brand_id]));
    const ALL_CATEGORIES = @json($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name]));
    const BRAND_BY_CAT_URL = '/api/brands/';
    const MODEL_BY_BRAND_URL = '/api/models/';
</script>
@endsection

@section('scripts')
<script>
// ─── Filter & Sort Logic ──────────────────────────────────────────────
function filterFleetData() {
    const query = document.getElementById('fleetSearch').value.toLowerCase();
    const catLimit = document.getElementById('categoryFilter').value.toLowerCase();
    const sortBy = document.getElementById('fleetSort').value;
    
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr[data-name]'));
        
        // 1. Filtering
        rows.forEach(row => {
            const name = row.getAttribute('data-name');
            const cat = row.getAttribute('data-category');
            
            const matchSearch = name.includes(query) || cat.includes(query);
            const matchCategory = catLimit === 'all' || cat.includes(catLimit);
            
            row.style.display = (matchSearch && matchCategory) ? '' : 'none';
        });
        
        // 2. Sorting
        rows.sort((a, b) => {
            const nameA = a.getAttribute('data-name');
            const nameB = b.getAttribute('data-name');
            const dateA = parseInt(a.getAttribute('data-date'));
            const dateB = parseInt(b.getAttribute('data-date'));
            
            if (sortBy === 'name-asc') return nameA.localeCompare(nameB);
            if (sortBy === 'name-desc') return nameB.localeCompare(nameA);
            if (sortBy === 'newest') return dateB - dateA;
            if (sortBy === 'oldest') return dateA - dateB;
            return 0;
        });
        
        // Render sorted rows
        rows.forEach(row => tbody.appendChild(row));
    });
}

// ─── Section Search Logic ──────────────────────────────────────────────
function filterTable(input) {
    const query = input.value.toLowerCase();
    const type = input.getAttribute('data-type'); // 'brand' or 'model'
    const table = input.closest('.table-container').querySelector('table');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        if (!name) return; // skip empty states
        row.style.display = name.includes(query) ? '' : 'none';
    });
}

// ─── Shared Chained Dropdown Helper ──────────────────────────────────────────
async function fetchBrandsByCategory(categoryId, brandSelect, modelSelect) {
    brandSelect.innerHTML = '<option value="" disabled selected>Loading brands...</option>';
    brandSelect.disabled = true;
    if (modelSelect) {
        modelSelect.innerHTML = '<option value="" disabled selected>Select Brand First…</option>';
        modelSelect.disabled = true;
    }

    if (!categoryId) {
        brandSelect.innerHTML = '<option value="" disabled selected>Select Category First…</option>';
        return;
    }

    const res = await fetch(BRAND_BY_CAT_URL + categoryId);
    const brands = await res.json();
    brandSelect.innerHTML = '<option value="" disabled selected>Select Brand…</option>';

    brands.forEach(b => {
        brandSelect.innerHTML += `<option value="${b.id}">${b.name}</option>`;
    });
    brandSelect.disabled = false;
}

async function fetchModelsByBrand(brandId, modelSelect) {
    modelSelect.innerHTML = '<option value="" disabled selected>Loading models...</option>';
    modelSelect.disabled = true;

    if (!brandId) {
        modelSelect.innerHTML = '<option value="" disabled selected>Select Brand First…</option>';
        return;
    }

    const res = await fetch(MODEL_BY_BRAND_URL + brandId);
    const models = await res.json();
    modelSelect.innerHTML = '<option value="" disabled selected>Select Model…</option>';

    models.forEach(m => {
        modelSelect.innerHTML += `<option value="${m.id}">${m.name}</option>`;
    });
    if (models.length > 0) {
        modelSelect.innerHTML += `<option value="Other">Other (Not Listed)</option>`;
    }
    modelSelect.disabled = false;
}

// ─── Category Modal ───────────────────────────────────────────────────────────
function showAddCategoryModal() {
    Swal.fire({
        title: 'Add New Category',
        html: `
            <form id="addCategoryForm" action="{{ route('admin.manage.fleet.category.store') }}" method="POST" style="text-align: left;">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Category Name <span style="color:red">*</span></label>
                    <input type="text" name="name" class="swal2-input custom-swal-input" placeholder="e.g. Car/Sedan, Motorcycle" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Icon (Phosphor Icon Name)</label>
                    <input type="text" name="icon" class="swal2-input custom-swal-input" placeholder="e.g. motorcycle, car, truck">
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Category',
        confirmButtonColor: '#741b1b',
        preConfirm: () => {
            const form = document.getElementById('addCategoryForm');
            if (!form.checkValidity()) { form.reportValidity(); return false; }
            form.submit();
        }
    });
}

// ─── Brand Modals (with Category chain) ──────────────────────────────────────
function showAddBrandModal() {
    const catOptions = ALL_CATEGORIES.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

    Swal.fire({
        title: 'Add New Brand',
        html: `
            <form id="addBrandForm" action="{{ route('admin.manage.fleet.brand.store') }}" method="POST" style="text-align: left;">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Vehicle Category <span style="color:red">*</span></label>
                    <select name="vehicle_category_id" id="modal-add-brand-cat" class="swal2-select custom-swal-input" required>
                        <option value="" disabled selected>Select Category…</option>
                        ${catOptions}
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Brand Name <span style="color:red">*</span></label>
                    <input type="text" name="name" class="swal2-input custom-swal-input" placeholder="e.g. Toyota, Honda" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Brand',
        confirmButtonColor: '#741b1b',
        preConfirm: () => {
            const form = document.getElementById('addBrandForm');
            if (!form.checkValidity()) { form.reportValidity(); return false; }
            form.submit();
        }
    });
}

function showEditBrandModal(brand) {
    const brandData = ALL_BRANDS.find(b => b.id == brand.id);
    const catIds = brandData ? brandData.category_ids : [];

    const catOptions = ALL_CATEGORIES.map(c =>
        `<option value="${c.id}" ${catIds.includes(c.id) ? 'selected' : ''}>${c.name}</option>`
    ).join('');

    Swal.fire({
        title: 'Edit Brand',
        html: `
            <form id="editBrandForm" action="{{ url('admin/manage/fleet/brand') }}/${brand.id}" method="POST" style="text-align: left;">
                @csrf
                @method('PUT')
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Vehicle Category</label>
                    <select name="vehicle_category_id" class="swal2-select custom-swal-input">
                        <option value="">— Uncategorized —</option>
                        ${catOptions}
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Brand Name <span style="color:red">*</span></label>
                    <input type="text" name="name" class="swal2-input custom-swal-input" value="${brand.name}" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update Brand',
        confirmButtonColor: '#741b1b',
        preConfirm: () => {
            const form = document.getElementById('editBrandForm');
            if (!form.checkValidity()) { form.reportValidity(); return false; }
            form.submit();
        }
    });
}

// ─── Model Modals (Category → Brand → Model chain) ───────────────────────────
function showAddModelModal() {
    const catOptions = ALL_CATEGORIES.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

    Swal.fire({
        title: 'Add New Model',
        html: `
            <form id="addModelForm" action="{{ route('admin.manage.fleet.model.store') }}" method="POST" style="text-align: left;">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Step 1 — Vehicle Category <span style="color:red">*</span></label>
                    <select id="modal-model-cat" class="swal2-select custom-swal-input" required>
                        <option value="" disabled selected>Select Category…</option>
                        ${catOptions}
                    </select>
                    <small style="color:#94a3b8; font-size:0.75rem;">This filters the brand list below.</small>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Step 2 — Manufacturer / Brand <span style="color:red">*</span></label>
                    <select name="vehicle_brand_id" id="modal-model-brand" class="swal2-select custom-swal-input" required disabled>
                        <option value="" disabled selected>Select Category First…</option>
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Step 3 — Model Name <span style="color:red">*</span></label>
                    <input type="text" name="name" class="swal2-input custom-swal-input" placeholder="e.g. Vios, Civic, Mio" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Model',
        confirmButtonColor: '#741b1b',
        didOpen: () => {
            const catSel   = document.getElementById('modal-model-cat');
            const brandSel = document.getElementById('modal-model-brand');
            catSel.addEventListener('change', () => fetchBrandsByCategory(catSel.value, brandSel, null));
        },
        preConfirm: () => {
            const form = document.getElementById('addModelForm');
            if (!form.checkValidity()) { form.reportValidity(); return false; }
            form.submit();
        }
    });
}

function showEditModelModal(model) {
    const catOptions = ALL_CATEGORIES.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

    // find current brand's category
    const brandData = ALL_BRANDS.find(b => b.id == model.vehicle_brand_id);
    const currentCatIds = brandData ? brandData.category_ids : [];
    const currentCatId = currentCatIds.length > 0 ? currentCatIds[0] : null;

    const brandOptions = ALL_BRANDS
        .filter(b => b.category_ids.includes(currentCatId))
        .map(b => `<option value="${b.id}" ${b.id == model.vehicle_brand_id ? 'selected' : ''}>${b.name}</option>`)
        .join('');

    Swal.fire({
        title: 'Edit Model',
        html: `
            <form id="editModelForm" action="{{ url('admin/manage/fleet/model') }}/${model.id}" method="POST" style="text-align: left;">
                @csrf
                @method('PUT')
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Step 1 — Vehicle Category</label>
                    <select id="edit-modal-model-cat" class="swal2-select custom-swal-input">
                        <option value="" disabled ${!currentCatId ? 'selected' : ''}>Select Category…</option>
                        ${ALL_CATEGORIES.map(c => `<option value="${c.id}" ${c.id == currentCatId ? 'selected' : ''}>${c.name}</option>`).join('')}
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Step 2 — Manufacturer / Brand <span style="color:red">*</span></label>
                    <select name="vehicle_brand_id" id="edit-modal-model-brand" class="swal2-select custom-swal-input" required ${!currentCatId ? 'disabled' : ''}>
                        <option value="" disabled ${!model.vehicle_brand_id ? 'selected' : ''}>Select Brand…</option>
                        ${brandOptions}
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label class="form-label">Step 3 — Model Name <span style="color:red">*</span></label>
                    <input type="text" name="name" class="swal2-input custom-swal-input" value="${model.name}" required>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update Model',
        confirmButtonColor: '#741b1b',
        didOpen: () => {
            const catSel   = document.getElementById('edit-modal-model-cat');
            const brandSel = document.getElementById('edit-modal-model-brand');
            catSel.addEventListener('change', () => fetchBrandsByCategory(catSel.value, brandSel, null));
        },
        preConfirm: () => {
            const form = document.getElementById('editModelForm');
            if (!form.checkValidity()) { form.reportValidity(); return false; }
            form.submit();
        }
    });
}

function confirmDeleteBrand(id) {
    Swal.fire({
        title: 'Delete Manufacturer?',
        text: "This will also delete ALL models linked to this brand! This action cannot be reversed.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#741b1b',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete brand'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-brand-' + id).submit();
        }
    });
}

function confirmDeleteModel(id) {
    Swal.fire({
        title: 'Archive Model?',
        text: "Are you sure you want to remove this specific vehicle model from the registry?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#741b1b',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, archive it'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-model-' + id).submit();
        }
    });
}
</script>

<style>
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .section-header h3 { margin: 0; font-size: 1.1rem; font-weight: 800; color: #1e293b; }
    .badge { padding: 0.25rem 0.55rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
    .custom-swal-input { width: 100% !important; margin: 0 !important; height: 38px !important; font-size: 0.9rem !important; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.3rem; color: #475569; }
    .chain-pill {
        background: linear-gradient(135deg, #741b1b, #b91c1c);
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.2rem 0.65rem;
        border-radius: 30px;
        letter-spacing: 0.03em;
    }
    small { display: block; margin-top: 4px; }
</style>
@endsection
