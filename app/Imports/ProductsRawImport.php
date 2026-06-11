<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Membaca seluruh sheet pertama menjadi array 2 dimensi (baris pertama = header).
 * Logika pemrosesan ditangani ProductImportService agar mudah diuji.
 */
class ProductsRawImport implements ToArray
{
    public array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
    }
}
