<?php

namespace Tests\Unit;

use App\Support\OrderFormCustomerProfile;
use Tests\TestCase;

class OrderFormCustomerProfileTest extends TestCase
{
    public function test_no_buyer_nip_means_person(): void
    {
        $this->assertSame(
            OrderFormCustomerProfile::PERSON,
            OrderFormCustomerProfile::fromBuyerAndRecipient(null, '5252445767', 'Urząd')
        );
        $this->assertSame(
            OrderFormCustomerProfile::PERSON,
            OrderFormCustomerProfile::fromBuyerAndRecipient('', null, null)
        );
    }

    public function test_buyer_and_recipient_means_school(): void
    {
        $this->assertSame(
            OrderFormCustomerProfile::SCHOOL,
            OrderFormCustomerProfile::fromBuyerAndRecipient('5252445767', '1132321669', null)
        );
        $this->assertSame(
            OrderFormCustomerProfile::SCHOOL,
            OrderFormCustomerProfile::fromBuyerAndRecipient('5252445767', null, 'Szkoła Podstawowa nr 1')
        );
    }

    public function test_buyer_nip_only_means_organisation(): void
    {
        $this->assertSame(
            OrderFormCustomerProfile::ORGANISATION,
            OrderFormCustomerProfile::fromBuyerAndRecipient('5252445767', null, null)
        );
        $this->assertSame(
            OrderFormCustomerProfile::ORGANISATION,
            OrderFormCustomerProfile::fromBuyerAndRecipient('525-244-57-67', ' ', '')
        );
    }

    public function test_buyer_type_for_profile(): void
    {
        $this->assertSame('person', OrderFormCustomerProfile::buyerTypeForProfile('person'));
        $this->assertSame('organisation', OrderFormCustomerProfile::buyerTypeForProfile('school'));
        $this->assertSame('organisation', OrderFormCustomerProfile::buyerTypeForProfile('organisation'));
    }
}
