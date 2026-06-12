<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Support\AppSettings;
use App\Support\ColorPalette;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function __construct(private readonly AppSettings $settings) {}

    /** Nilai konfigurasi yang dapat diedit (+ URL logo siap pakai). */
    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->payload()]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['logo', 'remove_logo']);

        if (array_key_exists('primary_color', $data)) {
            $data['primary_color'] = ColorPalette::normalize($data['primary_color']);
        }

        // Logo: unggah baru menggantikan lama, atau hapus bila diminta.
        if ($request->hasFile('logo')) {
            $this->deleteLogoFile();
            $data['store_logo'] = $request->file('logo')->store('settings', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogoFile();
            $data['store_logo'] = '';
        }

        $this->settings->setMany($data);

        return response()->json([
            'data' => $this->payload(),
            'message' => 'Konfigurasi berhasil disimpan',
        ]);
    }

    public function destroyLogo(): JsonResponse
    {
        $this->deleteLogoFile();
        $this->settings->setMany(['store_logo' => '']);

        return response()->json([
            'data' => $this->payload(),
            'message' => 'Logo dihapus',
        ]);
    }

    private function deleteLogoFile(): void
    {
        $path = $this->settings->logoPath();
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function payload(): array
    {
        return [
            'store_name' => $this->settings->storeName(),
            'store_address' => $this->settings->storeAddress(),
            'store_phone' => $this->settings->storePhone(),
            'receipt_footer' => $this->settings->receiptFooter(),
            'primary_color' => $this->settings->primaryColor(),
            'logo_url' => $this->settings->logoUrl(),
        ];
    }
}
