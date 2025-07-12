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
        $data = $request->all();

        if (array_is_list($data)) {
            // Handle bulk insert
            $created = [];
            $errors = [];

            foreach ($data as $index => $item) {
                $validator = Validator::make($item, [
                    'name' => 'required|string|unique:package_details,name',
                ]);

                if ($validator->fails()) {
                    $errors[$index] = $validator->errors();
                    continue;
                }

                $created[] = PackageDetail::create(['name' => $item['name']]);
            }

            return response()->json([
                'message' => 'Bulk insert processed.',
                'created' => $created,
                'errors'  => $errors,
            ], empty($errors) ? 201 : 207);
        }

        // Handle single insert
        $validator = Validator::make($data, [
            'name' => 'required|string|unique:package_details,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $detail = PackageDetail::create(['name' => $data['name']]);

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
            'name' => 'required|string|unique:package_details,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $detail->update(['name' => $request->name]);

        return response()->json(['message' => 'Package detail updated.', 'data' => $detail]);
    }

    public function destroy($id)
    {
        $detail = PackageDetail::findOrFail($id);
        $detail->delete();

        return response()->json(['message' => 'Package detail deleted.']);
    }
}
