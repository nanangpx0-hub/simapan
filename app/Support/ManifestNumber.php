<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DocumentManifest;
use Illuminate\Support\Facades\DB;

final class ManifestNumber
{
    public static function next(?string $date = null): string
    {
        $day = $date ?? now()->format('Ymd');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $sequence = DB::transaction(function () use ($day): int {
                $last = DocumentManifest::query()
                    ->where('manifest_number', 'like', 'DM-'.$day.'-%')
                    ->lockForUpdate()
                    ->orderByDesc('manifest_number')
                    ->first();

                if (! $last instanceof DocumentManifest) {
                    return 1;
                }

                return (int) mb_substr((string) $last->manifest_number, -3) + 1;
            });

            $candidate = 'DM-'.$day.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);

            if (! DocumentManifest::query()->where('manifest_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Gagal membuat nomor manifest unik.');
    }

    /**
     * Nomor BAST penyerahan pemutakhiran: BAST-P-SUSENAS/YYYYMM/XXXX.
     */
    public static function nextUpdating(?string $month = null): string
    {
        $period = $month ?? now()->format('Ym');
        $prefix = 'BAST-P-SUSENAS/'.$period.'/';

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $sequence = DB::transaction(function () use ($prefix): int {
                $last = DocumentManifest::query()
                    ->where('manifest_number', 'like', $prefix.'%')
                    ->lockForUpdate()
                    ->orderByDesc('manifest_number')
                    ->first();

                if (! $last instanceof DocumentManifest) {
                    return 1;
                }

                return (int) mb_substr((string) $last->manifest_number, -4) + 1;
            });

            $candidate = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            if (! DocumentManifest::query()->where('manifest_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Gagal membuat nomor BAST pemutakhiran unik.');
    }
}
