import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root'
})
export class RoleGuard implements CanActivate {
  
  constructor(private authService: AuthService, private router: Router) {}

  canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): boolean {
    if (!this.authService.isLoggedIn()) {
      this.authService.destroySession();
      this.router.navigate(['/login']);
      return false;
    }

    const currentUser = this.authService.currentUserValue;
    
    if (currentUser) {
      const roles = route.data['roles'] as Array<string>;
      if (roles && !this.authService.hasRole(roles)) {
        // User does not have the right role, redirect to appropriate dashboard
        this.redirectToDashboard(currentUser.role);
        return false;
      }
      return true;
    }

    // Not logged in so redirect to login page
    this.router.navigate(['/login']);
    return false;
  }

  private redirectToDashboard(role: string) {
    switch (role) {
      case 'superadmin':
        this.router.navigate(['/admin/super-dashboard']);
        break;
      case 'admin':
        this.router.navigate(['/admin/dashboard']);
        break;
      case 'maker':
        this.router.navigate(['/admin/maker-dashboard']);
        break;
      case 'checker':
        this.router.navigate(['/admin/checker-dashboard']);
        break;
      default:
        this.router.navigate(['/login']);
    }
  }
}
