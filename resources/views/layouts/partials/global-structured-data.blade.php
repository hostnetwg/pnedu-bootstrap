@php
    $baseUrl = rtrim((string) config('app.url'), '/');
    $orgId = $baseUrl.'/#organization';
    $websiteId = $baseUrl.'/#website';

    $globalSchema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'WebSite',
                '@id' => $websiteId,
                'name' => config('app.name'),
                'alternateName' => 'pnedu.pl',
                'url' => $baseUrl.'/',
                'inLanguage' => 'pl-PL',
                'description' => config('seo.default_description'),
                'publisher' => ['@id' => $orgId],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => route('blog.index').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type' => 'EducationalOrganization',
                '@id' => $orgId,
                'name' => config('app.name'),
                'legalName' => config('seo.organization.legal_name'),
                'url' => $baseUrl.'/',
                'logo' => config('seo.organization.logo'),
                'image' => config('seo.organization.logo'),
                'email' => config('seo.organization.email'),
                'telephone' => config('seo.organization.telephone'),
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => config('seo.organization.address.street'),
                    'addressLocality' => config('seo.organization.address.locality'),
                    'postalCode' => config('seo.organization.address.postal_code'),
                    'addressCountry' => config('seo.organization.address.country'),
                ],
                'sameAs' => array_values(array_filter(config('seo.organization.same_as', []))),
            ],
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($globalSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
