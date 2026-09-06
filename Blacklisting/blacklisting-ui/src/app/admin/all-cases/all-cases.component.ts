import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Router, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-all-cases',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  template: `
    <div class="min-h-screen bg-slate-50 p-6">

      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 bg-blue-600 rounded-xl shadow-md">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-slate-900">All Cases</h1>
            <p class="text-slate-500 text-sm mt-0.5">Manage and track all lodged blacklisting cases</p>
          </div>
        </div>
        <button (click)="router.navigate(['/admin/lodge-case'])"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm shadow transition-all">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Lodge New Case
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-4 flex flex-wrap gap-3 items-center">
        <input [(ngModel)]="searchTerm" (ngModelChange)="applyFilter()"
          placeholder="Search by name or case number..."
          class="flex-1 min-w-48 px-4 py-2 rounded-lg border border-slate-200 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition">

        <select [(ngModel)]="statusFilter" (ngModelChange)="applyFilter()"
          class="px-4 py-2 rounded-lg border border-slate-200 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition bg-white">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="notice_issued">Notice Issued</option>
          <option value="dishonour_issued">Dishonour Issued</option>
          <option value="sent_to_province">Sent to Province</option>
          <option value="cib_blacklisted">CIB Blacklisted</option>
          <option value="internally_blacklisted">Internally Blacklisted</option>
          <option value="on_hold">On Hold</option>
          <option value="terminated">Terminated</option>
        </select>

        <span class="text-xs text-slate-400">{{ filteredCases.length }} cases</span>
      </div>

      <!-- Loading -->
      <div *ngIf="loading" class="flex items-center justify-center py-20">
        <div class="flex flex-col items-center gap-3">
          <svg class="animate-spin w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-slate-500 text-sm">Loading cases...</p>
        </div>
      </div>

      <!-- Error -->
      <div *ngIf="errorMsg && !loading" class="bg-red-50 border border-red-200 rounded-xl px-5 py-4 text-red-700 text-sm mb-4">
        {{ errorMsg }}
      </div>

      <!-- Table -->
      <div *ngIf="!loading && filteredCases.length > 0" class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
              <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Case #</th>
              <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Applicant</th>
              <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
              <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Created</th>
              <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr *ngFor="let app of filteredCases" class="hover:bg-slate-50 transition-colors">
              <td class="px-5 py-3.5 font-mono text-xs text-blue-700 font-semibold">
                {{ app.application_number }}
              </td>
              <td class="px-5 py-3.5">
                <div class="font-medium text-slate-800">{{ app.applicant?.name_english || '—' }}</div>
                <div class="text-xs text-slate-400">{{ app.applicant?.contact_details?.email || '' }}</div>
              </td>
              <td class="px-5 py-3.5">
                <span [class]="getStatusClass(app.status)" class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize">
                  {{ formatStatus(app.status) }}
                </span>
              </td>
              <td class="px-5 py-3.5 text-slate-500 text-xs">
                {{ app.created_at | date:'dd MMM yyyy' }}
              </td>
              <td class="px-5 py-3.5 text-right">
                <button (click)="openCase(app.id)"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium rounded-lg text-xs transition">
                  Open
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty state -->
      <div *ngIf="!loading && filteredCases.length === 0 && !errorMsg"
        class="bg-white rounded-2xl border border-slate-200 p-16 text-center shadow-sm">
        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
        </div>
        <h3 class="text-slate-700 font-semibold mb-1">No cases found</h3>
        <p class="text-slate-400 text-sm mb-5">No blacklisting cases have been lodged yet.</p>
        <button (click)="router.navigate(['/admin/lodge-case'])"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm shadow transition-all">
          Lodge First Case
        </button>
      </div>

    </div>
  `
})
export class AllCasesComponent implements OnInit {
  allCases: any[] = [];
  filteredCases: any[] = [];
  loading = true;
  errorMsg = '';
  searchTerm = '';
  statusFilter = '';

  constructor(
    private http: HttpClient, 
    public router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.fetchCases();
  }

  fetchCases() {
    this.loading = true;
    this.cdr.detectChanges();
    this.http.get('http://127.0.0.1:8000/api/applications').subscribe({
      next: (res: any) => {
        this.allCases = res.data || [];
        this.applyFilter();
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.errorMsg = 'Failed to load cases. Please try again.';
        this.loading = false;
        this.cdr.detectChanges();
      }
    });
  }

  applyFilter() {
    const query = (this.searchTerm || '').trim().toLowerCase();
    const selectedStatus = (this.statusFilter || '').trim().toLowerCase();

    this.filteredCases = this.allCases.filter(app => {
      const appNum = (app.application_number || '').toLowerCase();
      const applicantName = (app.applicant?.name_english || '').toLowerCase();
      const applicantEmail = (app.applicant?.contact_details?.email || '').toLowerCase();

      const caseNumbers = (app.cases || []).map((c: any) => (c.case_number || '').toLowerCase()).join(' ');
      const targetNames = (app.cases || []).map((c: any) => (c.target?.name_english || '').toLowerCase()).join(' ');

      const matchSearch = !query ||
        appNum.includes(query) ||
        applicantName.includes(query) ||
        applicantEmail.includes(query) ||
        caseNumbers.includes(query) ||
        targetNames.includes(query);

      const appStatus = (app.status || 'draft').toLowerCase();
      const matchStatus = !selectedStatus || appStatus === selectedStatus;

      return matchSearch && matchStatus;
    });
  }

  openCase(id: number) {
    this.router.navigate(['/admin/application', id]);
  }

  formatStatus(status: string): string {
    return (status || 'draft').replace(/_/g, ' ');
  }

  getStatusClass(status: string): string {
    const map: Record<string, string> = {
      draft:                   'bg-slate-100 text-slate-600',
      notice_issued:           'bg-amber-100 text-amber-700',
      dishonour_issued:        'bg-orange-100 text-orange-700',
      sent_to_province:        'bg-blue-100 text-blue-700',
      province_review:         'bg-indigo-100 text-indigo-700',
      sent_to_central:         'bg-violet-100 text-violet-700',
      central_review:          'bg-purple-100 text-purple-700',
      approved_for_cib:        'bg-cyan-100 text-cyan-700',
      cib_blacklisted:         'bg-rose-100 text-rose-700',
      internally_blacklisted:  'bg-red-100 text-red-800',
      on_hold:                 'bg-yellow-100 text-yellow-700',
      terminated:              'bg-gray-100 text-gray-600',
    };
    return map[status] || 'bg-slate-100 text-slate-600';
  }
}
