import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterOutlet, RouterModule, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { AuthService, User } from '../../auth/auth.service';

@Component({
  selector: 'app-admin-layout',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterModule, FormsModule],
  templateUrl: './layout.component.html',
})
export class AdminLayoutComponent implements OnInit {
  currentUser: User | null = null;
  dashboardLink = '/admin/dashboard';
  searchQuery: string = '';
  
  isSidebarOpen: boolean = true;
  isProfileDropdownOpen: boolean = false;

  constructor(public authService: AuthService, private router: Router) {}

  toggleSidebar() {
    this.isSidebarOpen = !this.isSidebarOpen;
  }

  toggleProfileDropdown() {
    this.isProfileDropdownOpen = !this.isProfileDropdownOpen;
  }

  // Close dropdown when clicking outside (simple logic handled in template for now)

  onSearch(event: Event) {
    event.preventDefault();
    if (this.searchQuery.trim()) {
      this.router.navigate(['/admin/screening'], { queryParams: { q: this.searchQuery.trim() } });
    }
  }

  ngOnInit() {
    this.authService.currentUser$.subscribe(user => {
      this.currentUser = user;
      if (user) {
        if (user.role === 'superadmin') this.dashboardLink = '/admin/super-dashboard';
        else if (user.role === 'maker') this.dashboardLink = '/admin/maker-dashboard';
        else if (user.role === 'checker') this.dashboardLink = '/admin/checker-dashboard';
        else this.dashboardLink = '/admin/dashboard';
      }
    });
  }

  getInitials(): string {
    if (!this.currentUser || !this.currentUser.name) return 'U';
    const names = this.currentUser.name.split(' ');
    if (names.length >= 2) {
      return names[0].charAt(0).toUpperCase() + names[1].charAt(0).toUpperCase();
    }
    return names[0].charAt(0).toUpperCase();
  }

  logout(event: Event) {
    event.preventDefault();
    this.authService.logout().subscribe(() => {
      this.router.navigate(['/login']);
    });
  }
}
