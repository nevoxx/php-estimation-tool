<?php

require_once dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use App\Estimations\Cli\CommandLineInterface;
use App\Estimations\Parser\MarkdownParser;
use App\Estimations\Renderer\HtmlRenderer;
use App\Estimations\Renderer\MarkdownRenderer;
use App\Estimations\Renderer\PdfRenderer;

function displayInfo($markdownPath, $render, $pdfOutputFile, $mdOutputFile) {
    // Define a title
    echo "\n" . str_repeat('=', 40) . "\n";
    echo " Estimation Command-Line Interface\n";
    echo str_repeat('=', 40) . "\n\n";

    // Prepare the output with padding for alignment
    $format = "%-30s: %s\n"; // Left-aligned for label and right-aligned for value
    printf($format, "Source File Path", $markdownPath);
    printf($format, "Rendering Enabled", ($render ? 'Yes' : 'No'));
    printf($format, "PDF Output File", $pdfOutputFile);
    printf($format, "Markdown Output File", $mdOutputFile);
    
    echo "\n" . str_repeat('-', 40) . "\n";
}

function processMarkdown($markdownPath, $mdOutputFile, $render) {
    // Read Markdown file
    echo "Reading Markdown file ...";
    $contents = file_get_contents($markdownPath);
    echo " ✔️\n";

    // Parse Markdown content
    echo "Parsing Markdown content ...";
    $parser = new MarkdownParser();
    $tree = $parser->parse($contents);
    $tree->calculateDurations();
    echo " ✔️\n";

    // Render Markdown to file
    echo "Rendering Markdown to file ...";
    $markdownRenderer = new MarkdownRenderer();
    $markdown = $markdownRenderer->render($tree->children[0]);
    file_put_contents($mdOutputFile, $markdown);
    echo " ✔️\n";

    if ($render) {
        return renderHtmlAndPdf($tree->children[0]);
    }

    return null;
}

function renderHtmlAndPdf($node) {
    // Render HTML
    echo "Rendering HTML ...";
    $htmlRenderer = new HtmlRenderer();
    $html = $htmlRenderer->render($node);
    echo " ✔️\n";

    // Render PDF
    echo "Rendering PDF ...";
    $pdfRenderer = new PdfRenderer();
    $pdfData = $pdfRenderer->render($html);
    echo " ✔️\n";
    
    return $pdfData;
}

try {
    $cli = new CommandLineInterface($argv);

    // Access the parsed options
    $markdownPath = $cli->getMarkdownPath();
    $render = $cli->isRenderEnabled();
    $pdfOutputFile = $cli->getPdfOutputFile();
    $mdOutputFile = $cli->getMdOutputFile();

    displayInfo($markdownPath, $render, $pdfOutputFile, $mdOutputFile);
    processMarkdown($markdownPath, $mdOutputFile, $render);
} catch (Exception $e) {
    echo "❌  An error occurred: " . $e->getMessage() . "\n";
}
