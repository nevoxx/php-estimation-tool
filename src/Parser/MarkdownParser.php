<?php

namespace App\Estimations\Parser;

use App\Estimations\Nodes\ListNode;

class MarkdownParser
{
    public function parse(string $data): ListNode
    {
        $lines = explode("\n", $data);
        $root = new ListNode('root');
        $stack = [[$root, -1]];
        $currentNode = null;

        foreach ($lines as $line) {
            // Skip empty lines
            if (! trim($line)) {
                continue;
            }

            // If the line starts with a dash (-)
            if (trim($line)[0] === '-') {
                $indentLevel = strlen($line) - strlen(ltrim($line));
                $cleanLine = trim($line);
                
                // Parse duration and percentage (if exists)
                $parsedData = $this->_parseDurationAndPercentage($cleanLine);

                $duration = $parsedData['duration'];
                $percentage = $parsedData['percentage'];
                $percentageLevel = $parsedData['percentageLevel'];

                // Process label without duration and note
                $labelWithoutDuration = ltrim(preg_replace(['/\\[.*?h]/', '/\\{.*?}/'], '', $cleanLine), '- ');
                $labelParts = explode('[!]', $labelWithoutDuration);
                $labelWithoutNote = trim($labelParts[0]);
                $note = isset($labelParts[1]) ? trim(implode('<br>', array_slice($labelParts, 1))) : '';

                // Maintain the stack according to the indent level
                while (count($stack) > 0 && $stack[count($stack) - 1][1] >= $indentLevel) {
                    array_pop($stack);
                }

                // Create a new node
                $currentNode = new ListNode(
                    $labelWithoutNote,
                    $duration,
                    $percentage,
                    $percentageLevel,
                    $note,
                    [],
                );

                // Add the node to its parent
                $parentNode = $stack[count($stack) - 1][0];
                $parentNode->addChild($currentNode);

                // Add current node to stack
                $stack[] = [$currentNode, $indentLevel];
            } else {
                // Append to the label if not a list item
                if ($currentNode !== null) {
                    $currentNode->label .= "\n" . $line;
                }
            }
        }

        return $root;
    }

    protected function _parseDurationAndPercentage(string $label): array
    {
        // Initialize variables as null
        $duration = null;
        $percentage = null;
        $percentageLevel = null;

        // Match the duration in the format "[Xh]"
        if (preg_match('/\[(\d+(?:\.\d+)?)h]/', $label, $durationMatch)) {
            $duration = floatval($durationMatch[1]);
        }

        // Match the percentage in the format "{^^^X%}"
        if (preg_match('/\{(\^+)(\d+)%}/', $label, $percentageMatch)) {
            $percentageLevel = strlen($percentageMatch[1]);
            $percentage = floatval($percentageMatch[2]);
            $duration = 0; // As per the original logic, if percentage is matched, set duration to 0
        }

        // Return the results as an associative array
        return [
            'duration' => $duration,
            'percentage' => $percentage,
            'percentageLevel' => $percentageLevel,
        ];
    }
}
