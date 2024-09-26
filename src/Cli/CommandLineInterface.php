<?php

namespace App\Estimations\Cli;

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
            exit(1);
        }

        // Extract the required markdown path
        $this->markdownPath = $args[1];

        // Validate if the markdown path exists
        if (!file_exists($this->markdownPath)) {
            echo "Error: The specified markdown file does not exist: {$this->markdownPath}\n";
            exit(1);
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
                case (preg_match('/^--pdf-outfile=(.+)$/', $arg, $matches) ? true : false):
                    $this->pdfOutputFile = $this->_getRelativePathBasedOnCwd($matches[1]);
                    break;
                case (preg_match('/^--md-outfile=(.+)$/', $arg, $matches) ? true : false):
                    $this->mdOutputFile = $this->_getRelativePathBasedOnCwd($matches[1]);
                    break;
                case '--help':
                    $this->displayHelp();
                    exit(0);
                default:
                    echo "Error: Unknown argument: {$arg}\n";
                    $this->displayHelp();
                    exit(1);
            }
        }
    }

    private function displayHelp(): void
    {
        echo "Usage: php estimation.php /path/to/markdown.md [options]\n";
        echo "Options:\n";
        echo "  --render                 Enable rendering of the markdown.\n";
        echo "  --pdf-outfile=/path/to/output.pdf  Specify the PDF output file.\n";
        echo "  --md-outfile=/path/to/output.md    Specify the Markdown output file.\n";
        echo "  --help                   Display this help message.\n";
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

    /**
     * Generate a relative path based on the current working directory.
     *
     * @param string $path The target path.
     * @return string The relative path from the current working directory.
     */
    protected function _getRelativePathBasedOnCwd(string $path): string {
      // Get the current working directory
      $cwd = getcwd();
      
      // Normalize the paths to avoid issues with different separators
      $normalizedCwd = realpath($cwd);
      $normalizedTargetPath = realpath($path);

      // If the target path is not absolute, return it directly
      if (!$normalizedTargetPath) {
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
