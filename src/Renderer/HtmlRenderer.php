<?php

namespace App\Estimations\Renderer;

use App\Estimations\Nodes\ListNode;

class HtmlRenderer implements RendererInterface
{
    const IDENT_PADDING_PIXELS = 20;

    public function render(ListNode $tree): string
    {
        return <<<HTML
      <!DOCTYPE html>
      <html lang='de'>
        <head>
          <meta charset='utf-8'>
          <title>Estimation</title>
          <style>
            body {
              font-family: Arial, sans-serif;
            }
            note {
              vertical-align: top;
              font-size: 14px;
              color: grey;
              line-height: 14px;
              font-weight: normal;
              display: block;
            }
          </style>
        </head>
        <body>
        <table class='estimation-table' border='0' cellspacing='0' cellpadding='0' style='width: 100%'>
          {$this->_buildRow($tree)}
        </table>
      </body>
    </html>
    HTML;
    }

    protected function _buildRow(ListNode $item, int $index = 0, string $prefix = '1'): string
    {
        $prefix = $prefix === '' ? '1' : $prefix;
        $prefix .= '.';
        $hasChildren = ! empty($item->children);

        $rowStyles = [
            'line-height: 1.5em;',
            'vertical-align: top;',
        ];

        $labelStyles = [
            'padding-left: '.($index * self::IDENT_PADDING_PIXELS).'px;',
        ];

        $estimateStyles = [
            'text-align: right;',
            'padding-left: 20px;',
        ];

        $noteStyles = [
            'vertical-align: top;',
            'font-size: 14px;',
            'color: grey;',
            'line-height: 14px;',
            'font-weight: normal;',
        ];

        if ($hasChildren) {
            $rowStyles[] = 'font-weight: bold;';
        }

        // Adjust font size based on index level
        if ($index === 0) {
            $rowStyles[] = 'font-size: 24px;';
        } elseif ($index === 1) {
            $rowStyles[] = 'font-size: 20px;';
        } else {
            $rowStyles[] = 'font-size: 18px;';
        }

        // Optional note
        $note = '';
        if (! empty($item->note)) {
            $note = "
        <tr style='".implode(' ', $noteStyles)."'>
          <td></td>
          <td>{$item->note}</td>
        </tr>
      ";
        }

        // Main row construction
        $row = "
      <tr style='".implode(' ', $rowStyles)."'>
        <td style='".implode(' ', $labelStyles)."'>
          <table cellspacing='0' cellpadding='0' border='0'>
            <tr style='vertical-align: top;'>
              <td style='padding: 4px 0px;'>
                {$prefix}) 
              </td>
              <td style='padding-top: 4px; padding-bottom: 4px; padding-left: 5px;'>
                {$item->label}
              </td>
            </tr>
            {$note}
          </table>
          <!-- {$prefix}) {$item->label} -->
        </td>
        <td style='".implode(' ', $estimateStyles)."'>
          {$item->getDurationFormatted()}
        </td>
      </tr>";

        $result = $row;

        // Process children recursively
        if ($hasChildren) {
            $idx = 1;

            foreach ($item->children as $child) {
                $result .= $this->_buildRow($child, $index + 1, $prefix.$idx);
                $idx++;
            }
        }

        return $result;
    }
}
