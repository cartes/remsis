<?php

namespace Modules\AdminPanel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\Isapre;
use Modules\AdminPanel\Models\Ccaf;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('adminpanel::settings.index', [
            'afps' => Afp::all(),
            'isapres' => Isapre::all(),
            'ccafs' => Ccaf::all(),
        ]);
    }

    public function storeAfp(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:255|unique:afps,nombre']);
        Afp::create(['nombre' => $request->nombre]);
        return redirect()->route('settings.index')->with('success_afp', 'AFP agregada.');
    }

    public function storeIsapre(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:255|unique:isapres,nombre']);
        Isapre::create(['nombre' => $request->nombre]);
        return redirect()->route('settings.index')->with('success_isapre', 'Isapre agregada.');
    }

    public function storeCcaf(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:255|unique:ccafs,nombre']);
        Ccaf::create(['nombre' => $request->nombre]);
        return redirect()->route('settings.index')->with('success_ccaf', 'CCAF agregada.');
    }

    public function updateAfp(Request $request, $id)
    {
        $afp = Afp::findOrFail($id);
        $request->validate(['nombre' => 'required|string|max:255|unique:afps,nombre,' . $afp->id]);
        $afp->update(['nombre' => $request->nombre]);
        return redirect()->route('settings.index')->with('success_afp', 'AFP actualizada.');
    }

    public function updateIsapre(Request $request, $id)
    {
        $isapre = Isapre::findOrFail($id);
        $request->validate(['nombre' => 'required|string|max:255|unique:isapres,nombre,' . $isapre->id]);
        $isapre->update(['nombre' => $request->nombre]);
        return redirect()->route('settings.index')->with('success_isapre', 'Isapre actualizada.');
    }

    public function updateCcaf(Request $request, $id)
    {
        $ccaf = Ccaf::findOrFail($id);
        $request->validate(['nombre' => 'required|string|max:255|unique:ccafs,nombre,' . $ccaf->id]);
        $ccaf->update(['nombre' => $request->nombre]);
        return redirect()->route('settings.index')->with('success_ccaf', 'CCAF actualizada.');
    }
}
