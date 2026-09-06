/**
 * preeti-input.directive.ts
 * ─────────────────────────────────────────────────────────────────────────
 * Angular Directive: [preetiInput]
 *
 * PURPOSE:
 *  Attach to any <input> or <textarea> to enable Preeti keyboard input.
 *
 * HOW IT WORKS:
 *  1. CSS: applies 'Preeti' font to the field — user SEES Nepali glyphs
 *  2. On every keystroke: captures raw ASCII (Preeti keymap),
 *     converts to Unicode, and pushes Unicode into the ngModel/formControl
 *  3. The visual display stays in Preeti font (user-friendly)
 *  4. The actual model value is Unicode (DB-safe)
 *
 * USAGE:
 *  <!-- Simple ngModel -->
 *  <input type="text" preetiInput [(ngModel)]="nameNepali">
 *
 *  <!-- Reactive form -->
 *  <input type="text" preetiInput formControlName="address_nepali">
 *
 * NOTE:
 *  Make sure 'Preeti' font is loaded in your global styles.css:
 *  @font-face { font-family: 'Preeti'; src: url('/assets/fonts/Preeti.ttf'); }
 * ─────────────────────────────────────────────────────────────────────────
 */

import {
  Directive,
  ElementRef,
  HostListener,
  OnInit,
  Renderer2,
  Self,
  Optional,
} from '@angular/core';
import { NgControl } from '@angular/forms';
import { preetiToUnicode } from 'nepali-keyboard';

@Directive({
  selector: '[preetiInput]',
  standalone: true,
})
export class PreetiInputDirective implements OnInit {
  /** Stores raw Preeti keystrokes internally */
  private preetiBuffer: string = '';

  constructor(
    private el: ElementRef<HTMLInputElement | HTMLTextAreaElement>,
    private renderer: Renderer2,
    @Optional() @Self() private ngControl: NgControl
  ) {}

  ngOnInit() {
    // Apply Preeti font styling to the input field
    this.renderer.setStyle(this.el.nativeElement, 'font-family', 'Preeti, serif');
    this.renderer.setStyle(this.el.nativeElement, 'font-size', '1rem');
    this.renderer.setStyle(this.el.nativeElement, 'direction', 'ltr');

    // If the model already has a value (edit mode), init the buffer
    // Note: In edit mode, value comes from DB as Unicode, convert to Preeti for display
    // (reverse: unicode → preeti for the visual buffer — advanced use case)
  }

  @HostListener('input', ['$event'])
  onInput(event: InputEvent): void {
    const nativeEl = this.el.nativeElement;
    const rawValue = nativeEl.value; // This is what the user sees (ASCII/Preeti keystrokes)

    // Store the Preeti raw buffer
    this.preetiBuffer = rawValue;

    // Convert Preeti ASCII → Unicode
    const unicodeValue = preetiToUnicode(rawValue);

    // Push Unicode into the Angular form control (what gets saved to DB)
    if (this.ngControl && this.ngControl.control) {
      // Temporarily set internal value without triggering another input event
      this.ngControl.control.setValue(unicodeValue, { emitEvent: true });
    }

    // Keep visual display as Preeti (restore visual value to raw Preeti)
    // We patch the element value back to Preeti so user sees Nepali glyphs
    // The model holds Unicode; the element holds Preeti visual
    const cursorPos = (nativeEl as HTMLInputElement).selectionStart ?? rawValue.length;
    nativeEl.value = rawValue;
    try {
      (nativeEl as HTMLInputElement).setSelectionRange(cursorPos, cursorPos);
    } catch {}
  }

  @HostListener('blur')
  onBlur(): void {
    // On blur, ensure final Unicode value is set in control
    if (this.ngControl?.control) {
      const unicode = preetiToUnicode(this.preetiBuffer);
      this.ngControl.control.setValue(unicode, { emitEvent: false });
    }
  }

  /** Returns the current Preeti buffer (raw user input) */
  getPreetiBuffer(): string {
    return this.preetiBuffer;
  }
}
