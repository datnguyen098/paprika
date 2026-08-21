<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Contact;
use App\Support\BranchAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $contacts = Contact::query()
            ->with('branch')
            ->tap(fn ($query) => BranchAccess::apply($query, $request->user()))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $query->where(function ($query) use ($request): void {
                    $query->where('name', 'like', '%'.$request->q.'%')
                        ->orWhere('phone', 'like', '%'.$request->q.'%')
                        ->orWhere('email', 'like', '%'.$request->q.'%');
                });
            })
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->branch_id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $branches = Branch::query()
            ->active()
            ->when($request->user()?->branch_id, fn ($query) => $query->where('id', $request->user()->branch_id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.contacts.index', compact('contacts', 'branches'));
    }

    public function show(Contact $contact): View
    {
        $contact->load('branch');
        BranchAccess::authorize(auth()->user(), $contact->branch_id);

        if ($contact->status === 'new') {
            $contact->update(['status' => 'read']);
        }

        return view('admin.contacts.show', compact('contact'));
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        BranchAccess::authorize($request->user(), $contact->branch_id);

        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'read', 'processed'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $contact->update($data);

        return redirect()->route('admin.contacts.show', $contact)->with('success', 'Đã cập nhật liên hệ.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        BranchAccess::authorize(auth()->user(), $contact->branch_id);

        $contact->delete();

        return redirect()->route('admin.contacts.index')->with('success', 'Đã xóa liên hệ.');
    }
}
