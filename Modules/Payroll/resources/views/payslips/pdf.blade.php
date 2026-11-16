<!DOCTYPE html>
<html lang="es">
@php
    $clp = fn ($amount) => '$ '.number_format((int) $amount, 0, ',', '.');
    $companyName = $company->razon_social ?: $company->name;
    $companyAddress = collect([$company->direccion, $company->comuna, $company->region])->filter()->implode(', ');
@endphp

<head>
    <meta charset="utf-8">
    <title>Liquidación de sueldo {{ $periodLabel }} - {{ $employee->full_name }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; }
        h1 { font-size: 15px; margin: 0; letter-spacing: 1px; }
        .muted { color: #64748b; }
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .header td { vertical-align: top; padding-bottom: 10px; }
        .box { border: 1px solid #cbd5e1; }
        .box td { padding: 4px 7px; vertical-align: top; }
        .label { font-size: 7.5px; text-transform: uppercase; color: #64748b; display: block; }
        .section-title { background: #0f172a; color: #ffffff; font-size: 8.5px; font-weight: bold; text-transform: uppercase; padding: 5px 7px; letter-spacing: 1px; }
        .group { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #475569; padding: 6px 7px 2px; }
        .rows td { padding: 3px 7px; border-bottom: 1px solid #f1f5f9; }
        .subtotal td { padding: 4px 7px; font-weight: bold; background: #f1f5f9; }
        .total td { padding: 6px 7px; font-weight: bold; border-top: 1.5px solid #0f172a; font-size: 10px; }
        .net { background: #0f172a; color: #ffffff; }
        .net td { padding: 9px 10px; }
        .net .amount { font-size: 16px; font-weight: bold; }
        .draft { border: 1.5px solid #b45309; color: #b45309; text-align: center; font-weight: bold; padding: 5px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
        .signature { border-top: 1px solid #1e293b; padding-top: 4px; text-align: center; font-size: 8.5px; }
        .small { font-size: 8px; }
    </style>
</head>

<body>
    @unless ($isIssued)
        <div class="draft">Borrador — período sin cerrar, los montos pueden cambiar</div>
    @endunless

    <table class="header">
        <tr>
            <td style="width: 60%;">
                <div class="bold" style="font-size: 12px;">{{ $companyName }}</div>
                <div class="muted">RUT {{ $company->rut ?: '—' }}</div>
                @if ($companyAddress)
                    <div class="muted">{{ $companyAddress }}</div>
                @endif
            </td>
            <td class="right" style="width: 40%;">
                <h1>LIQUIDACIÓN DE SUELDO</h1>
                <div class="bold" style="font-size: 11px; margin-top: 2px;">{{ $periodLabel }}</div>
            </td>
        </tr>
    </table>

    <table class="box">
        <tr>
            <td style="width: 34%;"><span class="label">Trabajador</span><span class="bold">{{ $employee->full_name }}</span></td>
            <td style="width: 22%;"><span class="label">RUT</span>{{ $employee->rut ?: '—' }}</td>
            <td style="width: 22%;"><span class="label">Cargo</span>{{ $employee->position ?: '—' }}</td>
            <td style="width: 22%;"><span class="label">Centro de costo</span>{{ $employee->costCenter?->name ?? ($employee->costCenter?->code ?? '—') }}</td>
        </tr>
        <tr>
            <td><span class="label">Fecha de ingreso</span>{{ $employee->hire_date?->format('d/m/Y') ?? '—' }}</td>
            <td><span class="label">Tipo de contrato</span>{{ $contractLabel }}</td>
            <td><span class="label">Días trabajados</span>{{ $payroll->worked_days ?? '—' }}@if ((int) $payroll->medical_leave_days > 0) · {{ (int) $payroll->medical_leave_days }} de licencia médica @endif</td>
            <td><span class="label">Horas extraordinarias</span>{{ rtrim(rtrim(number_format((float) $payroll->overtime_hours, 2, ',', '.'), '0'), ',') ?: '0' }}</td>
        </tr>
        <tr>
            <td><span class="label">AFP</span>{{ $afpLabel }}</td>
            <td><span class="label">Salud</span>{{ $healthLabel }}</td>
            <td><span class="label">Sueldo base pactado</span>{{ $clp($payroll->base_salary) }}</td>
            <td><span class="label">Cargas familiares</span>{{ (int) $employee->num_dependents }}</td>
        </tr>
    </table>

    <table style="margin-top: 10px;">
        <tr>
            {{-- Haberes --}}
            <td style="width: 49.5%; vertical-align: top;">
                <table class="box">
                    <tr><td colspan="2" class="section-title">Haberes</td></tr>
                    <tr><td colspan="2" class="group">Imponibles</td></tr>
                    @forelse ($sections['taxable'] as $row)
                        <tr class="rows"><td>{{ $row['label'] }}</td><td class="right">{{ $clp($row['amount']) }}</td></tr>
                    @empty
                        <tr class="rows"><td colspan="2" class="muted">Sin haberes imponibles</td></tr>
                    @endforelse
                    <tr class="subtotal"><td>Total imponible</td><td class="right">{{ $clp($totals['taxable']) }}</td></tr>

                    <tr><td colspan="2" class="group">No imponibles</td></tr>
                    @forelse ($sections['non_taxable'] as $row)
                        <tr class="rows"><td>{{ $row['label'] }}</td><td class="right">{{ $clp($row['amount']) }}</td></tr>
                    @empty
                        <tr class="rows"><td colspan="2" class="muted">Sin haberes no imponibles</td></tr>
                    @endforelse
                    <tr class="subtotal"><td>Total no imponible</td><td class="right">{{ $clp($totals['non_taxable']) }}</td></tr>

                    <tr class="total"><td>Total haberes</td><td class="right">{{ $clp($totals['earnings']) }}</td></tr>
                </table>
            </td>
            <td style="width: 1%;"></td>
            {{-- Descuentos --}}
            <td style="width: 49.5%; vertical-align: top;">
                <table class="box">
                    <tr><td colspan="2" class="section-title">Descuentos</td></tr>
                    <tr><td colspan="2" class="group">Legales</td></tr>
                    @forelse ($sections['legal'] as $row)
                        <tr class="rows"><td>{{ $row['label'] }}</td><td class="right">{{ $clp($row['amount']) }}</td></tr>
                    @empty
                        <tr class="rows"><td colspan="2" class="muted">Sin descuentos legales</td></tr>
                    @endforelse
                    <tr class="subtotal"><td>Total descuentos legales</td><td class="right">{{ $clp($totals['legal']) }}</td></tr>

                    <tr><td colspan="2" class="group">Otros descuentos</td></tr>
                    @forelse ($sections['other'] as $row)
                        <tr class="rows"><td>{{ $row['label'] }}</td><td class="right">{{ $clp($row['amount']) }}</td></tr>
                    @empty
                        <tr class="rows"><td colspan="2" class="muted">Sin otros descuentos</td></tr>
                    @endforelse
                    <tr class="subtotal"><td>Total otros descuentos</td><td class="right">{{ $clp($totals['other']) }}</td></tr>

                    <tr class="total"><td>Total descuentos</td><td class="right">{{ $clp($totals['deductions']) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="net" style="margin-top: 10px;">
        <tr>
            <td style="width: 65%;">
                <span class="bold" style="text-transform: uppercase; letter-spacing: 1px;">Líquido a pagar</span><br>
                @if ($netInWords)
                    <span class="small">Son: {{ $netInWords }}</span>
                @endif
            </td>
            <td class="right amount">{{ $clp($totals['net']) }}</td>
        </tr>
    </table>

    <table class="box" style="margin-top: 10px;">
        <tr>
            <td style="width: 25%;"><span class="label">Base tributable</span>{{ $clp($tributableBase) }}</td>
            <td style="width: 25%;"><span class="label">Forma de pago</span>{{ $paymentLabel }}</td>
            <td style="width: 25%;"><span class="label">Fecha de pago</span>{{ $paymentDate ? \Carbon\Carbon::parse($paymentDate)->format('d/m/Y') : '—' }}</td>
            <td style="width: 25%;">
                <span class="label">UF / UTM del período</span>
                {{ $uf ? '$ '.number_format((float) $uf, 2, ',', '.') : '—' }} / {{ $utm ? $clp($utm) : '—' }}
            </td>
        </tr>
        @if ($employee->payment_method === 'transferencia' && ($payroll->bank_account_number || $employee->bank_account_number))
            <tr>
                <td colspan="4">
                    <span class="label">Cuenta de depósito</span>
                    {{ $payroll->bank_name ?: ($employee->bank?->name ?? '—') }}
                    · {{ ucfirst((string) ($payroll->bank_account_type ?: $employee->bank_account_type)) }}
                    · {{ $payroll->bank_account_number ?: $employee->bank_account_number }}
                </td>
            </tr>
        @endif
    </table>

    @if (collect($employer)->sum() > 0)
        <table class="box" style="margin-top: 10px;">
            <tr><td colspan="2" class="group">Aportes de cargo del empleador (informativo, no se descuentan al trabajador)</td></tr>
            @foreach ($employer as $label => $amount)
                @if ($amount > 0)
                    <tr class="rows"><td class="small">{{ $label }}</td><td class="right small">{{ $clp($amount) }}</td></tr>
                @endif
            @endforeach
        </table>
    @endif

    <p class="small muted" style="margin-top: 12px;">
        Certifico que he recibido de {{ $companyName }}, a mi entera satisfacción, el saldo líquido indicado en la
        presente liquidación, sin tener cargo ni cobro posterior alguno que hacer por los conceptos comprendidos en ella.
    </p>

    <table style="margin-top: 46px;">
        <tr>
            <td style="width: 40%;"><div class="signature">{{ $companyName }}<br><span class="muted">Empleador</span></div></td>
            <td style="width: 20%;"></td>
            <td style="width: 40%;"><div class="signature">{{ $employee->full_name }}<br><span class="muted">RUT {{ $employee->rut ?: '—' }} · Recibí conforme</span></div></td>
        </tr>
    </table>
</body>

</html>
