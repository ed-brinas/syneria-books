<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of contacts.
     */
    public function index(Request $request)
    {
        $type = $request->query('type'); // Optional filter: ?type=customer
        $search = $request->query('search'); // Optional search: ?search=keyword

        // Use withTrashed() to include disabled (soft deleted) contacts
        $query = Contact::withTrashed();

        if ($type) {
            $query->where('type', $type);
        }

        // Fetch contacts
        // Note: With encryption, we can't sort or filter via SQL LIKE efficiently. 
        // We fetch tenant's contacts and sort/filter in PHP memory.
        $contacts = $query->latest()->get(); 

        // Filter by search term if present
        if ($search) {
            $searchLower = strtolower($search);
            
            $contacts = $contacts->filter(function ($contact) use ($searchLower) {
                return str_contains(strtolower($contact->name), $searchLower) ||
                       str_contains(strtolower((string)$contact->email), $searchLower) ||
                       str_contains(strtolower((string)$contact->company_name), $searchLower) ||
                       str_contains(strtolower((string)$contact->tax_number), $searchLower);
            });
        }

        // Sort by name
        $contacts = $contacts->sortBy('name'); 

        return view('contacts.index', compact('contacts'));
    }

    /**
     * Restore the specified disabled contact.
     */
    public function restore($id)
    {
        $contact = Contact::withTrashed()->where('id', $id)->where('tenant_id', auth()->user()->tenant_id)->firstOrFail();
        
        $contact->restore();

        // Activity Log
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'action' => 'restored',
            'description' => "Restored Contact: {$contact->name}",
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('contacts.index')->with('success', 'Contact enabled/restored successfully.');
    }

    /**
     * Show the form for creating a new contact.
     */
    public function create()
    {
        return view('contacts.create');
    }

    /**
     * Store a newly created contact.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,supplier,employee',
            'tax_number' => 'required|string|max:255',
            'email' => 'nullable|email',            
        ]);

        $contact = Contact::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $request->name,
            'type' => $request->type,
            'email' => $request->email,
            'company_name' => $request->company_name,
            'tax_number' => $request->tax_number,
            'address' => $request->address,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'contact' => $contact]);
        }

        // Activity Log
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'description' => "Created Contact: {$contact->name} ({$contact->type})",
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
            'ip_address' => $request->ip(),
            'properties' => $contact->toArray(), // Removed json_encode
        ]);

        return redirect()->route('contacts.index')->with('success', 'Contact created successfully.');
    }

    /**
     * Show the form for editing the specified contact.
     */
    public function edit(Contact $contact)
    {
        // Security check: Ensure tenant owns this record
        if ($contact->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        return view('contacts.edit', compact('contact'));
    }

    /**
     * Update the specified contact.
     */
    public function update(Request $request, Contact $contact)
    {
        if ($contact->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:customer,supplier,employee',
            'tax_number' => 'required|string|max:255',            
            'email' => 'nullable|email',
        ]);

        // Note: We allow modification of master data. 
        // Historical invoices will still link to this ID, but show the new name 
        // (or you can snapshot name on invoice creation if strict history is needed).
        $contact->update([
            'name' => $request->name,
            'type' => $request->type,
            'email' => $request->email,
            'company_name' => $request->company_name,
            'tax_number' => $request->tax_number,
            'address' => $request->address,
        ]);

        // Activity Log
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'action' => 'updated',
            'description' => "Updated Contact: {$contact->name}",
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
            'ip_address' => $request->ip(),
            'properties' => [ // Removed json_encode
                'old' => $originalData,
                'new' => $contact->fresh()->toArray()
            ],
        ]);

        return redirect()->route('contacts.index')->with('success', 'Contact updated successfully.');
    }

    /**
     * Remove the specified contact (Soft Delete for Compliance).
     */
    public function destroy(Contact $contact)
    {
        if ($contact->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // We use SoftDeletes (defined in Model), which sets a 'deleted_at' timestamp.
        // The record remains in the DB for audit but is hidden from lists.
        $contact->delete();

        // Activity Log
        ActivityLog::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'action' => 'disabled',
            'description' => "Disabled/Archived Contact: {$contact->name}",
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('contacts.index')->with('success', 'Contact disabled/archived successfully.');
    }
}