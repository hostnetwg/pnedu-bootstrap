<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Odczyt ustawień widoczności opcji płatności (tabela w bazie pneadm).
 * Zapis odbywa się w panelu adm (pneadm-bootstrap).
 */
class PaymentDisplayOption extends Model
{
    protected $connection = 'pneadm';

    protected $table = 'payment_display_options';

    protected $casts = [
        'show_pay_publigo' => 'boolean',
        'show_pay_online' => 'boolean',
        'show_deferred_order' => 'boolean',
        'show_order_form' => 'boolean',
        'show_order_form_alt' => 'boolean',
        'order_form_auto_fill_test_data' => 'boolean',
        'order_form_auto_fill_test_data_enabled_at' => 'datetime',
        'default_post_end_access_duration_value' => 'integer',
    ];

    /**
     * Zwraca tablicę flag widoczności (dla widoku). W razie błędu lub braku tabeli – wszystkie true.
     */
    public static function getForCoursePage(): array
    {
        try {
            $row = self::first();
            if ($row) {
                $autoFillEnabled = (bool) ($row->order_form_auto_fill_test_data ?? false);
                $enabledAt = $row->order_form_auto_fill_test_data_enabled_at;

                if (app()->environment('production') && $autoFillEnabled) {
                    $isExpired = true;
                    if ($enabledAt instanceof Carbon) {
                        $isExpired = $enabledAt->lt(now()->subMinute());
                    }

                    if ($isExpired) {
                        $autoFillEnabled = false;
                        $row->forceFill([
                            'order_form_auto_fill_test_data' => false,
                            'order_form_auto_fill_test_data_enabled_at' => null,
                        ])->save();
                    }
                }

                return [
                    'show_pay_publigo' => (bool) $row->show_pay_publigo,
                    'show_pay_online' => (bool) $row->show_pay_online,
                    'show_deferred_order' => (bool) $row->show_deferred_order,
                    'show_order_form' => (bool) $row->show_order_form,
                    'show_order_form_alt' => (bool) $row->show_order_form_alt,
                    'order_form_auto_fill_test_data' => $autoFillEnabled,
                    'default_post_end_access_duration_value' => (int) ($row->default_post_end_access_duration_value ?? 2),
                    'default_post_end_access_duration_unit' => (string) ($row->default_post_end_access_duration_unit ?? 'months'),
                ];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'show_pay_publigo' => true,
            'show_pay_online' => true,
            'show_deferred_order' => true,
            'show_order_form' => true,
            'show_order_form_alt' => true,
            'order_form_auto_fill_test_data' => false,
            'default_post_end_access_duration_value' => 2,
            'default_post_end_access_duration_unit' => 'months',
        ];
    }
}
