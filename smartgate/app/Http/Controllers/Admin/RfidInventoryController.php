<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RfidTag;
use Illuminate\Http\Request;

class RfidInventoryController extends Controller
{
    public function index()
    {
        $tags = RfidTag::orderByDesc('created_at')->get();
        return view('admin.manage.rfid-inventory', compact('tags'));
    }

    public function store(Request $request)
    {
        $request->validate(['tag_id' => 'required|string|unique:rfid_tags,tag_id']);
        RfidTag::create($request->all());
        return back()->with('success', 'RFID Tag added to inventory.');
    }

    public function destroy($id)
    {
        RfidTag::findOrFail($id)->delete();
        return back()->with('success', 'Tag removed from inventory.');
    }
}
