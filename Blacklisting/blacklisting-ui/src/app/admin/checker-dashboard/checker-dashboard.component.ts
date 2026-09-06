import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Router, RouterModule } from '@angular/router';
import { BaseChartDirective } from 'ng2-charts';
import { ChartConfiguration, ChartData, ChartType } from 'chart.js';

@Component({
  selector: 'app-checker-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule, BaseChartDirective],
  template: `
    <div class="p-8 w-full space-y-8">
      <div class="mb-8 flex justify-between items-center">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Checker Workspace</h1>
          <p class="text-gray-500 mt-2">Review pending cases and monitor CIB Blacklisting overview.</p>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col">
          <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Total Applications</span>
          <span class="text-3xl font-bold text-gray-900 mt-2">{{ pendingCases.length }}</span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col">
          <span class="text-sm font-semibold text-amber-500 uppercase tracking-wider">Awaiting Review</span>
          <span class="text-3xl font-bold text-amber-600 mt-2">{{ getPendingCount() }}</span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col">
          <span class="text-sm font-semibold text-red-500 uppercase tracking-wider">Currently Blacklisted</span>
          <span class="text-3xl font-bold text-red-600 mt-2">{{ stats.blacklisted || 0 }}</span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col">
          <span class="text-sm font-semibold text-green-500 uppercase tracking-wider">Released Entities</span>
          <span class="text-3xl font-bold text-green-600 mt-2">{{ stats.released || 0 }}</span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Chart Section -->
        <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-bold text-gray-800 mb-4">Blacklisted Entities</h2>
          <div class="h-64 flex justify-center items-center">
            <canvas baseChart
              [data]="barChartData"
              [options]="barChartOptions"
              [type]="barChartType">
            </canvas>
          </div>
        </div>

        <!-- Pending Cases Section -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-6 py-5 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Pending Approvals & Applications</h2>
            <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold">{{ pendingCases.length }} Total</span>
          </div>
          
          <div *ngIf="loading" class="py-12 text-center text-slate-400 text-sm">
            Loading cases for review...
          </div>

          <div *ngIf="!loading && pendingCases.length === 0" class="py-12 text-center text-slate-400 text-sm">
            No pending cases found.
          </div>

          <div *ngIf="!loading && pendingCases.length > 0" class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-white border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
                  <th class="py-3 px-6 font-semibold">Application #</th>
                  <th class="py-3 px-6 font-semibold">Applicant</th>
                  <th class="py-3 px-6 font-semibold">Status</th>
                  <th class="py-3 px-6 font-semibold">Date</th>
                  <th class="py-3 px-6 font-semibold text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr *ngFor="let app of pendingCases" class="border-b border-gray-50 text-sm hover:bg-gray-50 transition">
                  <td class="py-4 px-6 font-semibold font-mono text-blue-600">{{ app.application_number }}</td>
                  <td class="py-4 px-6 text-gray-800 font-medium">{{ app.applicant?.name_english || '—' }}</td>
                  <td class="py-4 px-6">
                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold capitalize">
                      {{ formatStatus(app.status) }}
                    </span>
                  </td>
                  <td class="py-4 px-6 text-gray-500 text-xs">{{ app.created_at | date:'dd MMM yyyy' }}</td>
                  <td class="py-4 px-6 text-right">
                    <button (click)="openCase(app.id)"
                      class="bg-blue-50 text-blue-700 px-3 py-1.5 rounded text-xs font-semibold hover:bg-blue-100 transition">
                      Review Case →
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  `
})
export class CheckerDashboardComponent implements OnInit {
  stats: any = {
    individuals: 0,
    units: 0,
    blacklisted: 0,
    released: 0
  };

  pendingCases: any[] = [];
  loading = true;

  public barChartOptions: ChartConfiguration['options'] = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
    }
  };
  public barChartType: ChartType = 'bar';
  public barChartData: ChartData<'bar'> = {
    labels: [ 'Ind', 'Units' ],
    datasets: [
      { data: [0, 0], label: 'Blacklisted', backgroundColor: ['#ef4444', '#3b82f6'] }
    ]
  };

  constructor(
    private http: HttpClient, 
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.fetchData();
  }

  fetchData() {
    this.loading = true;
    this.cdr.detectChanges();

    this.http.get<any>('http://127.0.0.1:8000/api/cib/stats').subscribe({
      next: (data) => {
        this.stats = data;
        this.barChartData.datasets[0].data = [data.individuals || 0, data.units || 0];
        this.cdr.detectChanges();
      },
      error: (err) => console.error(err)
    });

    this.http.get<any>('http://127.0.0.1:8000/api/applications').subscribe({
      next: (res) => {
        this.pendingCases = res.data || [];
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Pending cases load error:', err);
        this.loading = false;
        this.cdr.detectChanges();
      }
    });
  }

  getPendingCount(): number {
    return (this.pendingCases || []).filter(a => {
      const st = (a.status || '').toLowerCase();
      return st === 'draft' || st === 'sent_to_province' || st === 'sent_to_central' || st === 'notice_issued' || st === 'province_review' || st === 'central_review';
    }).length;
  }

  openCase(id: number) {
    this.router.navigate(['/admin/application', id]);
  }

  formatStatus(status: string): string {
    return (status || 'draft').replace(/_/g, ' ');
  }
}
