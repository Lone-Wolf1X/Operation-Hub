/**
 * shared/index.ts — Barrel Export
 * ─────────────────────────────────────────────────────────────────────────
 * Import everything Nepali-related from this single path:
 *
 *   import { NepaliTextService, PreetiInputDirective,
 *            UnicodeToPreetiPipe, NepaliDatePipe, NepaliDigitsPipe }
 *     from '../shared';
 *
 * In your standalone component's `imports: []` array, add:
 *   PreetiInputDirective, UnicodeToPreetiPipe, NepaliDatePipe, NepaliDigitsPipe
 * ─────────────────────────────────────────────────────────────────────────
 */

export { NepaliTextService } from './nepali-text.service';
export { PreetiInputDirective } from './preeti-input.directive';
export {
  UnicodeToPreetiPipe,
  NepaliDatePipe,
  NepaliDigitsPipe,
} from './nepali.pipe';
