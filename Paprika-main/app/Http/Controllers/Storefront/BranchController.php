<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function set(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'redirect' => ['nullable', 'string', 'max:2000'],
        ]);

        $branch = Branch::query()->active()->findOrFail((int) $data['branch_id']);

        $request->session()->put('active_branch_id', $branch->id);

        $redirect = $data['redirect'] ?? url()->previous();

        return redirect()->to($redirect);
    }
}
