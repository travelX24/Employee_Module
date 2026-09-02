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
            border-radius: 6px;
            display: inline-block;
            vertical-align: middle;
            object-fit: cover;
        }

        .system-logo-fallback {
            width: 30px;
            height: 30px;
            background: #903749;
            border-radius: 6px;
            text-align: center;
            line-height: 30px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            display: inline-block;
            vertical-align: middle;
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
            color: #903749;
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
            /* Keep item placement deterministic in DomPDF. */
            direction: ltr;
        }

        .meta-item {
            display: inline-block;
            margin-right: 15px;
            margin-left: 15px;
        }

        .meta-table {
            border-collapse: collapse;
            border: 0;
            margin-top: 4px;
        }

        .meta-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
            white-space: nowrap;
        }

        .meta-pair {
            border-collapse: collapse;
            border: 0;
            direction: ltr;
        }

        .meta-pair td {
            border: 0;
            padding: 0 2px;
            white-space: nowrap;
        }

        .meta-label {
            font-weight: 700;
        }

        .main-content {
            padding-top: 15px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* DomPDF does not reliably reorder table cells for RTL.
               Keep physical table layout LTR and reverse the field array in Arabic. */
            direction: ltr;
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
            vertical-align: middle;
        }

        /*
         * QA 323
         * ArabicHelper creates visual RTL glyphs for DomPDF. When a complete
         * multi-word heading is left for DomPDF to wrap automatically, DomPDF
         * can move the last visual word to the first line. Render every logical
         * Arabic word as its own block so the vertical order stays exactly as
         * written in the translated label.
         */
        .pdf-header-label {
            width: 100%;
            line-height: 1.25;
            text-align: center;
        }

        .pdf-header-word {
            display: block;
            width: 100%;
            white-space: nowrap;
            text-align: center;
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

        .status-active {
            background-color: #ECFDF5;
            color: #10B981;
        }

        .status-suspended {
            background-color: #FFFBEB;
            color: #F59E0B;
        }

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
    @php
        /*
         * Use the exact Athka HR logo already used by the application sidebar.
         * Source: HR_System/public/brand/athka-hr.png
         * Embedding as base64 makes it reliable inside DomPDF without creating
         * or copying another logo file.
         */
        $athkaLogoPath = public_path('brand/athka-hr.png');
        $athkaLogoSrc = file_exists($athkaLogoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($athkaLogoPath))
            : null;
    @endphp

    <div class="header">
        <table class="header-table">
            <tr>
                @if(app()->getLocale() == 'ar')
                    {{-- Arabic: Logo on Right, Company on Left --}}
                    <td style="width: 50%; text-align: left;">
                        <div class="company-name">
                            {{ \Athka\Employees\Support\ArabicHelper::prepareForPdf(
                                $company->legal_name_ar
                                ?? $company->legal_name_en
                                ?? tr('Athka Company')
                            ) }}
                        </div>

                        <div class="company-details">
                            {{ \Athka\Employees\Support\ArabicHelper::prepareForPdf(
                                $company->address_line ?? tr('Unknown Address')
                            ) }}<br>
                            {{ $company->official_email ?? 'info@company.com' }}
                            |
                            {{ $company->phone_1 ?? '' }}
                        </div>
                    </td>

                    <td style="width: 50%; text-align: right;">
                        <div class="system-name">ATHKA HR</div>
                        @if($athkaLogoSrc)
                            <img src="{{ $athkaLogoSrc }}" alt="Athka HR Logo" class="system-logo">
                        @else
                            <div class="system-logo-fallback">A</div>
                        @endif
                    </td>
                @else
                    {{-- English: Logo on Left, Company on Right --}}
                    <td style="width: 50%; text-align: left;">
                        @if($athkaLogoSrc)
                            <img src="{{ $athkaLogoSrc }}" alt="Athka HR Logo" class="system-logo">
                        @else
                            <div class="system-logo-fallback">A</div>
                        @endif
                        <div class="system-name">ATHKA HR</div>
                    </td>

                    <td style="width: 50%; text-align: right;">
                        <div class="company-name">
                            {{ $company->legal_name_en
                                ?? $company->legal_name_ar
                                ?? 'Athka Company' }}
                        </div>

                        <div class="company-details">
                            {{ $company->address_line ?? 'Unknown Address' }}<br>
                            {{ $company->official_email ?? 'info@company.com' }}
                            |
                            {{ $company->phone_1 ?? '' }}
                        </div>
                    </td>
                @endif
            </tr>
        </table>
    </div>

    <div class="report-info">
        <h1 class="report-title">
            {{ \Athka\Employees\Support\ArabicHelper::prepareForPdf($title) }}
        </h1>

        <div class="meta-container">
            @if(app()->getLocale() == 'ar')
                {{--
                    QA 323
                    Use nested LTR tables to make the visual Arabic order deterministic in DomPDF.
                    The physical cell order is value | colon | label, so the PDF visually reads:
                    label : value
                --}}
                <table class="meta-table" style="margin-left: auto; margin-right: 0; direction: ltr;">
                    <tr>
                        {{-- Record count appears to the left of Generated By --}}
                        <td style="padding-right: 22px;">
                            <table class="meta-pair">
                                <tr>
                                    <td>{{ count($employees) }}</td>
                                    <td>:</td>
                                    <td class="meta-label">{{ \Athka\Employees\Support\ArabicHelper::prepareForPdf('عدد السجلات') }}</td>
                                </tr>
                            </table>
                        </td>

                        {{-- Generated By appears first/right and reads: تم إنشاؤها: محمد الصبري --}}
                        <td>
                            <table class="meta-pair">
                                <tr>
                                    <td>{{ \Athka\Employees\Support\ArabicHelper::prepareForPdf(auth()->user()->name) }}</td>
                                    <td>:</td>
                                    <td class="meta-label">{{ \Athka\Employees\Support\ArabicHelper::prepareForPdf('تم إنشاؤها') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            @else
                @php
                    $metaItems = [
                        ['label' => tr('Export Date'), 'value' => $date],
                        ['label' => tr('Record Count'), 'value' => count($employees)],
                        ['label' => tr('Generated By'), 'value' => auth()->user()->name],
                    ];
                @endphp

                @foreach($metaItems as $item)
                    <div class="meta-item">
                        <strong>{{ $item['label'] }}:</strong>
                        {{ $item['value'] }}
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <div class="main-content">
        @php
            $isArabic = app()->getLocale() == 'ar';

            /*
             * QA 323
             * DomPDF renders table cells physically left-to-right even when the
             * surrounding document is RTL. Reverse the field order in Arabic so
             * the first logical field (Employee Number) appears at the far right.
             * The same array is used for both headings and row values, keeping
             * every value aligned with its correct column.
             */
            $displayFields = $isArabic
                ? array_reverse($fields)
                : $fields;
        @endphp

        <table class="data-table">
            <thead>
                <tr>
                    @foreach($displayFields as $field)
                        @php
                            $headerLabel = (string) ($availableFields[$field] ?? $field);

                            $headerWords = $isArabic
                                ? preg_split(
                                    '/\s+/u',
                                    trim($headerLabel),
                                    -1,
                                    PREG_SPLIT_NO_EMPTY
                                )
                                : [];
                        @endphp

                        <th>
                            @if($isArabic)
                                <div class="pdf-header-label">
                                    @foreach($headerWords as $headerWord)
                                        <span class="pdf-header-word">{{
                                            \Athka\Employees\Support\ArabicHelper::prepareForPdf($headerWord)
                                        }}</span>
                                    @endforeach
                                </div>
                            @else
                                {{ $headerLabel }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach($employees as $employee)
                    <tr>
                        @foreach($displayFields as $field)
                            <td>
                                {!! $employee->{$field . '_fmt'} !!}
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
                    <td style="width: 33%; text-align: right; border: 0;">
                        <span class="page-number"></span>
                    </td>

                    <td style="width: 33%; text-align: center; border: 0;">
                        &copy; {{ date('Y') }}
                    </td>

                    <td style="width: 33%; text-align: left; border: 0;">
                        {{ \Athka\Employees\Support\ArabicHelper::prepareForPdf(
                            tr('Athka HR Management System')
                        ) }}
                    </td>
                @else
                    <td style="width: 33%; text-align: left; border: 0;">
                        {{ tr('Athka HR Management System') }}
                    </td>

                    <td style="width: 33%; text-align: center; border: 0;">
                        &copy; {{ date('Y') }}
                    </td>

                    <td style="width: 33%; text-align: right; border: 0;">
                        <span class="page-number"></span>
                    </td>
                @endif
            </tr>
        </table>
    </div>
</body>
</html>
