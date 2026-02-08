<?php

namespace Modules\AdminPanel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AdminPanel\Models\Afp;
use Modules\AdminPanel\Models\Isapre;
use Modules\AdminPanel\Models\Ccaf;
use Modules\AdminPanel\Models\LegalParameter;
use Modules\AdminPanel\Models\CodigoSii;
use Modules\AdminPanel\Models\Bank;

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
            'bancos' => Bank::orderBy('name')->get(['id', 'name as nombre']),
        ]);
    }

    public function legal()
    {
        return view('adminpanel::settings.legal', [
            'legalParameters' => LegalParameter::all(),
        ]);
    }

    public function siiCodes()
    {
        return view('adminpanel::settings.sii_codes', [
            'codigosSii' => CodigoSii::orderBy('codigo')->get(),
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

    public function storeBanco(Request $request)
    {
        $request->validate(['nombre' => 'required|string|max:255|unique:banks,name']);
        $banco = Bank::create(['name' => $request->nombre]);
        return redirect()->route('settings.index')->with('success_banco', 'Banco agregado.');
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

    public function updateBanco(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:banks,name,' . $id,
        ]);

        $banco = Bank::findOrFail($id);
        $banco->name = $request->nombre;
        $banco->save();

        return response()->json(['message' => 'Banco actualizado correctamente.']);
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

    public function destroyBanco($id)
    {
        $banco = Bank::findOrFail($id);
        $banco->delete();

        return response()->json(['message' => 'Banco eliminado correctamente.']);
    }

    public function ccafJson()
    {
        return Ccaf::orderBy('nombre')->get(['id', 'nombre']); 
    }

    public function afpJson()
    {
        return Afp::orderBy('nombre')->get(['id', 'nombre']);
    }

    public function isapreJson()
    {
        return Isapre::orderBy('nombre')->get(['id', 'nombre']);
    }

    public function bancoJson()
    {
        return Bank::orderBy('name')->get(['id', 'name as nombre']);
    }

    public function updateLegalParameters(Request $request)
    {
        $data = $request->except(['_token', '_method']);
        
        foreach ($data as $key => $value) {
            LegalParameter::where('key', $key)->update(['value' => $value]);
        }

        return redirect()->route('settings.legal')->with('success_legal', 'Parámetros legales actualizados correctamente.');
    }

}
