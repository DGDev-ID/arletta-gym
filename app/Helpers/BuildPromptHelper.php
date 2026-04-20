<?php

namespace App\Helpers;

use App\Http\Services\OpenAIService;
use App\Helpers\S3Helper;
use Illuminate\Http\UploadedFile;

class BuildPromptHelper
{
    public static function run(UploadedFile $ktpImg)
    {
        // Upload sementara ke temp (biar konsisten dengan sistem kamu)
        $tempImg = S3Helper::storeFileTemp($ktpImg);

        $prompt = "
        Analisa gambar ini.

        Jika ini BUKAN KTP Indonesia, jawab:
        {\"is_ktp\": false}

        Jika ini adalah KTP Indonesia, ekstrak data berikut dalam format JSON:
        {
            \"is_ktp\": true,
            \"nik\": \"\",
            \"nama\": \"\",
            \"tempat_lahir\": \"\",
            \"tanggal_lahir\": \"\",
            \"jenis_kelamin\": \"\",
            \"alamat\": \"\",
            \"rt_rw\": \"\",
            \"kelurahan\": \"\",
            \"kecamatan\": \"\",
            \"agama\": \"\",
            \"status_perkawinan\": \"\",
            \"pekerjaan\": \"\",
            \"kewarganegaraan\": \"\"
        }

        Pastikan hanya return JSON tanpa teks tambahan.
        ";

        $payload = [
            'prompt' => $prompt,
            'temp_images' => [$tempImg],
        ];

        try {
            $result = OpenAIService::run($payload);

            $analysis = $result['analysis'] ?? null;

            if (!$analysis || !isset($analysis['is_ktp'])) {
                return false;
            }

            if ($analysis['is_ktp'] === false) {
                return false;
            }

            return $analysis;

        } catch (\Throwable $e) {
            throw $e;
        } finally {
            S3Helper::removeFileTemp($tempImg);
        }
    }
}