<?php

namespace App\Services\Support;

use Illuminate\Support\Carbon;

/**
 * Generator nomor dokumen berurutan dengan counter harian:
 *   PREFIX-YYYYMMDD-XXXXX  (mis. INV-20260609-00001, SUP-..., OPN-..., RTN-...)
 *
 * WAJIB dipanggil di dalam DB::transaction pemanggil agar lockForUpdate efektif
 * (mencegah race pada counter).
 */
class SequenceGenerator
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    public function next(string $modelClass, string $column, string $prefix, ?Carbon $date = null): string
    {
        $date ??= Carbon::now();
        $fullPrefix = $prefix . '-' . $date->format('Ymd') . '-';

        $last = $modelClass::query()
            ->where($column, 'like', $fullPrefix . '%')
            ->lockForUpdate()
            ->orderByDesc($column)
            ->value($column);

        $seq = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $fullPrefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
