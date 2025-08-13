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
        $request->validate([
            'nombre' => 'required|string|max:255|unique:afps,nombre,' . $id,
        ]);

        $afp = Afp::findOrFail($id);
        $afp->nombre = $request->nombre;
        $afp->save();

        return response()->json(['message' => 'AFP actualizada correctamente.']);
    }

    public function updateIsapre(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:isapres,nombre,' . $id,
        ]);

        $isapre = Isapre::findOrFail($id);
        $isapre->nombre = $request->nombre;
        $isapre->save();

        return response()->json(['message' => 'Isapre actualizada correctamente.']);
    }

    public function updateCcaf(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:ccafs,nombre,' . $id,
        ]);

        $ccaf = Ccaf::findOrFail($id);
        $ccaf->nombre = $request->nombre;
        $ccaf->save();

        return response()->json(['message' => 'CCAF actualizada correctamente.']);
    }

    public function destroyAfp($id)
    {
        $afp = Afp::findOrFail($id);
        $afp->delete();

        return response()->json(['message' => 'AFP eliminada correctamente.']);
    }

    public function destroyIsapre($id)
    {
        $isapre = Isapre::findOrFail($id);
        $isapre->delete();

        return response()->json(['message' => 'Isapre eliminada correctamente.']);
    }

    public function destroyCcaf($id)
    {
        $ccaf = Ccaf::findOrFail($id);
        $ccaf->delete();

        return response()->json(['message' => 'CCAF eliminada correctamente.']);
    }

    public function ccafJson()
    {
        return Ccaf::orderBy('nombre')->get(['id', 'nombre']); 
    }

    public function afpJson()
    {
        return Afp::orderBy('name')->get(['id', 'name']);
    }

    public function isapreJson()
    {
        return Isapre::orderBy('name')->get(['id', 'name']);
    }

}
