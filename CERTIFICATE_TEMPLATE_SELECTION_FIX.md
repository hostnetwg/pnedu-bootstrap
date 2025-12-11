# ✅ Naprawa wyboru szablonu certyfikatu na podstawie course.certificate_template_id

## 🐛 Problem
Na `pnedu.pl` podczas generowania zaświadczenia nie był wybierany odpowiedni szablon przypisany w tabeli `courses` do tego szkolenia.

## 🔍 Analiza
Pakiet `CertificateGeneratorService` poprawnie:
1. ✅ Pobiera `certificate_template_id` z tabeli `courses` przez `leftJoin`
2. ✅ Pobiera `template_slug` z tabeli `certificate_templates` na podstawie `certificate_template_id`
3. ✅ Używa `template_slug` do wyboru odpowiedniego szablonu Blade w `TemplateRenderer`

Ale:
- ❌ `certificate_template_id` nie było dodawane do obiektu `course` w zwracanych danych
- ❌ `course_certificate_template_id` nie było zwracane na najwyższym poziomie danych

## ✅ Rozwiązanie
Dodano `certificate_template_id` do obiektu `course` i `course_certificate_template_id` do zwracanych danych:

```php
return [
    // ...
    'course_certificate_template_id' => $certificate->course_certificate_template_id ?? null,
    'course' => (object) [
        // ...
        'certificate_template_id' => $certificate->course_certificate_template_id ?? null,
    ],
    // ...
];
```

## 🔍 Weryfikacja
Przed naprawą:
- `Course certificate_template_id (from data): NULL`
- `Course->certificate_template_id: NULL`

Po naprawie:
- `Course certificate_template_id (from data): 5` ✅
- `Course->certificate_template_id: 5` ✅
- `Template slug: default-kopia` ✅

## 📝 Jak działa wybór szablonu

1. **Pobieranie danych z bazy**:
   ```sql
   SELECT courses.certificate_template_id, certificate_templates.slug
   FROM certificates
   JOIN courses ON certificates.course_id = courses.id
   LEFT JOIN certificate_templates ON courses.certificate_template_id = certificate_templates.id
   WHERE certificates.participant_id = ?
   ```

2. **Wybieranie szablonu**:
   - Jeśli `certificate_template_id` jest ustawione → używa `template_slug` z `certificate_templates`
   - Jeśli `certificate_template_id` jest NULL → używa domyślnego szablonu `default`

3. **Renderowanie**:
   - `TemplateRenderer` sprawdza, czy szablon istnieje w pakiecie (`pne-certificate-generator::certificates.{slug}`)
   - Jeśli nie istnieje w pakiecie, sprawdza w aplikacji (`certificates.{slug}`)
   - Jeśli nie istnieje, używa domyślnego szablonu

## ✅ Status
- ✅ `certificate_template_id` jest teraz dostępne w obiekcie `course`
- ✅ `course_certificate_template_id` jest dostępne na najwyższym poziomie danych
- ✅ Pakiet poprawnie wybiera szablon na podstawie `certificate_template_id` z tabeli `courses`
- ✅ Cache został wyczyszczony, aby upewnić się, że zmiany są widoczne

## 📝 Uwagi
- Jeśli szablon nadal nie jest wybierany poprawnie, sprawdź:
  1. Czy `courses.certificate_template_id` jest ustawione w bazie danych
  2. Czy `certificate_templates.id` odpowiada `courses.certificate_template_id`
  3. Czy `certificate_templates.slug` jest poprawny i odpowiada istniejącemu plikowi Blade
  4. Czy cache został wyczyszczony: `sail artisan cache:clear`








