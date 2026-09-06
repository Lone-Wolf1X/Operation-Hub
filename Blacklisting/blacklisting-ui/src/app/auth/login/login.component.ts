import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.component.html',
})
export class LoginComponent {
  credentials = {
    email: 'admin@nextgen.com',
    password: 'admin'
  };
  isLoading = false;
  errorMessage = '';

  constructor(private authService: AuthService, private router: Router) {
    if (this.authService.isLoggedIn()) {
      this.redirectUser(this.authService.currentUserValue?.role);
    }
  }

  onSubmit() {
    this.isLoading = true;
    this.errorMessage = '';
    
    this.authService.login(this.credentials).subscribe({
      next: (res) => {
        this.isLoading = false;
        this.redirectUser(res.user.role);
      },
      error: (err) => {
        this.isLoading = false;
        this.errorMessage = 'Invalid email or password';
      }
    });
  }

  private redirectUser(role: string | undefined) {
    switch(role) {
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
