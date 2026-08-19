<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CertificationService
{
    /**
     * Issue a certificate for a product or service.
     *
     * Trust model: a vendor is verified ONCE at onboarding
     * (VendorProfile::is_verified). After that, any certificate the vendor
     * issues for their own product/service is accepted immediately —
     * there is no separate per-certificate admin review step.
     * Admins can always issue certificates directly for anything.
     */
    public function issue(Model $certifiable, User $issuer, array $data): Certificate
    {
        $this->assertCertifiableType($certifiable);
        $this->assertIssuerAllowed($certifiable, $issuer);

        $certificateNumber = $this->generateCertificateNumber();

        $certificate = Certificate::create([
            'certifiable_type' => $certifiable::class,
            'certifiable_id' => $certifiable->id,
            'issued_by_id' => $issuer->id,
            'certificate_number' => $certificateNumber,
            'meteorite_type' => $data['meteorite_type'] ?? null,
            'origin_location' => $data['origin_location'] ?? null,
            'discovery_date' => $data['discovery_date'] ?? null,
            'analysis_report_path' => $data['analysis_report_path'] ?? null,
            'issued_at' => now(),
        ]);

        $certificate->qr_code_path = $this->generateQrCode($certificate);
        $certificate->save();

        return $certificate;
    }

    /**
     * Public verification — used by the QR scan endpoint (no auth).
     */
    public function verify(string $certificateNumber, ?string $ip = null, ?string $userAgent = null): ?Certificate
    {
        $certificate = Certificate::with(['certifiable', 'issuedBy'])
            ->where('certificate_number', $certificateNumber)
            ->first();

        if ($certificate) {
            $certificate->verifications()->create([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'scanned_at' => now(),
            ]);
        }

        return $certificate;
    }

    private function assertCertifiableType(Model $certifiable): void
    {
        if (! $certifiable instanceof Product && ! $certifiable instanceof Service) {
            throw new \InvalidArgumentException('الشهادة تُصدر فقط لمنتج أو خدمة.');
        }
    }

    private function assertIssuerAllowed(Model $certifiable, User $issuer): void
    {
        if ($issuer->isAdmin()) {
            return;
        }

        $vendorProfile = $issuer->vendorProfile;

        if (! $vendorProfile || ! $vendorProfile->isApproved()) {
            throw new \RuntimeException('لازم تكون تاجر معتمد وموثّق عشان تصدر شهادة.');
        }

        if ((int) $certifiable->vendor_id !== (int) $vendorProfile->id) {
            throw new \RuntimeException('مينفعش تصدر شهادة لمنتج أو خدمة مش ملكك.');
        }
    }

    private function generateCertificateNumber(): string
    {
        do {
            $number = 'CERT-'.now()->format('Ym').'-'.strtoupper(Str::random(6));
        } while (Certificate::where('certificate_number', $number)->exists());

        return $number;
    }

    private function generateQrCode(Certificate $certificate): string
    {
        $verifyUrl = config('app.url').'/verify/'.$certificate->certificate_number;

        $qrSvg = QrCode::format('svg')->size(300)->generate($verifyUrl);

        $path = 'certificates/qr/'.$certificate->certificate_number.'.svg';
        Storage::disk('public')->put($path, $qrSvg);

        return $path;
    }
}
