<?php

namespace App\Http\Controllers;

use App\Models\ComplaintType;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ComplaintTypesImport;

class ComplaintTypeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ComplaintType::class);  // ✅ ƏLAVƏ

        $types = ComplaintType::orderBy('id')->get();
        return view('complaint-types.index', compact('types'));
    }

    public function create()
    {
        $this->authorize('create', ComplaintType::class);  // ✅ ƏLAVƏ

        return view('complaint-types.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', ComplaintType::class);  // ✅ ƏLAVƏ

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        ComplaintType::create($validated);

        return redirect()->route('complaint-types.index')->with('success', 'Şikayət növü uğurla əlavə edildi!');
    }

    public function edit($id)
    {
        $type = ComplaintType::findOrFail($id);

        $this->authorize('update', $type);  // ✅ ƏLAVƏ

        return view('complaint-types.edit', compact('type'));
    }

    public function update(Request $request, $id)
    {
        $type = ComplaintType::findOrFail($id);

        $this->authorize('update', $type);  // ✅ ƏLAVƏ

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $type->update($validated);

        return redirect()->route('complaint-types.index')->with('success', 'Şikayət növü uğurla yeniləndi!');
    }

    public function destroy($id)
    {
        $type = ComplaintType::findOrFail($id);

        $this->authorize('delete', $type);  // ✅ ƏLAVƏ

        $type->delete();

        return redirect()->route('complaint-types.index')->with('success', 'Şikayət növü uğurla silindi!');
    }

    public function importForm()
    {
        $this->authorize('import', ComplaintType::class);  // ✅ ƏLAVƏ

        return view('complaint-types.import');
    }

    public function import(Request $request)
    {
        $this->authorize('import', ComplaintType::class);  // ✅ ƏLAVƏ

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            Excel::import(new ComplaintTypesImport, $request->file('file'));
            return redirect()->route('complaint-types.index')->with('success', 'Şikayət növləri uğurla idxal edildi!');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('complaint-types.index')->with('error', 'Şikayət növlərinin idxalı zamanı xəta baş verdi.');
        }
    }
}
