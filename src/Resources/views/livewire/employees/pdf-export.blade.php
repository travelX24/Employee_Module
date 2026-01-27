<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: normal;
            src: url(https://cdn.jsdelivr.net/gh/dompdf/dompdf@v3.0.0/lib/res/fonts/DejaVuSans.ttf) format('truetype');
        }
        @page {
            margin: 15px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #1e293b;
            line-height: 1.1;
            direction: {{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }};
        }
        .header {
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .system-logo {
            width: 30px;
            height: 30px;
            background: #4f46e5;
            border-radius: 6px;
            text-align: center;
            line-height: 30px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            display: inline-block;
        }
        .system-name {
            font-size: 14px;
            font-weight: 800;
            color: #1e293b;
            margin-right: 8px;
            margin-left: 8px;
            display: inline-block;
            vertical-align: middle;
        }
        .company-name {
            font-size: 13px;
            font-weight: 700;
            color: #4f46e5;
        }
        .company-details {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
        }
        .report-info {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
        }
        .report-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }
        .meta-container {
            margin-top: 4px;
            font-size: 8px;
            color: #64748b;
        }
        .meta-item {
            display: inline-block;
            margin-right: 15px;
            margin-left: 15px;
        }
        .main-content {
            padding-top: 15px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: {{ count($fields) > 25 ? '5px' : (count($fields) > 15 ? '7px' : '9px') }};
            font-weight: 800;
            padding: 4px 2px;
            text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
            border: 1px solid #cbd5e1;
            overflow: hidden;
        }
        .data-table td {
            padding: 4px 2px;
            font-size: {{ count($fields) > 25 ? '5px' : (count($fields) > 15 ? '7px' : '9px') }};
            color: #334155;
            border: 1px solid #f1f5f9;
            text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};
            word-wrap: break-word;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .status-pill {
            padding: 1px 3px;
            border-radius: 3px;
            font-size: {{ count($fields) > 15 ? '5px' : '7px' }};
            font-weight: bold;
        }
        .status-active { background-color: #dcfce7; color: #166534; }
        .status-suspended { background-color: #fee2e2; color: #991b1b; }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            padding: 8px 0;
            border-top: 1px solid #f1f5f9;
            font-size: 7px;
            color: #94a3b8;
        }
        .page-number:after {
            content: " - " counter(page);
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                @if(app()->getLocale() == 'ar')
                    {{-- Arabic: Logo on Right, Company on Left --}}
                    <td style="width: 50%; text-align: left;">
                        <div class="company-name">{{ \Athka\Employees\Support\ArabicHelper::prepareForPdf($company->legal_name_ar ?? $company->legal_name_en ?? tr('Athka Company')) }}</div>
                        <div class="company-details">
                            {{ \Athka\Employees\Support\ArabicHelper::prepareForPdf($company->address_line ?? tr('Unknown Address')) }}<br>
                            {{ $company->official_email ?? 'info@company.com' }} | {{ $company->phone_1 ?? '' }}
                        </div>
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <div class="system-name">ATHKA HR</div>
                        <div class="system-logo">A</div>
                    </td>
                @else
                    {{-- English: Logo on Left, Company on Right --}}
                    <td style="width: 50%; text-align: left;">
                        <div class="system-logo">A</div>
                        <div class="system-name">ATHKA HR</div>
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <div class="company-name">{{ $company->legal_name_en ?? $company->legal_name_ar ?? 'Athka Company' }}</div>
                        <div class="company-details">
                            {{ $company->address_line ?? 'Unknown Address' }}<br>
                            {{ $company->official_email ?? 'info@company.com' }} | {{ $company->phone_1 ?? '' }}
                        </div>
                    </td>
                @endif
            </tr>
        </table>
    </div>

    <div class="report-info">
        <h1 class="report-title">{{ \Athka\Employees\Support\ArabicHelper::prepareForPdf($title) }}</h1>
        <div class="meta-container">
            @php
                $metaItems = [
                    ['label' => tr('Export Date'), 'value' => $date],
                    ['label' => tr('Record Count'), 'value' => count($employees)],
                    ['label' => tr('Generated By'), 'value' => auth()->user()->name]
                ];
                if(app()->getLocale() == 'ar') $metaItems = array_reverse($metaItems);
            @endphp
            @foreach($metaItems as $item)
                <div class="meta-item">
                    <strong>{{ \Athka\Employees\Support\ArabicHelper::prepareForPdf($item['label']) }}:</strong> 
                    {{ app()->getLocale() == 'ar' ? \Athka\Employees\Support\ArabicHelper::prepareForPdf($item['value']) : $item['value'] }}
                </div>
            @endforeach
        </div>
    </div>

    <div class="main-content">
        <table class="data-table">
            <thead>
                <tr>
                    @php 
                        $displayFields = app()->getLocale() == 'ar' ? array_reverse($fields) : $fields; 
                    @endphp
                    @foreach($displayFields as $field)
                        <th>{{ \Athka\Employees\Support\ArabicHelper::prepareForPdf($availableFields[$field] ?? $field) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $employee)
                    <tr>
                        @foreach($displayFields as $field)
                            <td>
                                @php
                                    $val = '';
                                    if ($field === 'department_id') {
                                        $val = $employee->department?->name ?? 'N/A';
                                    } elseif ($field === 'sub_department_id') {
                                        $val = $employee->subDepartment?->name ?? 'N/A';
                                    } elseif ($field === 'job_title_id') {
                                        $val = $employee->jobTitle?->name ?? 'N/A';
                                    } elseif ($field === 'manager_id') {
                                        $val = $employee->manager?->name_ar ?? $employee->manager?->name_en ?? 'N/A';
                                    } elseif ($field === 'status') {
                                        $statusClass = $employee->status === 'ACTIVE' ? 'status-active' : 'status-suspended';
                                        $val = '<span class="status-pill '.$statusClass.'">'.\Athka\Employees\Support\ArabicHelper::prepareForPdf(tr($employee->status)).'</span>';
                                    } elseif ($field === 'basic_salary' || $field === 'allowances') {
                                        $val = number_format((float)$employee->{$field}, 2);
                                    } else {
                                        $val = $employee->{$field} ?? '-';
                                    }

                                    // Reshape Arabic text for PDF
                                    if (!in_array($field, ['basic_salary', 'allowances', 'status', 'employee_number'])) {
                                        $val = \Athka\Employees\Support\ArabicHelper::prepareForPdf($val);
                                    }
                                @endphp
                                {!! $val !!}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <table style="width: 100%; border: 0;">
            <tr>
                @if(app()->getLocale() == 'ar')
                    <td style="width: 33%; text-align: right; border: 0;"><span class="page-number"></span></td>
                    <td style="width: 33%; text-align: center; border: 0;">&copy; {{ date('Y') }}</td>
                    <td style="width: 33%; text-align: left; border: 0;">{{ \Athka\Employees\Support\ArabicHelper::prepareForPdf(tr('Athka HR Management System')) }}</td>
                @else
                    <td style="width: 33%; text-align: left; border: 0;">{{ tr('Athka HR Management System') }}</td>
                    <td style="width: 33%; text-align: center; border: 0;">&copy; {{ date('Y') }}</td>
                    <td style="width: 33%; text-align: right; border: 0;"><span class="page-number"></span></td>
                @endif
            </tr>
        </table>
    </div>
</body>
</html>




