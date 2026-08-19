<?php

namespace App\Services;

use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Support\Str;

class VendorOnboardingService
{
    /**
     * Register a user as a vendor (starts as pending, not verified).
     */
    public function register(User $user, array $data): VendorProfile
    {
        if ($user->vendorProfile) {
            throw new \RuntimeException('هذا المستخدم لديه بالفعل حساب تاجر.');
        }

        $vendor = VendorProfile::create([
            'user_id' => $user->id,
            'store_name' => $data['store_name'],
            'store_slug' => $this->generateUniqueSlug($data['store_name']),
            'store_logo' => $data['store_logo'] ?? null,
            'store_cover' => $data['store_cover'] ?? null,
            'bio' => $data['bio'] ?? null,
            'phone' => $data['phone'] ?? $user->phone,
            'is_verified' => false,
            'commission_rate' => $data['commission_rate'] ?? config('marketplace.default_commission_rate', 10),
            'status' => 'pending',
        ]);

        if (! $user->hasRole('vendor')) {
            $user->assignRole('vendor');
        }

        return $vendor;
    }

    /**
     * Approve the vendor — this is the ONLY verification checkpoint in the
     * system. Once is_verified = true, the vendor can self-issue
     * certificates for their own products/services without further review.
     */
    public function approve(VendorProfile $vendor, User $admin): VendorProfile
    {
        $vendor->update([
            'status' => 'approved',
            'is_verified' => true,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return $vendor->fresh();
    }

    public function reject(VendorProfile $vendor, string $reason): VendorProfile
    {
        $vendor->update([
            'status' => 'rejected',
            'is_verified' => false,
            'rejection_reason' => $reason,
        ]);

        return $vendor->fresh();
    }

    public function suspend(VendorProfile $vendor, string $reason): VendorProfile
    {
        $vendor->update([
            'status' => 'suspended',
            'is_verified' => false,
            'rejection_reason' => $reason,
        ]);

        return $vendor->fresh();
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (VendorProfile::where('store_slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
