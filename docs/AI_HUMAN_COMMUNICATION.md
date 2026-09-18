# Forma komunikacji: Waldemar – ChatGPT – Cursor Agent AI

**Kanon (pełna treść):** `pneadm/docs/AI_HUMAN_COMMUNICATION.md`

Zasada obowiązuje w projekcie `pnedu` i na każdym komputerze deweloperskim.

## Skrót

**Role:** Waldemar — decyzje; ChatGPT — konsultant tylko na żądanie; Cursor — kod, testy, fakty z repo.

**Zasada główna:** najpierw pytania i decyzje, potem implementacja. Pytania decyzyjne **przed** kodowaniem, nie na końcu etapu.

**Tryby:** A wdrażaj | B pytaj i zatrzymaj | C diagnozuj.

**Po znaczącym etapie:** raport 11-punktowy + podsumowanie dla Waldemara. Prompt do ChatGPT przygotowuj tylko wtedy, gdy Waldemar wyraźnie napisze, że robi zewnętrzną konsultację i poprosi o taki prompt.

**Wersje:** po znaczącym etapie zasugeruj wpis w `CHANGELOG.md` (hotfix do bieżącej wersji albo nowy numer). Numer tylko po potwierdzeniu Waldemara. Widok w ADM, nie na froncie pnedu.pl.

**Deploy analityki:** migracje `pneadm` → potem `pnedu` → test → backfill.

Pełny opis: `pneadm/docs/AI_HUMAN_COMMUNICATION.md`.
