import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';

@Component({
  imports: [CommonModule],
  selector: 'app-super-dashboard',
  styleUrl: './super-dashboard.scss',
  templateUrl: './super-dashboard.html',
  standalone: true
})
export class SuperDashboard implements OnInit {
  activeTenants = 0;
  globalUsers = 0;
  recentTenants: any[] = [];
  loading = true;

  constructor(
    private http: HttpClient, 
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.fetchDashboardData();
  }

  fetchDashboardData() {
    this.loading = true;
    this.cdr.detectChanges();

    this.http.get<any[]>('http://127.0.0.1:8000/api/users').subscribe({
      next: (users) => {
        this.globalUsers = users.length;
        
        // Group by tenant
        const tenantMap = new Map<string, any>();
        users.forEach(u => {
          if (!tenantMap.has(u.tenant_id)) {
            tenantMap.set(u.tenant_id, {
              tenant_id: u.tenant_id,
              adminEmail: u.role === 'admin' ? u.email : (tenantMap.get(u.tenant_id)?.adminEmail || u.email),
              status: 'Active'
            });
          } else if (u.role === 'admin') {
            const t = tenantMap.get(u.tenant_id);
            t.adminEmail = u.email;
          }
        });

        this.activeTenants = tenantMap.size;
        this.recentTenants = Array.from(tenantMap.values());
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Failed to load dashboard data', err);
        this.loading = false;
        this.cdr.detectChanges();
      }
    });
  }

  manageTenant(tenantId: string) {
    this.router.navigate(['/admin/users']); // Redirect to user management or a tenant specific page
  }
}
