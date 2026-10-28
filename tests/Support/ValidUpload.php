<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;

class ValidUpload
{
    public static function pdf(string $name = 'evidence.pdf', int $kilobytes = 1): UploadedFile
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R >>', "<< /Length 0 >>\nstream\n\nendstream"];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 5\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Root 1 0 R /Size 5 >>\nstartxref\n$xref\n%%EOF\n";

        return UploadedFile::fake()->createWithContent($name, $pdf);
    }
}
