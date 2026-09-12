<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    /**
     * GET /api/v1/branches
     * GET /api/v1/branches/{id}
     */
    public function index(): JsonResponse
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Branch $b): array => $this->transform($b))
            ->all();

        return response()->json([
            'success' => true,
            'data'    => $branches,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $branch = Branch::query()->find($id);

        if (! $branch) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy chi nhánh với id = {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->transform($branch),
        ]);
    }

    /**
     * Map model Branch → shape mà Flutter đang expect.
     *
     * @return array<string,mixed>
     */
    private function transform(Branch $branch): array
    {
        return [
            'id'            => $branch->id,
            'name'          => $branch->name,
            'address'       => $branch->address,
            'phone'         => $branch->phone,
            'email'         => $branch->email,
            'opening_hours' => $branch->opening_hours,
            'latitude'      => $branch->latitude !== null ? (float) $branch->latitude : null,
            'longitude'     => $branch->longitude !== null ? (float) $branch->longitude : null,
            'image'         => $branch->image,
            'description'   => $branch->description,
            'is_active'     => (bool) $branch->is_active,
        ];
    }
}
