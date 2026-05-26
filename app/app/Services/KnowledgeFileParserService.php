<?php

namespace App\Services;

use Illuminate\Support\Str;
use ZipArchive;

class KnowledgeFileParserService
{
    public function parse(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        return match ($extension) {
            'txt' => $this->parseText($path),
            'csv' => $this->parseCsv($path),
            'docx' => $this->parseDocx($path),
            'pdf' => $this->parsePdf($path),
            default => throw new \InvalidArgumentException("Unsupported file type: {$extension}"),
        };
    }

    /** @return list<array{title: string, content: string}> */
    protected function parseText(string $path): array
    {
        $content = trim(file_get_contents($path) ?: '');

        if ($content === '') {
            return [];
        }

        $sections = preg_split("/\n{2,}/", $content) ?: [$content];
        $items = [];

        foreach ($sections as $i => $section) {
            $section = trim($section);
            if ($section === '') {
                continue;
            }
            $lines = explode("\n", $section, 2);
            $items[] = [
                'title' => Str::limit($lines[0], 120, ''),
                'content' => $section,
            ];
        }

        return $items ?: [['title' => 'Imported document', 'content' => $content]];
    }

    /** @return list<array{title: string, content: string}> */
    protected function parseCsv(string $path): array
    {
        $items = [];
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return [];
        }

        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }
            $data = array_combine($header, array_pad(array_slice($row, 0, count($header)), count($header), ''));
            if ($data === false) {
                continue;
            }

            $title = $data['title'] ?? $data['question'] ?? $row[0] ?? null;
            $content = $data['content'] ?? $data['answer'] ?? $data['body'] ?? $row[1] ?? null;

            if ($title && $content) {
                $items[] = ['title' => $title, 'content' => $content];
            }
        }

        fclose($handle);

        return $items;
    }

    /** @return list<array{title: string, content: string}> */
    protected function parseDocx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Could not open DOCX file.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $xml) {
            throw new \RuntimeException('Invalid DOCX structure.');
        }

        $xml = preg_replace('/<w:tab[^>]*\/>/', "\t", $xml);
        $xml = preg_replace('/<w:br[^>]*\/>/', "\n", $xml);
        $xml = preg_replace('/<\/w:p>/', "\n\n", $xml);
        $text = html_entity_decode(strip_tags($xml));
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", trim($text));

        return $this->parseTextFromString($text, pathinfo($path, PATHINFO_FILENAME));
    }

    /** @return list<array{title: string, content: string}> */
    protected function parsePdf(string $path): array
    {
        $text = $this->extractPdfText($path);

        if ($text === '') {
            throw new \RuntimeException('Could not extract text from PDF. Try a text-based PDF or install smalot/pdfparser.');
        }

        return $this->parseTextFromString($text, pathinfo($path, PATHINFO_FILENAME));
    }

    protected function extractPdfText(string $path): string
    {
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($path);

            return trim($pdf->getText());
        }

        if ($this->commandExists('pdftotext')) {
            $output = tempnam(sys_get_temp_dir(), 'pdf_');
            $command = sprintf('pdftotext %s %s 2>&1', escapeshellarg($path), escapeshellarg($output));
            exec($command, $lines, $code);

            if ($code === 0 && is_readable($output)) {
                $text = trim(file_get_contents($output) ?: '');
                @unlink($output);

                return $text;
            }

            @unlink($output);
        }

        return $this->extractPdfTextBasic($path);
    }

    protected function extractPdfTextBasic(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return '';
        }

        $parts = [];

        if (preg_match_all('/\((?:\\\\.|[^\\\\)])*\)/s', $content, $matches)) {
            foreach ($matches[0] as $match) {
                $text = trim(stripslashes(trim($match, '()')));
                if ($text !== '' && ! preg_match('/^[\x00-\x1F]+$/', $text)) {
                    $parts[] = $text;
                }
            }
        }

        $text = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)) ?? '');

        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
    }

    protected function commandExists(string $command): bool
    {
        $check = PHP_OS_FAMILY === 'Windows' ? 'where' : 'command -v';
        exec("{$check} ".escapeshellarg($command).' 2>&1', $output, $code);

        return $code === 0;
    }

    /** @return list<array{title: string, content: string}> */
    protected function parseTextFromString(string $text, string $defaultTitle): array
    {
        if ($text === '') {
            return [];
        }

        return [['title' => $defaultTitle ?: 'Imported document', 'content' => $text]];
    }
}
