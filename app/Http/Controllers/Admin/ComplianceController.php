<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Compliance;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ComplianceController extends Controller
{
    public function index()
    {
        $compliance = Compliance::orderBy('sort_order')->first();

        if (!$compliance) {
            $compliance = Compliance::create([
                'title' => 'Compliance',
                'slug' => 'compliance',
                'content' => '<p>Welcome to <strong>Al Rihan Perfumes</strong> compliance page. We are committed to upholding the highest regulatory standards, ethical sourcing, and product safety in all our artisanal fragrance formulations.</p><h3>Product Safety & Quality Assurance</h3><p>Every fragrance created by Al Rihan adheres strictly to International Fragrance Association (IFRA) standards and local cosmetic regulations. Our ingredients are rigorously certified and batch-tested.</p><h3>Ethical Sourcing</h3><p>We source pure oud, natural essences, and botanical extracts exclusively from sustainable and verified suppliers worldwide.</p>',
                'meta_title' => 'Compliance | Al Rihan Perfumes',
                'meta_description' => 'Learn about Al Rihan Perfumes compliance, ethical sourcing, and IFRA safety standards.',
                'meta_keywords' => 'compliance, IFRA standards, perfume safety, al rihan quality',
                'is_active' => true,
                'sort_order' => 0
            ]);
        }

        return view('admin.compliance.edit', compact('compliance'));
    }

    public function create()
    {
        return view('admin.compliance.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $compliance = Compliance::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'content' => $request->content,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'is_active' => $request->has('is_active'),
            'sort_order' => (int) Compliance::max('sort_order') + 1
        ]);

        return redirect()->route('compliance.edit', $compliance->id)
            ->with('success', 'Compliance page created successfully.');
    }

    public function edit(Compliance $compliance)
    {
        return view('admin.compliance.edit', compact('compliance'));
    }

    public function update(Request $request, Compliance $compliance)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $compliance->update([
            'title' => $request->title,
            'slug' => $request->filled('slug') ? Str::slug($request->slug) : Str::slug($request->title),
            'content' => $request->content,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('compliance.edit', $compliance->id)
            ->with('success', 'Compliance page updated successfully.');
    }

    public function destroy(Compliance $compliance)
    {
        $compliance->delete();

        return redirect()->route('compliance.index')
            ->with('success', 'Compliance page deleted successfully.');
    }

    public function toggleStatus(Compliance $compliance)
    {
        $compliance->update(['is_active' => !$compliance->is_active]);
        $status = $compliance->is_active ? 'activated' : 'deactivated';

        return redirect()->route('compliance.edit', $compliance->id)
            ->with('success', "Compliance page has been {$status} successfully.");
    }
}
