<?php

namespace Modules\Companies\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Companies\Models\Company;
use Modules\Employees\Models\Employee;
use Modules\Employees\Models\EmployeeDocument;

class EmployeeDocumentController extends Controller
{
    /**
     * Sube un documento PDF a la carpeta digital del colaborador.
     */
    public function store(Request $request, Company $company, Employee $employee)
    {
        $this->authorizeCompanyAccess($company);

        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'document_type' => 'required|in:contrato,anexo,comprobante,legal,otro',
            'file'          => 'required|file|mimes:pdf|max:10240',
        ], [
            'file.required' => 'Debes seleccionar un archivo PDF.',
            'file.mimes'    => 'El archivo debe ser un PDF.',
            'file.max'      => 'El archivo no puede superar los 10 MB.',
        ]);

        $path = $request->file('file')->store(
            "employee_documents/{$employee->id}",
            'local'
        );

        EmployeeDocument::create([
            'employee_id'      => $employee->id,
            'title'            => $validated['title'],
            'document_type'    => $validated['document_type'],
            'file_path'        => $path,
            'signature_status' => 'sin_firma',
        ]);

        return back()
            ->with('active_tab', 'documentos')
            ->with('success', 'Documento subido correctamente.');
    }

    /**
     * Descarga un documento de la carpeta digital.
     */
    public function download(Company $company, Employee $employee, EmployeeDocument $document)
    {
        $this->authorizeCompanyAccess($company);

        abort_if($document->employee_id !== $employee->id, 403);

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->title . '.pdf'
        );
    }

    /**
     * Elimina un documento de la carpeta digital.
     */
    public function destroy(Company $company, Employee $employee, EmployeeDocument $document)
    {
        $this->authorizeCompanyAccess($company);

        abort_if($document->employee_id !== $employee->id, 403);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()
            ->with('active_tab', 'documentos')
            ->with('success', 'Documento eliminado.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function authorizeCompanyAccess(Company $company): void
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            return;
        }

        abort_if((int) $user->company_id !== $company->id, 403);
    }
}
