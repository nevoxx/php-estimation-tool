<?php

namespace App\Estimations\Nodes;

class ListNode
{
    public string $label;

    public ?float $duration;

    public ?float $percentage;

    public ?int $percentageLevel;

    public ?string $note;

    /** @var ListNode[] */
    public array $children;

    public function __construct(
        string $label,
        ?float $duration = null,
        ?float $percentage = null,
        ?int $percentageLevel = null,
        ?string $note = null,
        array $children = []
    ) {
        $this->label = $label;
        $this->duration = $duration;
        $this->percentage = $percentage;
        $this->percentageLevel = $percentageLevel;
        $this->note = $note;
        $this->children = $children;
    }

    public function getDurationFormatted(): string
    {
        if ($this->duration === null) {
            return '-';
        }

        // Check if the duration has decimals
        $decimals = $this->duration - floor($this->duration);

        // If no decimals (e.g., 12.0), show as an integer
        if ($decimals == 0) {
            return number_format($this->duration, 0, ',', '.') . 'h';
        }

        // If there are decimals, remove trailing zeroes after the decimal
        return rtrim(number_format($this->duration, 2, ',', '.'), '0') . 'h';
    }

    public function addChild(ListNode $child): void
    {
        $this->children[] = $child;
    }

    public function calculateParentDuration(): void
    {
        $totalDuration = 0.0;

        foreach ($this->children as $child) {
            $child->calculateParentDuration();

            if ($child->duration !== null) {
                $totalDuration += $child->duration;
            }
        }

        if ($totalDuration > 0) {
            $this->duration = $totalDuration;
        }
    }

    public function adjustDurationsByPercentage(ListNode $node, array $parentStack): void
    {
        // Traverse children first to ensure the tree is processed bottom-up
        foreach ($node->children as $child) {
            $this->adjustDurationsByPercentage($child, array_merge($parentStack, [$node]));
        }

        // Adjust the duration using percentage and percentageLevel
        if ($node->percentage !== null && $node->percentageLevel !== null && $node->percentageLevel > 0) {
            // Find the correct ancestor based on percentageLevel
            $ancestorIndex = count($parentStack) - $node->percentageLevel;
            if ($ancestorIndex >= 0) {
                $ancestorNode = $parentStack[$ancestorIndex]; // Get the ancestor node

                // Calculate the sum of sibling durations at the ancestor level
                $siblingSum = array_reduce($ancestorNode->children, function ($sum, $sibling) use ($node) {
                    if ($sibling !== $node && $sibling->duration !== null) {
                        return $sum + $sibling->duration;
                    }

                    return $sum;
                }, 0);

                // Apply the percentage to the sum of sibling durations
                if ($siblingSum > 0) {
                    $node->duration = ($siblingSum * $node->percentage) / 100;
                }
            }
        }
    }

    public function calculateDurations(): void
    {
        // First calculate parent durations without percentages
        $this->calculateParentDuration();

        // Then adjust durations based on percentage and percentageLevel
        $this->adjustDurationsByPercentage($this, []);

        // Recalculate parent durations after adjustments
        $this->calculateParentDuration();
    }
}
