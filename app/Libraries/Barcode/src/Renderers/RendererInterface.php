<?php

namespace Picqer\src\Renderers;

use Picqer\src\Barcode;

interface RendererInterface
{
    public function render(Barcode $barcode, float $width = 200, float $height = 30): string;

    public function setForegroundColor(array $color): self;

    public function setBackgroundColor(?array $color): self;
}
