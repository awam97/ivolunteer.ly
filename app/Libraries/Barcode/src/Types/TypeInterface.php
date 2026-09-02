<?php

namespace Picqer\src\Types;

use Picqer\src\Barcode;

interface TypeInterface
{
    public function getBarcode(string $code): Barcode;
}
