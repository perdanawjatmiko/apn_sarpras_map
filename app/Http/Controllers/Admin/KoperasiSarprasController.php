<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Koperasi;
use App\Models\KoperasiSarpras;
use App\Models\Sarpras;
use App\Models\Status;
use App\Services\AdminPageService;
use App\Services\KoperasiSarprasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KoperasiSarprasController extends Controller
{
    public function __construct(
        private readonly AdminPageService $admins,
        private readonly KoperasiSarprasService $koperasiSarprases,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('admin/index', [
            'resource' => 'koperasiSarprases',
            'title' => 'Sarpras Koperasi',
            'search' => $search,
            'stats' => $this->admins->dashboard(),
            'records' => $this->koperasiSarprases->paginated($search),
            'options' => [
                'koperasis' => Koperasi::query()->orderBy('name')->limit(1000)->get(['id', 'name']),
                'sarprases' => Sarpras::query()->orderBy('name')->limit(1000)->get(['id', 'name']),
                'statuses' => Status::query()->orderBy('id')->get(['id', 'name']),
                'cities' => [],
                'districts' => [],
                'villages' => [],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->koperasiSarprases->upsert($this->validated($request));

        return back()->with('success', 'Sarpras koperasi disimpan.');
    }

    public function update(Request $request, KoperasiSarpras $koperasiSarpras): RedirectResponse
    {
        $this->koperasiSarprases->update($koperasiSarpras, $this->validated($request));

        return back()->with('success', 'Sarpras koperasi diperbarui.');
    }

    public function destroy(KoperasiSarpras $koperasiSarpras): RedirectResponse
    {
        $this->koperasiSarprases->delete($koperasiSarpras);

        return back()->with('success', 'Sarpras koperasi dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx', 'max:10240'],
        ]);

        $count = $this->koperasiSarprases->import($data['file']);

        return back()->with('success', "{$count} relasi sarpras koperasi diimport.");
    }

    public function templateCsv(): StreamedResponse
    {
        $headers = $this->templateHeaders();

        return response()->streamDownload(function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        }, 'template-sarpras-koperasi.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function templateXlsx(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'template-sarpras-koperasi-').'.xlsx';
        $this->writeXlsx($path, $this->templateHeaders());

        return response()->download($path, 'template-sarpras-koperasi.xlsx')->deleteFileAfterSend();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'koperasi_id' => ['required', 'exists:koperasis,id'],
            'sarpras_id' => ['required', 'exists:sarprases,id'],
            'status_id' => ['required', 'exists:statuses,id'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function templateHeaders(): array
    {
        return [
            'ai_id',
            ...Sarpras::query()->orderBy('id')->pluck('name')->all(),
        ];
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function writeXlsx(string $path, array $headers): void
    {
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="Sarpras Koperasi" sheetId="1" r:id="rId1"/></sheets>
</workbook>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>
<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/></cellXfs>
</styleSheet>');

        $cells = collect($headers)
            ->map(fn (string $header, int $index) => '<c r="'.$this->columnName($index + 1).'1" t="inlineStr" s="1"><is><t>'.htmlspecialchars($header, ENT_XML1).'</t></is></c>')
            ->implode('');

        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>
<sheetData><row r="1">'.$cells.'</row></sheetData>
</worksheet>');
        $zip->close();
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
