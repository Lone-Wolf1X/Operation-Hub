// Custom type declarations for packages without @types
// ─────────────────────────────────────────────────────────────────────────

/**
 * nepali-keyboard — Preeti ↔ Unicode conversion library
 * No official @types package exists; declaring manually.
 */
declare module 'nepali-keyboard' {
  /**
   * Converts Preeti-encoded ASCII text to Unicode Devanagari.
   * @param preeti - raw ASCII string typed via Preeti keyboard layout
   * @returns Unicode Devanagari string
   */
  export function preetiToUnicode(preeti: string): string;

  /**
   * Converts Unicode Devanagari text back to Preeti ASCII encoding.
   * @param unicode - Unicode Devanagari string
   * @returns Preeti ASCII-encoded string
   */
  export function unicodeToPreeti(unicode: string): string;
}
