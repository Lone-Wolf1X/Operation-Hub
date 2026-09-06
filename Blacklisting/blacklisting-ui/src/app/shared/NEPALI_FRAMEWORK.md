# Nepali Text Framework — Developer Guide

## How It Works

```
User types Preeti keystrokes → [preetiInput] directive
         ↓ visual: Preeti font CSS (glyphs show correctly)
         ↓ model: preetiToUnicode() converts to Unicode
         ↓ DB stores Unicode (UTF-8)
         ↓ PDF/Doc: NepaliTextHelper::unicodeToPreeti() → Preeti font output
```

---

## Step 1 — Install Preeti Font

Download `Preeti.ttf` and place it at:
```
blacklisting-ui/src/assets/fonts/Preeti.ttf
```
Sources:
- Copy from `C:\Windows\Fonts\Preeti.ttf` on any Windows machine
- Or download from: https://github.com/Shuvayatra/preeti-unicode-convertor

---

## Step 2 — Use in Angular Component

```typescript
import { PreetiInputDirective, NepaliDatePipe,
         UnicodeToPreetiPipe, NepaliDigitsPipe } from '../shared';

@Component({
  imports: [
    PreetiInputDirective,
    UnicodeToPreetiPipe,
    NepaliDatePipe,
    NepaliDigitsPipe,
  ]
})
```

### Input Field (Preeti typing → Unicode model)
```html
<!-- User sees Preeti font, model gets Unicode -->
<label class="preeti-input-badge">ठेगाना</label>
<input type="text" preetiInput [(ngModel)]="address_nepali">

<!-- In reactive forms -->
<input type="text" preetiInput formControlName="address_nepali">
```

### Display / Report Preview (Unicode from DB → Preeti rendering)
```html
<span class="preeti-text">{{ dbValue | unicodeToPreeti }}</span>
```

### Date Conversion
```html
<!-- "२०८३ भाद्र २१" -->
{{ caseDate | nepaliDate }}

<!-- "२०८३-०५-२१" -->
{{ caseDate | nepaliDate:'numeric' }}
```

### Number Conversion
```html
<!-- "१,५०,०००" -->
{{ amount | nepaliDigits }}
```

---

## Step 3 — Use in PHP (DocumentService / PDF)

```php
use App\Helpers\NepaliTextHelper;

// Unicode from DB → Preeti for Word/PDF output
$preetiName = NepaliTextHelper::unicodeToPreeti($entity->name_nepali);

// AD date → BS Nepali (for document headers)
$bsDate = NepaliTextHelper::adToBSNepali(now());
// → "२०८३ भाद्र २१"

// Digits
$digits = NepaliTextHelper::toNepaliDigits('2083');
// → "२०८३"
```

---

## File Locations

| File | Purpose |
|------|---------|
| `src/app/shared/nepali-text.service.ts` | Core Angular service |
| `src/app/shared/preeti-input.directive.ts` | `[preetiInput]` directive |
| `src/app/shared/nepali.pipe.ts` | Pipes: `unicodeToPreeti`, `nepaliDate`, `nepaliDigits` |
| `src/app/shared/index.ts` | Barrel export |
| `src/assets/fonts/Preeti.ttf` | Font file (**you must place this**) |
| `src/app/app.scss` | `@font-face` registration |
| `blacklisting-api/app/Helpers/NepaliTextHelper.php` | PHP backend converter |

---

## npm Packages Used

| Package | Purpose |
|---------|---------|
| `nepali-keyboard` | Preeti → Unicode conversion (frontend) |
| `nepali-date-converter` | AD ↔ BS date conversion |
