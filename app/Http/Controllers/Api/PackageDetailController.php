<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PackageDetail;
use Illuminate\Support\Facades\Validator;

class PackageDetailController extends Controller
{
    public function index()
    {
        return response()->json(PackageDetail::all());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|unique:package_details,name',
            'value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $detail = PackageDetail::create($request->only('name', 'value'));

        return response()->json(['message' => 'Package detail added.', 'data' => $detail], 201);
    }

    public function show($id)
    {
        return response()->json(PackageDetail::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $detail = PackageDetail::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'  => 'sometimes|string|unique:package_details,name,' . $id,
            'value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $detail->update($request->only('name', 'value'));

        return response()->json(['message' => 'Package detail updated.', 'data' => $detail]);
    }

    public function destroy($id)
    {
        $detail = PackageDetail::findOrFail($id);
        $detail->delete();

        return response()->json(['message' => 'Package detail deleted.']);
    }
}
