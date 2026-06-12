<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Penyimpanan key-value untuk konfigurasi aplikasi (nama toko, logo, warna, dll).
 * Dibaca terpusat lewat App\Support\AppSettings (dengan cache), bukan query langsung.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = true;
}
