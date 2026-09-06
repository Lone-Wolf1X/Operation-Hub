import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';

@Component({
  selector: 'app-lodge-case',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './lodge-case.html'
})
export class LodgeCase implements OnInit {
  lodgeForm!: FormGroup;
  isSubmitting = false;
  successMessage = '';
  errorMessage = '';

  constructor(
    private fb: FormBuilder,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.lodgeForm = this.fb.group({
      entity_type:       ['individual', Validators.required],
      applicant_name:    ['', Validators.required],
      applicant_email:   ['', [Validators.required, Validators.email]],
      applicant_contact: ['', Validators.required],
    });
  }

  get entityType() {
    return this.lodgeForm.get('entity_type')?.value;
  }

  async onSubmit() {
    if (this.lodgeForm.invalid) {
      this.lodgeForm.markAllAsTouched();
      return;
    }

    if (typeof window === 'undefined') return;

    this.isSubmitting = true;
    this.successMessage = '';
    this.errorMessage = '';
    this.cdr.detectChanges();

    try {
      const payload = this.lodgeForm.value;

      const response = await fetch('http://127.0.0.1:8000/api/applications', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const res = await response.json();
      console.log('Lodge case response:', res);

      if (!response.ok || !res.success) {
        throw new Error(res.message || `Server returned status ${response.status}`);
      }

      const newId = res?.data?.id;
      if (!newId) {
        this.errorMessage = 'Case created but could not retrieve ID. Please check All Cases.';
        this.isSubmitting = false;
        this.cdr.detectChanges();
        return;
      }

      const appNum = res?.data?.application_number || 'New';
      this.successMessage = `Case ${appNum} successfully created! Redirecting to workspace...`;
      this.isSubmitting = false;
      this.cdr.detectChanges();

      setTimeout(() => {
        this.router.navigate(['/admin/application', newId]).catch(err => {
          console.error('Navigation error:', err);
          this.errorMessage = `Case created (ID: ${newId}), but direct navigation failed. Please open it from All Cases.`;
          this.cdr.detectChanges();
        });
      }, 500);

    } catch (err: any) {
      console.error('Lodge Case Submit Error:', err);
      this.isSubmitting = false;
      let detail = err.message || 'Cannot connect to backend server.';
      if (err.name === 'TypeError' && err.message.includes('fetch')) {
        detail = 'Cannot connect to Laravel backend at http://127.0.0.1:8000. Please ensure server is running.';
      }
      this.errorMessage = `Failed to create case: ${detail}`;
      this.cdr.detectChanges();
    }
  }
}
