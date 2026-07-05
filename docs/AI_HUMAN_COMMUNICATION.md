# Forma komunikacji: AI (Cursor) ↔ Człowiek ↔ ChatGPT

Kanoniczna wersja tego dokumentu znajduje się w projekcie `pneadm`:
`pneadm/docs/AI_HUMAN_COMMUNICATION.md`

Zasada obowiązuje również w projekcie `pnedu` i na każdym komputerze deweloperskim.

## Skrót zasady

Po KAŻDEJ zakończonej akcji/kroku asystent AI (Cursor) przygotowuje **dwa podsumowania**:

1. **Dla człowieka (proste)** — krótkie, prostym językiem, bez żargonu: co zrobiono, co to znaczy, co dalej.
2. **Techniczne jako prompt do ChatGPT** — szczegółowe, w bloku kodu Markdown do skopiowania; samowystarczalne (ChatGPT nie zna historii czatu), bez sekretów.

Na końcu zadawaj **kluczowe pytania**:
- decyzyjne/biznesowe → do człowieka (to on decyduje),
- techniczne/architektoniczne → do ChatGPT w prompcie.

**Następny rekomendowany krok** — jeden konkretny krok (bez wdrażania bez zgody).

Pełna struktura **tylko po znaczących krokach** (nie przy drobnych poprawkach).

**UI:** potwierdzenia tylko modal Bootstrap — `pneadm/docs/UI_MODALS.md`.

Pełny opis, kolejność i wyjątki: zob. `pneadm/docs/AI_HUMAN_COMMUNICATION.md`.
