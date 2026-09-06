import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, FormArray, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';

declare const window: any;

@Component({
  selector: 'app-application-workspace',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './application-workspace.html'
})
export class ApplicationWorkspace implements OnInit {
  applicationId!: string;
  applicationData: any = null;
  
  caseForm!: FormGroup;
  isSubmitting = false;
  errorMessage = '';
  successMessage = '';

  // Workflow states: 'draft', 'verified', 'notice_generated', 'dishonour_generated', 'application_received', 'note_generated'
  workflowState = 'draft';

  // Section visibility and data flags
  showPayeeForm = false;
  showPayerForm = false;
  showChequeForm = false;

  hasPayeeData = false;
  hasPayerData = false;
  hasChequeData = false;

  // Locations Data
  locationsData: any[] = [];
  
  // Dropdown options
  payeePerDistricts: any[] = [];
  payeePerMunicipalities: any[] = [];
  payeePerWards: any[] = [];
  payeeTempDistricts: any[] = [];
  payeeTempMunicipalities: any[] = [];
  payeeTempWards: any[] = [];

  payerPerDistricts: any[] = [];
  payerPerMunicipalities: any[] = [];
  payerPerWards: any[] = [];
  payerTempDistricts: any[] = [];
  payerTempMunicipalities: any[] = [];
  payerTempWards: any[] = [];

  constructor(
    private route: ActivatedRoute,
    private fb: FormBuilder,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.route.params.subscribe(params => {
      if (params['id']) {
        this.applicationId = params['id'];
        this.fetchApplicationDetails();
      }
    });
    this.initForm();
    this.loadLocations();
  }

  async loadLocations() {
    if (typeof window === 'undefined') return;
    try {
      const res = await fetch('/assets/locations_en.json');
      if (res.ok) {
        const data = await res.json();
        this.locationsData = data.provinces || [];
        this.cdr.detectChanges();
      }
    } catch (e) {
      console.error('Failed to load locations', e);
    }
  }

  async fetchApplicationDetails() {
    if (typeof window === 'undefined') return;
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/applications/${this.applicationId}`);
      const json = await res.json();
      if (json.success) {
        this.applicationData = json.data;
        if (this.applicationData.workflow_state) {
          this.workflowState = this.applicationData.workflow_state;
        }
      } else {
        this.errorMessage = json.message || 'Failed to load application details';
      }
    } catch (err: any) {
      this.errorMessage = err.message || 'Failed to load application details';
    } finally {
      this.cdr.detectChanges();
    }
  }

  initForm() {
    this.caseForm = this.fb.group({
      payee: this.fb.group({
        entity_type: ['individual'],
        fullNameEnglish: [''], fullNameNepali: [''], dob: [''], dobBs: [''],
        gender: [''], nidNumber: [''], citizenshipNumber: [''], citizenshipIssueDate: [''],
        citizenshipIssueDistrict: [''], citizenshipAuthority: [''], reissueDate: [''],
        pratilipiType: [''], reissueReason: [''], perProvince: [''], perDistrict: [''],
        perMunicipality: [''], perWard: [''], perTown: [''], perStreet: [''],
        tempProvince: [''], tempDistrict: [''], tempMunicipality: [''], tempWard: [''],
        tempTown: [''], tempStreet: [''], grandfather: [''], grandmother: [''],
        father: [''], mother: [''], spouse: [''], daughter: [''], son: [''],
        fatherInLaw: [''], motherInLaw: [''],
        // Institutional
        companyNameEnglish: [''], companyNameNepali: [''], registrationNumber: [''],
        registrationDate: [''], registrationDateBs: [''], panNumber: [''],
        panIssueDistrict: [''], natureOfBusiness: [''], ceoName: ['']
      }),
      payer: this.fb.group({
        entity_type: ['individual'],
        fullNameEnglish: [''], fullNameNepali: [''], dob: [''], dobBs: [''],
        gender: [''], nidNumber: [''], citizenshipNumber: [''], citizenshipIssueDate: [''],
        citizenshipIssueDistrict: [''], citizenshipAuthority: [''], reissueDate: [''],
        pratilipiType: [''], reissueReason: [''], perProvince: [''], perDistrict: [''],
        perMunicipality: [''], perWard: [''], perTown: [''], perStreet: [''],
        tempProvince: [''], tempDistrict: [''], tempMunicipality: [''], tempWard: [''],
        tempTown: [''], tempStreet: [''], grandfather: [''], grandmother: [''],
        father: [''], mother: [''], spouse: [''], daughter: [''], son: [''],
        fatherInLaw: [''], motherInLaw: [''],
        companyNameEnglish: [''], companyNameNepali: [''], registrationNumber: [''],
        registrationDate: [''], registrationDateBs: [''], panNumber: [''],
        panIssueDistrict: [''], natureOfBusiness: [''], ceoName: ['']
      }),
      cheque: this.fb.group({
        chequeNumber: [''], chequeAmount: [''], chequeDateAd: [''], chequeDateBs: [''],
        bankName: [''], branchName: [''], accountName: [''], accountNumber: [''],
        accountType: [''], routingNumber: [''], swiftCode: [''], branchAddress: [''],
        currency: [''], amountWords: [''], remarks: ['']
      })
    });

    this.setupDateConversion();
    this.setupLocationCascades();
  }

  // =========================================================
  // DATE CONVERSION LOGIC
  // =========================================================
  setupDateConversion() {
    const handleDate = (group: string, source: string, target: string, toBS: boolean) => {
      this.caseForm.get(`${group}.${source}`)?.valueChanges.subscribe(val => {
        if (!val || typeof window === 'undefined' || !window.DateConverter) return;
        try {
          const parts = val.split('-');
          if (parts.length !== 3) return;
          const converter = window.DateConverter(parts[0], parts[1], parts[2]);
          let res = toBS ? converter.convertToBS().toBSString() : converter.convertToAD().toADString();
          // Ensure valid format yyyy-mm-dd
          let resParts = res.split('-');
          if (resParts.length === 3) {
             const m = resParts[1].padStart(2, '0');
             const d = resParts[2].padStart(2, '0');
             res = `${resParts[0]}-${m}-${d}`;
          }
          if (this.caseForm.get(`${group}.${target}`)?.value !== res) {
            this.caseForm.get(`${group}.${target}`)?.setValue(res, { emitEvent: false });
          }
        } catch (e) {}
      });
    };

    // Payee Dates
    handleDate('payee', 'dob', 'dobBs', true);
    handleDate('payee', 'dobBs', 'dob', false);
    handleDate('payee', 'registrationDate', 'registrationDateBs', true);
    handleDate('payee', 'registrationDateBs', 'registrationDate', false);

    // Payer Dates
    handleDate('payer', 'dob', 'dobBs', true);
    handleDate('payer', 'dobBs', 'dob', false);
    handleDate('payer', 'registrationDate', 'registrationDateBs', true);
    handleDate('payer', 'registrationDateBs', 'registrationDate', false);

    // Cheque Dates
    handleDate('cheque', 'chequeDateAd', 'chequeDateBs', true);
    handleDate('cheque', 'chequeDateBs', 'chequeDateAd', false);
  }

  // =========================================================
  // LOCATION CASCADES LOGIC
  // =========================================================
  setupLocationCascades() {
    const hookCascade = (group: 'payee'|'payer', prefix: 'per'|'temp') => {
      this.caseForm.get(`${group}.${prefix}Province`)?.valueChanges.subscribe(provName => {
        const prov = this.locationsData.find(p => p.name === provName);
        if (group === 'payee' && prefix === 'per') this.payeePerDistricts = prov ? prov.districts : [];
        if (group === 'payee' && prefix === 'temp') this.payeeTempDistricts = prov ? prov.districts : [];
        if (group === 'payer' && prefix === 'per') this.payerPerDistricts = prov ? prov.districts : [];
        if (group === 'payer' && prefix === 'temp') this.payerTempDistricts = prov ? prov.districts : [];
        this.caseForm.get(`${group}.${prefix}District`)?.setValue('');
      });

      this.caseForm.get(`${group}.${prefix}District`)?.valueChanges.subscribe(distName => {
        const provName = this.caseForm.get(`${group}.${prefix}Province`)?.value;
        const prov = this.locationsData.find(p => p.name === provName);
        let munics: any[] = [];
        if (prov) {
          const dist = prov.districts.find((d: any) => d.name === distName);
          munics = dist ? dist.municipalities : [];
        }
        if (group === 'payee' && prefix === 'per') this.payeePerMunicipalities = munics;
        if (group === 'payee' && prefix === 'temp') this.payeeTempMunicipalities = munics;
        if (group === 'payer' && prefix === 'per') this.payerPerMunicipalities = munics;
        if (group === 'payer' && prefix === 'temp') this.payerTempMunicipalities = munics;
        this.caseForm.get(`${group}.${prefix}Municipality`)?.setValue('');
      });

      this.caseForm.get(`${group}.${prefix}Municipality`)?.valueChanges.subscribe(munName => {
        const provName = this.caseForm.get(`${group}.${prefix}Province`)?.value;
        const distName = this.caseForm.get(`${group}.${prefix}District`)?.value;
        const prov = this.locationsData.find(p => p.name === provName);
        let wards: any[] = [];
        if (prov) {
          const dist = prov.districts.find((d: any) => d.name === distName);
          if (dist) {
            const mun = dist.municipalities.find((m: any) => m.name === munName);
            wards = mun ? mun.wards : [];
          }
        }
        if (group === 'payee' && prefix === 'per') this.payeePerWards = wards;
        if (group === 'payee' && prefix === 'temp') this.payeeTempWards = wards;
        if (group === 'payer' && prefix === 'per') this.payerPerWards = wards;
        if (group === 'payer' && prefix === 'temp') this.payerTempWards = wards;
        this.caseForm.get(`${group}.${prefix}Ward`)?.setValue('');
      });
    };

    hookCascade('payee', 'per');
    hookCascade('payee', 'temp');
    hookCascade('payer', 'per');
    hookCascade('payer', 'temp');
  }

  getLocationsArr(arrName: string): any[] {
    return (this as any)[arrName] || [];
  }


  async onSaveCase() {
    if (this.caseForm.invalid) {
      this.caseForm.markAllAsTouched();
      return;
    }
    this.isSubmitting = true;
    setTimeout(() => {
      this.isSubmitting = false;
      this.successMessage = "Case details saved successfully!";
      setTimeout(() => this.successMessage = '', 3000);
      this.cdr.detectChanges();
    }, 1000);
  }

  toggleForm(section: 'payee' | 'payer' | 'cheque') {
    if (section === 'payee') this.showPayeeForm = !this.showPayeeForm;
    if (section === 'payer') this.showPayerForm = !this.showPayerForm;
    if (section === 'cheque') this.showChequeForm = !this.showChequeForm;
  }

  saveSection(section: 'payee' | 'payer' | 'cheque') {
    this.toggleForm(section);
    if (section === 'payee') this.hasPayeeData = true;
    if (section === 'payer') this.hasPayerData = true;
    if (section === 'cheque') this.hasChequeData = true;

    this.successMessage = `${section.toUpperCase()} details saved!`;
    setTimeout(() => { this.successMessage = ''; this.cdr.detectChanges(); }, 2500);
  }

  // =========================================================
  // WORKFLOW ACTIONS
  // =========================================================
  
  async initiateWorkflow() {
    if (!confirm('Are you sure you want to verify and initiate this workflow?')) return;
    this.isSubmitting = true;
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/cases/${this.applicationId}/verify`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
        },
        body: JSON.stringify({})
      });
      const json = await res.json();
      if (json.success) {
        this.updateWorkflowState('checker_verified'); // Using new status mapping
        this.fetchApplicationDetails(); // Reload
      } else {
        alert(json.message || 'Failed to verify');
      }
    } catch (e: any) {
      alert(e.message || 'Error occurred');
    } finally {
      this.isSubmitting = false;
      this.cdr.detectChanges();
    }
  }

  async generate45DayNotice() {
    if (!confirm('Generate 45-Day Notice document?')) return;
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/cases/${this.applicationId}/documents/45-days-notice`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
        },
        body: JSON.stringify({
          customer_address: 'System Generated',
          branch_name: 'Main Branch',
          payee_name: this.caseForm.get('payee.fullNameEnglish')?.value || 'N/A',
          amount: this.caseForm.get('cheque.chequeAmount')?.value || 0,
          amount_words: this.caseForm.get('cheque.amountWords')?.value || 'N/A',
          cheque_number: this.caseForm.get('cheque.chequeNumber')?.value || 'N/A',
          presentation_date_1: 'N/A',
          presentation_date_2: 'N/A',
          application_date: 'N/A'
        })
      });
      if (res.ok) {
        const blob = await res.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `45_Day_Notice_${this.applicationId}.docx`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        
        // Don't change workflow state, just allow the user to upload proofs now
        this.successMessage = "Document Generated!";
        setTimeout(() => this.successMessage = '', 3000);
      } else {
        const json = await res.json();
        alert(json.message || 'Failed to generate document');
      }
    } catch (e: any) {
      alert(e.message || 'Error occurred');
    }
  }

  // Define Proof Form globally or add a new method to submit proofs
  async submitNoticeProofs() {
    const fileInput = document.getElementById('noticeProofInput') as HTMLInputElement;
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
      alert('Notice Proof is required');
      return;
    }
    
    this.isSubmitting = true;
    const formData = new FormData();
    formData.append('notice_proof', fileInput.files[0]);
    
    const postalInput = document.getElementById('postalReceiptInput') as HTMLInputElement;
    if (postalInput && postalInput.files && postalInput.files.length > 0) {
      formData.append('postal_receipt', postalInput.files[0]);
    }

    try {
      const res = await fetch(`http://127.0.0.1:8000/api/cases/${this.applicationId}/upload-notice-proofs`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
        },
        body: formData
      });
      const json = await res.json();
      if (json.success) {
        this.updateWorkflowState('waiting_period');
        this.fetchApplicationDetails();
      } else {
        alert(json.message || 'Failed to upload proofs');
      }
    } catch (e: any) {
      alert(e.message || 'Error occurred');
    } finally {
      this.isSubmitting = false;
      this.cdr.detectChanges();
    }
  }

  async generateDishonourCertificate() {
    if (!confirm('Generate Dishonour Certificate? Ensure 45 days have passed.')) return;
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/cases/${this.applicationId}/issue-dishonour`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
        },
        body: JSON.stringify({})
      });
      const json = await res.json();
      if (json.success) {
        this.updateWorkflowState('dishonour_generated');
        this.fetchApplicationDetails();
      } else {
        alert(json.message || 'Failed to issue dishonour certificate');
      }
    } catch (e: any) {
      alert(e.message || 'Error occurred');
    }
  }
  
  async initiateBlacklisting() {
    const notes = prompt("Enter Maker Notes for Blacklisting:");
    if (!notes) return;
    
    this.isSubmitting = true;
    try {
      const res = await fetch(`http://127.0.0.1:8000/api/cases/${this.applicationId}/confirm-blacklisting`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
        },
        body: JSON.stringify({ maker_notes: notes })
      });
      const json = await res.json();
      if (json.success) {
        this.updateWorkflowState('blacklisting_initiated');
        this.fetchApplicationDetails();
      } else {
        alert(json.message || 'Failed to initiate blacklisting');
      }
    } catch (e: any) {
      alert(e.message || 'Error occurred');
    } finally {
      this.isSubmitting = false;
      this.cdr.detectChanges();
    }
  }

  async receivePhysicalApplication() {
    if (!confirm('Confirm physical application received?')) return;
    this.updateWorkflowState('application_received');
  }

  async generateNote() {
    if (!confirm('Proceed to generate Final Note (Tippani)?')) return;
    this.updateWorkflowState('note_generated');
  }

  private async updateWorkflowState(newState: string) {
    this.workflowState = newState;
    this.successMessage = `Workflow advanced to: ${newState.replace('_', ' ').toUpperCase()}`;
    setTimeout(() => this.successMessage = '', 3000);
    this.cdr.detectChanges();
  }
}
