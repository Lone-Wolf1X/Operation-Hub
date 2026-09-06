import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { BaseChartDirective } from 'ng2-charts';
import { ChartConfiguration, ChartData } from 'chart.js';

@Component({
  selector: 'app-maker-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule, BaseChartDirective],
  templateUrl: './maker-dashboard.component.html',
})
export class MakerDashboardComponent implements OnInit {
  stats: any = {
    total_cases: 0,
    drafts: 0,
    active_notice: 0,
    blacklisted: 0
  };

  recentApplications: any[] = [];
  loading = true;

  // ─── Bar Chart ─────────────────────────────────
  public readonly barChartType = 'bar' as const;
  public barChartOptions: ChartConfiguration['options'] = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#1e293b',
        titleColor: '#f1f5f9',
        bodyColor: '#cbd5e1',
        padding: 12,
        cornerRadius: 8,
      }
    },
    scales: {
      x: {
        grid: { display: false },
        border: { display: false },
        ticks: { color: '#64748b', font: { size: 12, weight: 'bold' } }
      },
      y: {
        grid: { color: '#f1f5f9' },
        border: { display: false },
        ticks: { color: '#94a3b8', font: { size: 11 }, stepSize: 1 }
      }
    }
  };
  public barChartData: ChartData<'bar'> = {
    labels: ['Individuals', 'Institutional'],
    datasets: [
      {
        data: [0, 0],
        label: 'Blacklisted',
        backgroundColor: ['rgba(99, 102, 241, 0.85)', 'rgba(16, 185, 129, 0.85)'],
        hoverBackgroundColor: ['#6366f1', '#10b981'],
        borderRadius: 8,
        borderSkipped: false,
        barThickness: 52,
      }
    ]
  };

  // ─── Pie Chart ─────────────────────────────────
  public readonly pieChartType = 'doughnut' as const;
  public pieChartOptions: ChartConfiguration<'doughnut'>['options'] = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          color: '#475569',
          font: { size: 12 },
          padding: 16,
          usePointStyle: true,
          pointStyleWidth: 10,
        }
      }
    }
  };
  public pieChartData: ChartData<'doughnut'> = {
    labels: ['Individuals', 'Institutional', 'Released'],
    datasets: [
      {
        data: [0, 0, 0],
        backgroundColor: ['#6366f1', '#10b981', '#f59e0b'],
        hoverBackgroundColor: ['#4f46e5', '#059669', '#d97706'],
        borderWidth: 0,
        hoverOffset: 6,
      }
    ]
  };

  constructor(
    private http: HttpClient, 
    public router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.fetchData();
  }

  fetchData() {
    this.loading = true;
    this.cdr.detectChanges();

    // Fetch CIB stats
    this.http.get<any>('http://127.0.0.1:8000/api/cib/stats').subscribe({
      next: (data) => {
        this.barChartData.datasets[0].data = [data.individuals || 0, data.units || 0];
        this.pieChartData.datasets[0].data = [data.individuals || 0, data.units || 0, data.released || 0];
        this.cdr.detectChanges();
      },
      error: (err) => console.error(err)
    });

    // Fetch real applications / cases
    this.http.get<any>('http://127.0.0.1:8000/api/applications').subscribe({
      next: (res) => {
        const apps = res.data || [];
        this.recentApplications = apps;
        
        // Calculate live stats from real DB cases
        this.stats.total_cases = apps.length;
        this.stats.drafts = apps.filter((a: any) => a.status === 'draft').length;
        this.stats.active_notice = apps.filter((a: any) => a.status === 'notice_issued' || a.status === 'dishonour_issued').length;
        this.stats.blacklisted = apps.filter((a: any) => a.status === 'cib_blacklisted' || a.status === 'internally_blacklisted').length;
        
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Applications load error:', err);
        this.loading = false;
        this.cdr.detectChanges();
      }
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
      draft: 'bg-slate-100 text-slate-600',
      notice_issued: 'bg-amber-100 text-amber-700',
      dishonour_issued: 'bg-orange-100 text-orange-700',
      cib_blacklisted: 'bg-rose-100 text-rose-700',
      internally_blacklisted: 'bg-red-100 text-red-800'
    };
    return map[status] || 'bg-slate-100 text-slate-600';
  }
}
