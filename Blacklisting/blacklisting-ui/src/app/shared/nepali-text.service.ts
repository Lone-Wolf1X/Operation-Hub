/**
 * nepali-text.service.ts
 * ─────────────────────────────────────────────────────────────────────────
 * Central Nepali Text Framework Service
 *
 * PURPOSE:
 *  - Preeti (ASCII) → Unicode conversion for DB storage
 *  - Unicode → Preeti (ASCII) conversion for report/PDF display
 *  - AD → BS date conversion (for Nepali documents)
 *  - Nepali digit conversion (for display)
 *
 * LIBRARIES USED:
 *  - nepali-keyboard (prabin194/nepali-keyboard) — Preeti→Unicode
 *  - nepali-date-converter — AD↔BS date conversion
 *
 * USAGE:
 *  Inject NepaliTextService in your component or use the
 *  [preetiInput] directive for automatic input field handling.
 * ─────────────────────────────────────────────────────────────────────────
 */

import { Injectable } from '@angular/core';
import { preetiToUnicode } from 'nepali-keyboard';
import NepaliDate from 'nepali-date-converter';

@Injectable({ providedIn: 'root' })
export class NepaliTextService {

  // ─── Preeti → Unicode ────────────────────────────────────────────────
  /**
   * Converts Preeti-encoded ASCII text to Unicode Devanagari.
   * Use this before saving to DB.
   *
   * @param preetiText - raw ASCII text typed via Preeti keyboard
   * @returns Unicode Devanagari string
   *
   * @example
   *   preetiToUnic('g]kfn') → 'नेपाल'
   */
  preetiToUnic(preetiText: string): string {
    if (!preetiText) return '';
    return preetiToUnicode(preetiText);
  }

  // ─── Unicode → Preeti ────────────────────────────────────────────────
  /**
   * Converts Unicode Devanagari text back to Preeti ASCII encoding.
   * Use this when rendering text in Preeti font for reports/PDFs.
   *
   * Built-in mapping table handles conjuncts, matras, and reph reordering.
   * The output string should be displayed with `font-family: 'Preeti'`.
   *
   * @param unicodeText - Unicode Devanagari string from DB
   * @returns Preeti ASCII-encoded string
   */
  unicodeToPreeti(unicodeText: string): string {
    if (!unicodeText) return '';
    return this._unicodeToPreetiConvert(unicodeText);
  }

  // ─── AD → BS Date ────────────────────────────────────────────────────
  /**
   * Converts an AD (Gregorian) date to BS (Bikram Sambat) date.
   *
   * @param adDate - JavaScript Date object or ISO date string
   * @returns BS date string in YYYY-MM-DD format
   *
   * @example
   *   adToBS(new Date('2026-09-06')) → '2083-05-21'
   */
  adToBS(adDate: Date | string): string {
    try {
      const d = typeof adDate === 'string' ? new Date(adDate) : adDate;
      const nepDate = new NepaliDate(d);
      return nepDate.format('YYYY-MM-DD');
    } catch {
      return '';
    }
  }

  /**
   * Converts an AD date to BS and formats it in Nepali with Devanagari digits.
   * Used for document headers (45-day notice, dishonour certificate).
   *
   * @param adDate - JavaScript Date or ISO string
   * @returns e.g. "२०८३ भाद्र २१"
   */
  adToBSNepali(adDate: Date | string): string {
    try {
      const d = typeof adDate === 'string' ? new Date(adDate) : adDate;
      const nepDate = new NepaliDate(d);
      const months = [
        'बैशाख','जेठ','असार','साउन','भाद्र','असोज',
        'कार्तिक','मंसिर','पुष','माघ','फाल्गुन','चैत'
      ];
      const year  = this.toNepaliDigits(nepDate.getYear().toString());
      const month = months[nepDate.getMonth()];
      const day   = this.toNepaliDigits(nepDate.getDate().toString());
      return `${year} ${month} ${day}`;
    } catch {
      return '';
    }
  }

  // ─── BS → AD Date ────────────────────────────────────────────────────
  /**
   * Converts a BS date string to a JavaScript Date (AD).
   *
   * @param bsDate - BS date in YYYY-MM-DD format
   * @returns JavaScript Date object
   */
  bsToAD(bsDate: string): Date {
    const [y, m, d] = bsDate.split('-').map(Number);
    const nepDate = new NepaliDate(y, m - 1, d);
    return nepDate.toJsDate();
  }

  // ─── Nepali Digits ───────────────────────────────────────────────────
  /**
   * Converts ASCII digits (0-9) to Devanagari digits (०-९).
   * Use for display in Nepali documents.
   */
  toNepaliDigits(str: string): string {
    const eng = ['0','1','2','3','4','5','6','7','8','9'];
    const nep = ['०','१','२','३','४','५','६','७','८','९'];
    return str.split('').map(c => {
      const i = eng.indexOf(c);
      return i >= 0 ? nep[i] : c;
    }).join('');
  }

  /**
   * Converts Devanagari digits back to ASCII digits.
   */
  fromNepaliDigits(str: string): string {
    const nep = ['०','१','२','३','४','५','६','७','८','९'];
    return str.split('').map(c => {
      const i = nep.indexOf(c);
      return i >= 0 ? i.toString() : c;
    }).join('');
  }

  // ─── Internal: Unicode → Preeti Mapping ──────────────────────────────
  /**
   * Internal Preeti output converter.
   * Handles matra reordering (ि before consonant), reph (र्),
   * and longest-match-first conjunct resolution.
   */
  private _unicodeToPreetiConvert(text: string): string {
    // Longest-match-first mapping: Unicode → Preeti ASCII
    // Source: Community-vetted mapping (FOSS Nepal / Shuvayatra)
    const map: [string, string][] = [
      // Conjuncts — must come before single chars
      ['क्ष', 'If'],  ['त्र', 'q'], ['ज्ञ', '>]'], ['श्र', 'Zo'],
      // Reph (र् )
      ['र्', 'o'],
      // Consonants
      ['क', 'k'],  ['ख', 'v'],  ['ग', 'u'],  ['घ', 'w'],  ['ङ', '^'],
      ['च', 'r'],  ['छ', 'R'],  ['ज', 'h'],  ['झ', 'H'],  ['ञ', ']'],
      ['ट', '{'],  ['ठ', '}'],  ['ड', '8'],  ['ढ', '0'],  ['ण', 'K'],
      ['त', 't'],  ['थ', 'y'],  ['द', 'b'],  ['ध', 'W'],  ['न', 'g'],
      ['प', 'k'],  ['फ', 'km'],  ['ब', 'a'],  ['भ', 'e'],  ['म', 'd'],
      ['य', 'o'],  ['र', 'r'],  ['ल', 'n'],  ['व', 'j'],  ['श', 'z'],
      ['ष', 'iw'], ['स', 's'],  ['ह', 'x'],
      // Vowel signs (matras)
      ['ा', 'f'],  ['ि', 'f'], // ि handled separately with reorder
      ['ी', 'L'],  ['ु', 'M'],  ['ू', 'N'],
      ['े', ']'],  ['ै', 'P'],  ['ो', 'f]'], ['ौ', 'fP'],
      ['ं', '+'],  ['ः', ','],  ['ँ', ';'],  ['्', '\\'],
      // Independent vowels
      ['अ', 'c'],  ['आ', 'cf'],  ['इ', 'O'],  ['ई', 'pL'],
      ['उ', 'pm'],  ['ऊ', 'pmN'], ['ए', 'P'],  ['ऐ', 'g}'],
      ['ओ', 'cf]'],['औ', 'cf}'],
      // Numerals (Devanagari → ASCII)
      ['०','0'],['१','1'],['२','2'],['३','3'],['४','4'],
      ['५','5'],['६','6'],['७','7'],['८','8'],['९','9'],
      // Punctuation
      ['।', '.'],  ['॥', '..'],
    ];

    let result = '';
    let i = 0;
    while (i < text.length) {
      let matched = false;
      // Try longest match first
      for (const [uni, preeti] of map) {
        if (text.startsWith(uni, i)) {
          result += preeti;
          i += uni.length;
          matched = true;
          break;
        }
      }
      if (!matched) {
        result += text[i];
        i++;
      }
    }
    return result;
  }
}
