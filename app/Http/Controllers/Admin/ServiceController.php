<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\MembershipSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::latest();
        if ($request->filled('q')) {
            $query->whereLike('name', '%'.mb_substr((string) $request->query('q'), 0, 100).'%');
        }

        return view('admin.catalog.index', ['kind' => 'services', 'records' => $query->paginate(15)->withQueryString(), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function create()
    {
        return $this->form(new Service);
    }

    public function edit(Service $service)
    {
        return $this->form($service);
    }

    private function form(Service $service)
    {
        return view('admin.catalog.form', ['kind' => 'services', 'record' => $service, 'settings' => app(MembershipSettings::class)->all()]);
    }

    private function values(Request $request, ?Service $service = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'alpha_dash', 'max:64', Rule::unique('services')->ignore($service?->id)], 'description' => ['nullable', 'string', 'max:4000'], 'price' => ['required', 'integer', 'min:0', 'max:1000000000'], 'delivery_days' => ['required', 'integer', 'min:0', 'max:3650'], 'is_active' => ['required', 'boolean']]);
    }

    public function store(Request $request)
    {
        Service::create($this->values($request));

        return redirect()->route('admin.services.index')->with('success', 'Layanan ditambahkan.');
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->values($request, $service));

        return redirect()->route('admin.services.index')->with('success', 'Layanan diperbarui.');
    }

    public function destroy(Service $service)
    {
        $service->update(['is_active' => false]);

        return back()->with('success', 'Layanan diarsipkan. Tracking dan bill lama tetap tersimpan.');
    }
}
