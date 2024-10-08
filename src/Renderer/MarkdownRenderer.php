<?php

namespace App\Estimations\Renderer;

use App\Estimations\Nodes\ListNode;

class MarkdownRenderer implements RendererInterface
{
    public function render(ListNode $tree): string
    {
        $markdown = '';

        // Define a recursive function to traverse the tree
        function traverse(ListNode $node, int $level, string &$markdown): void
        {
            if ($node->duration !== null) {
                $indent = str_repeat('  ', $level); // Generate indentation
                $durationFormatted = $node->getDurationFormatted(); // Get the formatted duration
                $percentage = '';

                if ($node->percentage && $node->percentageLevel) {
                    $percentage = '{' . str_repeat('^', $node->percentageLevel) . $node->percentage . '%}';
                }

                $markdown .= "{$indent}- [{$durationFormatted}]{$percentage} {$node->label}";
            } else {
                $markdown .= "{$node->label}";
            }

            if (! empty($node->note)) {
                $markdown .= " [!] {$node->note}";
            }

            $markdown .= "\n";

            // Traverse children recursively
            foreach ($node->children as $child) {
                traverse($child, $level + 1, $markdown);
            }
        }

        // Start traversal from the root node with level 0
        traverse($tree, 0, $markdown);

        return $markdown;
    }
}
