<?php

namespace App\Services;

use App\Models\PriceType;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Import produk dari baris Excel (1 baris = 1 produk, kolom tipe harga di kanan).
 * Kolom tetap dikenali via alias header; kolom sisanya dicocokkan ke master price_types.
 */
class ProductImportService
{
    /** Alias header => field produk. */
    private array $fieldAliases = [
        'name' => ['name', 'nama', 'nama produk', 'product name'],
        'sku' => ['sku', 'kode', 'kode produk'],
        'brand' => ['brand', 'merek', 'merk'],
        'stock' => ['stock', 'stok', 'qty', 'jumlah'],
        'min_stock' => ['min_stock', 'min stok', 'minimal stok', 'stok minimal', 'min. stok', 'min stock'],
        'purchase_price' => ['purchase_price', 'harga beli', 'modal', 'harga modal', 'harga_beli'],
        'description' => ['description', 'deskripsi', 'keterangan'],
        'is_active' => ['is_active', 'aktif', 'status', 'is active'],
    ];

    /**
     * @param  array<int,array<int,mixed>>  $rows  Baris mentah; baris pertama = header.
     * @return array{created:int,updated:int,failed:array<int,array{row:int,message:string}>,price_types:array<int,string>}
     */
    public function import(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $failed = [];

        $rows = array_values(array_filter($rows, fn ($r) => is_array($r)));
        if (count($rows) < 2) {
            return ['created' => 0, 'updated' => 0, 'failed' => [['row' => 0, 'message' => 'File kosong atau tanpa data.']], 'price_types' => []];
        }

        // Master tipe harga aktif: code & name (lowercase) -> code.
        $priceTypes = PriceType::query()->where('is_active', true)->get(['code', 'name']);
        $priceTypeLookup = [];
        foreach ($priceTypes as $pt) {
            $priceTypeLookup[$this->norm($pt->code)] = $pt->code;
            $priceTypeLookup[$this->norm($pt->name)] = $pt->code;
        }

        // Petakan kolom.
        $headers = array_map(fn ($h) => $this->norm($h), $rows[0]);
        $colMap = []; // index => ['field'=>x] | ['price'=>code]
        $usedPriceTypes = [];
        foreach ($headers as $i => $h) {
            if ($h === '') {
                continue;
            }
            $field = $this->matchField($h);
            if ($field) {
                $colMap[$i] = ['field' => $field];
            } elseif (isset($priceTypeLookup[$h])) {
                $code = $priceTypeLookup[$h];
                $colMap[$i] = ['price' => $code];
                $usedPriceTypes[$code] = true;
            }
        }

        if (! collect($colMap)->contains(fn ($c) => ($c['field'] ?? null) === 'name')) {
            return ['created' => 0, 'updated' => 0, 'failed' => [['row' => 1, 'message' => 'Kolom "name/nama" wajib ada di header.']], 'price_types' => array_keys($usedPriceTypes)];
        }

        for ($r = 1; $r < count($rows); $r++) {
            $row = $rows[$r];
            $excelRow = $r + 1; // nomor baris di file (1-based)

            // Lewati baris kosong.
            if (collect($row)->every(fn ($v) => $v === null || trim((string) $v) === '')) {
                continue;
            }

            $data = ['fields' => [], 'prices' => []];
            foreach ($colMap as $i => $def) {
                $val = $row[$i] ?? null;
                if (isset($def['field'])) {
                    $data['fields'][$def['field']] = $val;
                } else {
                    $num = $this->parseNumber($val);
                    if ($num !== null) {
                        $data['prices'][$def['price']] = $num;
                    }
                }
            }

            $name = trim((string) ($data['fields']['name'] ?? ''));
            if ($name === '') {
                $failed[] = ['row' => $excelRow, 'message' => 'Nama produk kosong.'];
                continue;
            }

            try {
                $result = $this->saveRow($data);
                $result === 'created' ? $created++ : $updated++;
            } catch (\Throwable $e) {
                $failed[] = ['row' => $excelRow, 'message' => $e->getMessage()];
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => $failed, 'price_types' => array_keys($usedPriceTypes)];
    }

    /** @return 'created'|'updated' */
    private function saveRow(array $data): string
    {
        return DB::transaction(function () use ($data) {
            $f = $data['fields'];
            $sku = isset($f['sku']) ? trim((string) $f['sku']) : '';

            $attributes = [
                'name' => trim((string) $f['name']),
                'brand' => isset($f['brand']) && trim((string) $f['brand']) !== '' ? trim((string) $f['brand']) : null,
                'description' => isset($f['description']) && trim((string) $f['description']) !== '' ? trim((string) $f['description']) : null,
                'stock' => (int) ($this->parseNumber($f['stock'] ?? null) ?? 0),
                'min_stock' => (int) ($this->parseNumber($f['min_stock'] ?? null) ?? 0),
                'purchase_price' => $this->parseNumber($f['purchase_price'] ?? null) ?? 0,
                'is_active' => $this->parseBool($f['is_active'] ?? null),
            ];

            $isCreated = true;
            if ($sku !== '') {
                $existing = Product::where('sku', $sku)->first();
                $isCreated = $existing === null;
                $product = Product::updateOrCreate(['sku' => $sku], $attributes);
            } else {
                $product = Product::create($attributes);
            }

            foreach ($data['prices'] as $code => $price) {
                $product->prices()->updateOrCreate(['price_type' => $code], ['price' => $price]);
            }

            $this->ensureSingleDefault($product);

            return $isCreated ? 'created' : 'updated';
        });
    }

    /** Pastikan tepat satu harga default (prioritas 'umum'). */
    private function ensureSingleDefault(Product $product): void
    {
        $prices = $product->prices()->get();
        if ($prices->isEmpty()) {
            return;
        }

        $current = $prices->firstWhere('is_active_default', true);
        if ($current) {
            return; // sudah ada default valid
        }

        $defaultCode = $prices->contains('price_type', 'umum') ? 'umum' : $prices->first()->price_type;
        $product->prices()->update(['is_active_default' => false]);
        $product->prices()->where('price_type', $defaultCode)->update(['is_active_default' => true]);
    }

    private function matchField(string $header): ?string
    {
        foreach ($this->fieldAliases as $field => $aliases) {
            if (in_array($header, array_map(fn ($a) => $this->norm($a), $aliases), true)) {
                return $field;
            }
        }

        return null;
    }

    private function norm(mixed $v): string
    {
        return trim(preg_replace('/\s+/', ' ', strtolower((string) $v)));
    }

    /** Angka rupiah: buang pemisah ribuan; kosong -> null. */
    private function parseNumber(mixed $v): ?float
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }
        $clean = preg_replace('/[^0-9\-]/', '', $s);
        if ($clean === '' || $clean === '-') {
            return null;
        }

        return (float) $clean;
    }

    private function parseBool(mixed $v): bool
    {
        if ($v === null || trim((string) $v) === '') {
            return true;
        }

        return in_array(strtolower(trim((string) $v)), ['1', 'ya', 'y', 'aktif', 'true', 'active', 'yes'], true);
    }
}
