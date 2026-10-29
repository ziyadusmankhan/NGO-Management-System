<?php

use App\Domain\Committees\Models\Committee;
use App\Domain\Members\Models\Member;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;

// Read-only verification of files downloaded from the isolated browser QA environment.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$receipt = json_decode(file_get_contents(base_path('tmp/mysql-test-target.json')), true, flags: JSON_THROW_ON_ERROR);
$name = $receipt['database'] ?? '';
if (! app()->environment('local') || ! preg_match('/^foundation_management_test_[0-9]{14}_[a-f0-9]{4}$/', $name) || ! ($receipt['realistic_seeded'] ?? false)) {
    throw new RuntimeException('Disposable QA database required.');
}
config(['database.connections.mysql.database' => $name, 'database.connections.mysql.url' => null]);
DB::purge('mysql');
$expected = ['members' => Member::count(), 'committees' => Committee::count(),
    'users' => User::count(), 'roles' => Role::where('status', 'active')->count()];
$results = [];
foreach ($expected as $kind => $count) {
    $path = base_path('output/organization-exports/foundation-'.$kind.'.xlsx');
    $book = IOFactory::load($path);
    $sheet = $book->getSheet(0);
    if ((int) $sheet->getCell('B6')->getCalculatedValue() !== $count) {
        throw new RuntimeException('Export count differs from database for '.$kind);
    }
    if (! $sheet->getDrawingCollection()->count()) {
        throw new RuntimeException('Logo missing for '.$kind);
    }
    if (count($sheet->getDataValidationCollection())) {
        throw new RuntimeException('Obsolete sales validation survived.');
    }
    $sheets = [];
    foreach ($book->getWorksheetIterator() as $tab) {
        $headers = $tab->getTitle() === $sheet->getTitle() ? 15 : 7;
        $rows = $tab->getHighestDataRow() - $headers;
        if ($tab->getHighestDataRow() && $rows < 1) {
            throw new RuntimeException('Unexpected empty worksheet.');
        }
        $sheets[] = ['name' => $tab->getTitle(), 'rows' => $rows, 'frozen' => $tab->getFreezePane()];
        foreach ($tab->getCellCollection()->getCoordinates() as $coordinate) {
            $cell = $tab->getCell($coordinate);
            if ($cell->isFormula() && in_array($cell->getCalculatedValue(), ['#REF!', '#VALUE!', '#DIV/0!', '#NAME?', '#NUM!', '#N/A'], true)) {
                throw new RuntimeException('Formula error in '.$kind.' '.$coordinate);
            }
        }
    }
    $pdf = new PdfReader(new PdfParser(StreamReader::createByFile(base_path('output/organization-exports/foundation-'.$kind.'.pdf'))));
    $results[] = ['kind' => $kind, 'matching_records' => $count, 'sheets' => $sheets, 'pdf_pages' => $pdf->getPageCount()];
    $book->disconnectWorksheets();
}
file_put_contents(base_path('tmp/organization-exports/file-verification.json'), json_encode($results, JSON_PRETTY_PRINT));
echo json_encode($results, JSON_PRETTY_PRINT), PHP_EOL;
