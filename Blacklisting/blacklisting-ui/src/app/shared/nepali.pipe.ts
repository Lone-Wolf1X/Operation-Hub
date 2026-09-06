/**
 * nepali.pipe.ts
 * ─────────────────────────────────────────────────────────────────────────
 * Angular Pipes for Nepali Text & Date
 *
 * Pipes available:
 *   {{ value | unicodeToPreeti }}  — Convert Unicode→Preeti for display
 *   {{ value | nepaliDate }}       — Convert AD Date → BS Nepali string
 *   {{ value | nepaliDigits }}     — Convert ASCII digits → Devanagari digits
 * ─────────────────────────────────────────────────────────────────────────
 */

import { Pipe, PipeTransform } from '@angular/core';
import { NepaliTextService } from './nepali-text.service';

// ─── Pipe 1: Unicode → Preeti ─────────────────────────────────────────────
/**
 * Converts Unicode Devanagari to Preeti ASCII for display in Preeti font.
 *
 * @example
 *  <span style="font-family: Preeti">{{ dbValue | unicodeToPreeti }}</span>
 */
@Pipe({ name: 'unicodeToPreeti', standalone: true })
export class UnicodeToPreetiPipe implements PipeTransform {
  constructor(private nepali: NepaliTextService) {}
  transform(value: string | null | undefined): string {
    if (!value) return '';
    return this.nepali.unicodeToPreeti(value);
  }
}

// ─── Pipe 2: AD Date → BS Nepali String ──────────────────────────────────
/**
 * Converts a JS Date or ISO string to Nepali BS date string.
 * Default format: "२०८३ भाद्र २१" (Devanagari year + month name + day)
 *
 * @example
 *  {{ case.notice_date | nepaliDate }}   → "२०८३ भाद्र २१"
 *  {{ case.notice_date | nepaliDate:'numeric' }}  → "२०८३-०५-२१"
 */
@Pipe({ name: 'nepaliDate', standalone: true })
export class NepaliDatePipe implements PipeTransform {
  constructor(private nepali: NepaliTextService) {}
  transform(value: Date | string | null | undefined, format: 'full' | 'numeric' = 'full'): string {
    if (!value) return '';
    if (format === 'numeric') {
      const bs = this.nepali.adToBS(value);
      return this.nepali.toNepaliDigits(bs);
    }
    return this.nepali.adToBSNepali(value);
  }
}

// ─── Pipe 3: ASCII digits → Devanagari digits ────────────────────────────
/**
 * Converts ASCII digits to Nepali Devanagari digits for display.
 *
 * @example
 *  {{ '2026' | nepaliDigits }}   → "२०२६"
 *  {{ amount | nepaliDigits }}   → "१,५०,०००"
 */
@Pipe({ name: 'nepaliDigits', standalone: true })
export class NepaliDigitsPipe implements PipeTransform {
  constructor(private nepali: NepaliTextService) {}
  transform(value: string | number | null | undefined): string {
    if (value === null || value === undefined) return '';
    return this.nepali.toNepaliDigits(value.toString());
  }
}
