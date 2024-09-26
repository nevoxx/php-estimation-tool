<?php

namespace App\Estimations\Cli;

use App\Estimations\Parser\MarkdownParser;
use App\Estimations\Renderer\HtmlRenderer;
use App\Estimations\Renderer\MarkdownRenderer;
use App\Estimations\Renderer\PdfRenderer;

class CommandLineInterface
{
    private string $markdownPath;

    private bool $render = false;

    private string $pdfOutputFile;

    private string $mdOutputFile;

    public function __construct(array $args)
    {
        $this->parseArguments($args);
    }

    private function parseArguments(array $args): void
    {
        // Check if there are enough arguments
        if (count($args) < 2) {
            $this->displayHelp();
            exit(0);
        }

        // Extract the required markdown path
        $this->markdownPath = $args[1];

        // Validate if the markdown path exists
        if (! file_exists($this->markdownPath)) {
            echo "❌ Error: The specified markdown file does not exist: {$this->markdownPath}\n";
            exit(0);
        }

        // Set default values for output files
        $this->mdOutputFile = $this->_getRelativePathBasedOnCwd($this->markdownPath); // Default to the source file
        $this->pdfOutputFile = $this->_getRelativePathBasedOnCwd(preg_replace('/\.[^.]+$/', '.pdf', $this->markdownPath)); // Change extension to .pdf

        // Process the remaining arguments
        foreach (array_slice($args, 2) as $arg) {
            switch ($arg) {
                case '--render':
                    $this->render = true;
                    break;
                case preg_match('/^--pdf-outfile=(.+)$/', $arg, $matches) ? true : false:
                    $this->pdfOutputFile = $this->_getRelativePathBasedOnCwd($matches[1]);
                    break;
                case preg_match('/^--md-outfile=(.+)$/', $arg, $matches) ? true : false:
                    $this->mdOutputFile = $this->_getRelativePathBasedOnCwd($matches[1]);
                    break;
                case '--help':
                    $this->displayHelp();
                    exit(0);
                default:
                    echo "❌ Error: Unknown argument: {$arg}\n";
                    $this->displayHelp();
                    exit(0);
            }
        }
    }

    private function displayHelp(): void
    {
        // Display a header with some ASCII art
        echo "\n";
        echo "==============================\n";
        echo "  📚 Help - Estimation Tool  \n";
        echo "==============================\n\n";

        echo "Usage: php estimation.php /path/to/markdown.md [options]\n\n";
        echo "Options:\n";
        echo str_pad('  --render', 40) . " 🌟 Enable rendering of the markdown.\n";
        echo str_pad('  --pdf-outfile=/path/to/output.pdf', 40) . " 📄 Specify the PDF output file.\n";
        echo str_pad('  --md-outfile=/path/to/output.md', 40) . " 📜 Specify the Markdown output file.\n";
        echo str_pad('  --help', 40) . " ❓ Display this help message.\n";
        echo "\n";
    }

    public function getMarkdownPath(): string
    {
        return $this->markdownPath;
    }

    public function isRenderEnabled(): bool
    {
        return $this->render;
    }

    public function getPdfOutputFile(): string
    {
        return $this->pdfOutputFile;
    }

    public function getMdOutputFile(): string
    {
        return $this->mdOutputFile;
    }

    public function process(): void
    {
        try {
            $markdownPath = $this->getMarkdownPath();
            $render = $this->isRenderEnabled();
            $pdfOutputFile = $this->getPdfOutputFile();
            $mdOutputFile = $this->getMdOutputFile();

            $this->_displayInfo($markdownPath, $render, $pdfOutputFile, $mdOutputFile);
            $this->_processMarkdown($markdownPath, $mdOutputFile, $render);
        } catch (\Exception $e) {
            echo '❌  An error occurred: ' . $e->getMessage() . "\n";
        }
    }

    protected function _displayInfo($markdownPath, $render, $pdfOutputFile, $mdOutputFile)
    {
        // Define a title
        echo "\n" . str_repeat('=', 40) . "\n";
        echo " Estimation Command-Line Interface\n";
        echo str_repeat('=', 40) . "\n\n";

        // Prepare the output with padding for alignment
        $format = "%-30s: %s\n"; // Left-aligned for label and right-aligned for value
        printf($format, 'Source File Path', $markdownPath);
        printf($format, 'Rendering Enabled', ($render ? 'Yes' : 'No'));
        printf($format, 'PDF Output File', $pdfOutputFile);
        printf($format, 'Markdown Output File', $mdOutputFile);

        echo "\n" . str_repeat('-', 40) . "\n";
    }

    protected function _processMarkdown($markdownPath, $mdOutputFile, $render)
    {
        // Read Markdown file
        echo 'Reading Markdown file ...';
        $contents = file_get_contents($markdownPath);
        echo " ✔️\n";

        // Parse Markdown content
        echo 'Parsing Markdown content ...';
        $parser = new MarkdownParser;
        $tree = $parser->parse($contents);
        $tree->calculateDurations();
        echo " ✔️\n";

        // Render Markdown to file
        echo 'Rendering Markdown to file ...';
        $markdownRenderer = new MarkdownRenderer;
        $markdown = $markdownRenderer->render($tree->children[0]);
        file_put_contents($mdOutputFile, $markdown);
        echo " ✔️\n";

        if ($render) {
            return $this->_renderHtmlAndPdf($tree->children[0]);
        }

        return null;
    }

    protected function _renderHtmlAndPdf($node)
    {
        // Render HTML
        echo 'Rendering HTML ...';
        $htmlRenderer = new HtmlRenderer;
        $html = $htmlRenderer->render($node);
        echo " ✔️\n";

        // Render PDF
        echo 'Rendering PDF ...';
        $pdfRenderer = new PdfRenderer;
        $pdfData = $pdfRenderer->render($html);
        file_put_contents($this->pdfOutputFile, $pdfData);
        echo " ✔️\n";

        return $pdfData;
    }

    /**
     * Generate a relative path based on the current working directory.
     *
     * @param  string  $path  The target path.
     * @return string The relative path from the current working directory.
     */
    protected function _getRelativePathBasedOnCwd(string $path): string
    {
        return $path;
        // Get the current working directory
        $cwd = getcwd();

        // Normalize the paths to avoid issues with different separators
        $normalizedCwd = realpath($cwd);
        $normalizedTargetPath = realpath($path);

        // If the target path is not absolute, return it directly
        if (! $normalizedTargetPath) {
            return $path; // Return as-is if it doesn't exist
        }

        // Calculate the relative path
        $relativePath = str_replace($normalizedCwd . DIRECTORY_SEPARATOR, '', $normalizedTargetPath);

        // Handle case where the path is outside the current directory
        if (strpos($normalizedTargetPath, $normalizedCwd) !== 0) {
            return $normalizedTargetPath; // Return the absolute path if it's outside
        }

        return $relativePath;
    }
}
