<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalDocumentsTest extends TestCase
{
    public function test_current_and_versioned_terms_are_public(): void
    {
        $this->get(route('regulamin'))
            ->assertOk()
            ->assertSee('Regulamin pnedu.pl')
            ->assertSee(config('legal.terms.current_version'));

        $this->get(route('regulamin.version', config('legal.terms.current_version')))
            ->assertOk()
            ->assertSee('Zamówienie z obowiązkiem zapłaty');
    }

    public function test_withdrawal_and_article_fourteen_information_are_public(): void
    {
        $this->get(route('withdrawal'))
            ->assertOk()
            ->assertSee('Odstąpienie od umowy');

        $this->get(route('rodo.art14'))
            ->assertOk()
            ->assertSee('Dane otrzymaliśmy od podmiotu', false);
    }

    public function test_unknown_terms_version_is_not_found(): void
    {
        $this->get('/regulamin/2000-01-01')->assertNotFound();
    }

    public function test_unpublished_draft_terms_are_not_public(): void
    {
        $this->get('/regulamin/draft-pending-approval')->assertNotFound();
        $this->get('/regulamin/draft-pending-approval.pdf')->assertNotFound();
    }

    public function test_withdrawal_covers_recorded_courses(): void
    {
        $this->get(route('withdrawal'))
            ->assertOk()
            ->assertSee('kursów nagranych')
            ->assertSee('14 dni od jej zawarcia');
    }
}
