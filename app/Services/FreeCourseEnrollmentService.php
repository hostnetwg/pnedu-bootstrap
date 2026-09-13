<?php

namespace App\Services;

use App\Models\OnlineCourse;
use App\Models\OnlineCourseEnrollment;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Notifications\OnlineCourseProductAccessGranted;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FreeCourseEnrollmentService
{
    public const ACCESS_SOURCE = 'free_signup';

    /**
     * @return array{enrollment: OnlineCourseEnrollment, created: bool, already_had_access: bool}
     */
    public function enroll(Product $product, ProductPrice $price, array $participant): array
    {
        $price->refresh();
        $this->assertComplimentaryVariant($product, $price);

        $email = OnlineCourseEnrollment::normalizeEmail($participant['email']);
        if ($email === null) {
            throw ValidationException::withMessages([
                'email' => 'Podaj prawidłowy adres e-mail.',
            ]);
        }

        $course = $product->onlineCourse;
        abort_unless($course instanceof OnlineCourse, 404);

        $existing = OnlineCourseEnrollment::query()
            ->where('online_course_id', $course->id)
            ->where('email', $email)
            ->first();

        if ($existing && $this->hasUsableAccess($existing)) {
            return [
                'enrollment' => $existing,
                'created' => false,
                'already_had_access' => true,
            ];
        }

        [$user, $userExisted] = $this->findOrCreateUser(
            $email,
            $participant['first_name'],
            $participant['last_name']
        );

        $enrollment = DB::connection('pneadm')->transaction(function () use (
            $course,
            $price,
            $email,
            $participant

        ) {
            $enrollment = OnlineCourseEnrollment::query()
                ->where('online_course_id', $course->id)
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if ($enrollment && $this->hasUsableAccess($enrollment)) {
                return $enrollment;
            }

            $previousExpiry = $enrollment?->access_expires_at
                ? CarbonImmutable::instance($enrollment->access_expires_at)->utc()
                : null;
            $newExpiry = app(ProductAccessExpiryService::class)
                ->resolveForPrice($price, $enrollment !== null, $previousExpiry);

            if (! $enrollment) {
                $enrollment = new OnlineCourseEnrollment([
                    'online_course_id' => $course->id,
                    'email' => $email,
                ]);
            }

            $enrollment->first_name = $participant['first_name'];
            $enrollment->last_name = $participant['last_name'];
            $enrollment->phone = $participant['phone'] ?? null;
            $enrollment->access_expires_at = $newExpiry;
            $enrollment->access_starts_at = $this->resolveEnrollmentStartAt($price, $enrollment);
            if (filled($price->access_note)) {
                $enrollment->access_note = $price->access_note;
            }
            $enrollment->access_source = self::ACCESS_SOURCE;
            $enrollment->save();

            return $enrollment;
        });

        $alreadyHadAccess = $existing !== null && $this->hasUsableAccess($existing);
        if (! $alreadyHadAccess) {
            $token = $userExisted ? null : Password::createToken($user);
            $user->notify(new OnlineCourseProductAccessGranted(
                $course->title,
                $token,
                null,
                $participant['first_name'],
                $participant['last_name'],
                $email
            ));
        }

        return [
            'enrollment' => $enrollment->fresh(),
            'created' => ! $userExisted,
            'already_had_access' => $alreadyHadAccess,
        ];
    }

    private function assertComplimentaryVariant(Product $product, ProductPrice $price): void
    {
        $price->loadMissing('offer');
        abort_unless($price->isComplimentary() && $price->is_active, 404);
        abort_unless((int) $price->offer?->product_id === (int) $product->id, 404);
        abort_unless($price->offer?->is_active && $price->offer?->is_public, 404);
    }

    private function hasUsableAccess(OnlineCourseEnrollment $enrollment): bool
    {
        return ! $enrollment->hasExpiredAccess();
    }

    /**
     * @return array{0: User, 1: bool}
     */
    private function findOrCreateUser(string $email, string $firstName, string $lastName): array
    {
        return DB::transaction(function () use ($email, $firstName, $lastName) {
            $user = User::query()->where('email', $email)->lockForUpdate()->first();
            if ($user) {
                return [$user, true];
            }

            $user = new User;
            $user->forceFill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => Hash::make(Str::password(48)),
                'email_verified_at' => now('UTC'),
            ]);
            $user->save();

            return [$user, false];
        });
    }

    private function resolveEnrollmentStartAt(ProductPrice $price, OnlineCourseEnrollment $enrollment): ?CarbonImmutable
    {
        $now = CarbonImmutable::now('UTC');
        $scheduled = $price->access_starts_at
            ? CarbonImmutable::instance($price->access_starts_at)
            : null;
        if ($scheduled && $scheduled->utc()->lte($now)) {
            $scheduled = null;
        }

        $existing = $enrollment->exists && $enrollment->access_starts_at
            ? CarbonImmutable::instance($enrollment->access_starts_at)
            : null;
        if ($existing && $existing->utc()->lte($now)) {
            return null;
        }

        return $existing ?: $scheduled;
    }
}
