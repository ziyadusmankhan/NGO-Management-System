<?php

namespace App\Domain\Documents\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class UploadValidator
{
    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'docx', 'xlsx', 'pptx'];

    public function reject(): never
    {
        throw ValidationException::withMessages(['document' => 'The file is unsupported, malformed, encrypted, contains active Office content, or exceeds the 10 MB limit.']);
    }

    public function inspect(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();
        if (! $file->isValid() || ! $path || $file->getSize() > 10 * 1024 * 1024 || ! in_array($extension, self::EXTENSIONS, true)) {
            $this->reject();
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $image = @getimagesize($path);
            $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
            if (! $image || $image['mime'] !== $allowed[$extension] || $mime !== $allowed[$extension] || $image[0] * $image[1] > 40000000) {
                $this->reject();
            }
        } elseif ($extension === 'pdf') {
            $handle = fopen($path, 'rb');
            $header = fread($handle, 8);
            fseek($handle, max(0, filesize($path) - 4096));
            $tail = stream_get_contents($handle);
            fclose($handle);
            if ($mime !== 'application/pdf' || ! str_starts_with($header, '%PDF-') || ! str_contains($tail, '%%EOF')) {
                $this->reject();
            }
            if (! preg_match('/%PDF-[12]\.\d/', $header) || ! preg_match('/startxref\s+(\d+)\s+%%EOF\s*$/s', $tail, $xref)) {
                $this->reject();
            }
            $offset = (int) $xref[1];
            if ($offset < 8 || $offset >= filesize($path)) {
                $this->reject();
            }
            $handle = fopen($path, 'rb');
            fseek($handle, $offset);
            $section = fread($handle, 2048);
            fclose($handle);
            if (! str_starts_with($section, 'xref') && ! preg_match('/^\d+\s+\d+\s+obj\b.*?\/Type\s*\/XRef/s', $section)) {
                $this->reject();
            }

        } else {
            $this->office($path, $extension);
            $mime = ['docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'][$extension];
        }

        return ['mime_type' => $mime, 'size' => $file->getSize(), 'sha256' => hash_file('sha256', $path), 'extension' => $extension];
    }

    private function office(string $path, string $extension): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            $this->reject();
        }
        try {
            if ($zip->numFiles > 1000 || $zip->locateName('[Content_Types].xml') === false) {
                $this->reject();
            }
            $main = ['docx' => 'word/document.xml', 'xlsx' => 'xl/workbook.xml', 'pptx' => 'ppt/presentation.xml'][$extension];
            if ($zip->locateName($main) === false) {
                $this->reject();
            }$total = 0;
            $names = [];
            $typeInfo = $zip->statName('[Content_Types].xml');
            if (! $typeInfo || $typeInfo['size'] > 2 * 1024 * 1024 || ($typeInfo['encryption_method'] ?? 0) !== 0) {
                $this->reject();
            }
            $types = $zip->getFromName('[Content_Types].xml');
            $mainType = ['docx' => 'wordprocessingml.document.main+xml', 'xlsx' => 'spreadsheetml.sheet.main+xml', 'pptx' => 'presentationml.presentation.main+xml'][$extension];
            if (! $types || ! str_contains($types, $mainType)) {
                $this->reject();
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = strtolower(str_replace('\\', '/', $stat['name']));
                $total += $stat['size'];
                if (isset($names[$name])) {
                    $this->reject();
                }$names[$name] = true;
                if ($total > 100 * 1024 * 1024 || $stat['size'] > 20 * 1024 * 1024 || ($stat['encryption_method'] ?? 0) !== 0 || str_starts_with($name, '/') || str_contains($name, '../') || str_contains($name, ':') || preg_match('/(vbaproject|activex|embeddings\/|externallinks\/|connections\.xml|\.exe$|\.dll$|\.js$|\.vbs$|\.ps1$|\.bat$|\.cmd$|\.com$|\.hta$|\.msi$|\.jar$)/', $name)) {
                    $this->reject();
                }
                $prefix = $zip->getFromIndex($i, 8);
                if (is_string($prefix) && (str_starts_with($prefix, 'MZ') || str_starts_with($prefix, "\xD0\xCF\x11\xE0") || str_starts_with($prefix, "\x7FELF"))) {
                    $this->reject();
                }
                if (str_ends_with($name, '.xml') || str_ends_with($name, '.rels')) {
                    $xml = $zip->getFromIndex($i);
                    if ($xml === false || preg_match('/<!DOCTYPE|<!ENTITY|macroenabled|DDEAUTO|\bDDE\b|attachedTemplate/i', $xml)) {
                        $this->reject();
                    }
                    $previous = libxml_use_internal_errors(true);
                    try {
                        $doc = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
                        if ($doc === false) {
                            $this->reject();
                        }
                        foreach ($doc->xpath('//*[local-name()="Relationship"]') ?: [] as $rel) {
                            if (preg_match('~/(oleobject|package|vbaproject|control)$~i', (string) $rel['Type'])) {
                                $this->reject();
                            }
                            if (strtolower((string) $rel['TargetMode']) === 'external' && ! str_ends_with(strtolower((string) $rel['Type']), '/hyperlink')) {
                                $this->reject();
                            }
                        }
                    } finally {
                        libxml_clear_errors();
                        libxml_use_internal_errors($previous);
                    }
                }
            }
        } finally {
            $zip->close();
        }
    }
}
