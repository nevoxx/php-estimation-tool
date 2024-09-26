<?php

namespace App\Estimations\Renderer;

use App\Estimations\Nodes\ListNode;

interface RendererInterface 
{
  public function render(ListNode $tree): string;
}
