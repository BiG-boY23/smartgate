<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleCategory;
use Illuminate\Http\Request;

class FleetAssetController extends Controller
{
    public function index()
    {
        $brands     = VehicleBrand::with('categories')->withCount('models')->orderBy('name')->get();
        $models     = VehicleModel::with('brand.categories')->orderBy('name')->get();
        $categories = VehicleCategory::orderBy('name')->get();

        return view('admin.manage.fleet', compact('brands', 'models', 'categories'));
    }

    // ─── AJAX: brands filtered by category ────────────────────────────────────
    public function brandsByCategory($categoryId)
    {
        $category = VehicleCategory::find($categoryId);
        if (!$category) return response()->json([]);
        
        $brands = $category->brands()
                           ->orderBy('vehicle_brands.name')
                           ->get(['vehicle_brands.id', 'vehicle_brands.name as name']);
        return response()->json($brands);
    }

    // ─── AJAX: models filtered by brand ───────────────────────────────────────
    public function modelsByBrand($brandId)
    {
        $models = VehicleModel::where('vehicle_brand_id', $brandId)
                              ->orderBy('name')
                              ->get(['id', 'name']);
        return response()->json($models);
    }

    // ─── Brands CRUD ──────────────────────────────────────────────────────────
    public function storeBrand(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|unique:vehicle_brands,name',
            'vehicle_category_id' => 'nullable|exists:vehicle_categories,id', // Legacy support or single-selection during creation
        ]);
        $brand = VehicleBrand::create(['name' => $request->name]);
        if ($request->filled('vehicle_category_id')) {
            $brand->categories()->sync([$request->vehicle_category_id]);
        }
        return back()->with('success', 'Vehicle brand added.');
    }

    public function updateBrand(Request $request, $id)
    {
        $brand = VehicleBrand::findOrFail($id);
        $request->validate([
            'name'                => 'required|string|unique:vehicle_brands,name,' . $id,
            'vehicle_category_id' => 'nullable|exists:vehicle_categories,id',
        ]);
        $brand->update(['name' => $request->name]);
        if ($request->filled('vehicle_category_id')) {
            $brand->categories()->sync([$request->vehicle_category_id]);
        }
        return back()->with('success', 'Vehicle brand updated.');
    }

    public function destroyBrand($id)
    {
        VehicleBrand::findOrFail($id)->delete();
        return back()->with('success', 'Vehicle brand deleted.');
    }

    // ─── Models CRUD ──────────────────────────────────────────────────────────
    public function storeModel(Request $request)
    {
        $request->validate([
            'vehicle_brand_id' => 'required|exists:vehicle_brands,id',
            'name'             => 'required|string',
        ]);
        VehicleModel::create($request->only('vehicle_brand_id', 'name'));
        return back()->with('success', 'Vehicle model added.');
    }

    public function updateModel(Request $request, $id)
    {
        $model = VehicleModel::findOrFail($id);
        $request->validate([
            'vehicle_brand_id' => 'required|exists:vehicle_brands,id',
            'name'             => 'required|string',
        ]);
        $model->update($request->only('vehicle_brand_id', 'name'));
        return back()->with('success', 'Vehicle model updated.');
    }

    public function destroyModel($id)
    {
        VehicleModel::findOrFail($id)->delete();
        return back()->with('success', 'Vehicle model deleted.');
    }

    // ─── Categories CRUD ──────────────────────────────────────────────────────
    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:vehicle_categories,name']);
        VehicleCategory::create($request->only('name', 'icon'));
        return back()->with('success', 'Vehicle category added.');
    }

    public function toggleCategory($id)
    {
        $cat = VehicleCategory::findOrFail($id);
        $cat->update(['is_active' => !$cat->is_active]);
        return back()->with('success', 'Category status toggled.');
    }
}
