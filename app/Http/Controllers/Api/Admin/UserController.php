<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function sellers()
    {
        $sellers = User::where('role', 'seller')
            ->latest()
            ->paginate(10);

        return response()->json($sellers);
    }

    public function approveSeller($id)
    {
        $seller = User::where('role', 'seller')
            ->where('id', $id)
            ->firstOrFail();

        $seller->update(['status' => 'active']);

        return response()->json([
            'message' => 'Seller approved successfully',
            'seller' => $seller
        ]);
    }

    public function rejectSeller($id)
    {
        $seller = User::where('role', 'seller')
            ->where('id', $id)
            ->firstOrFail();

        $seller->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Seller rejected successfully',
            'seller' => $seller
        ]);
    }
}
