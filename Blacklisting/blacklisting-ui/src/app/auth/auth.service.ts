import { Injectable, Inject, PLATFORM_ID } from '@angular/core';
import { isPlatformBrowser } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { BehaviorSubject, tap, catchError, of } from 'rxjs';

export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  tenant_id: string;
  staff_id?: string;
  branch?: string;
  branch_sol?: string;
  contact_number?: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://127.0.0.1:8000/api';
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  // 45 minutes session lifetime (in milliseconds)
  private readonly SESSION_DURATION_MS = 45 * 60 * 1000;
  private sessionTimer: any = null;
  private isBrowser: boolean;
  private listenersAttached = false;

  constructor(
    private http: HttpClient,
    private router: Router,
    @Inject(PLATFORM_ID) private platformId: Object
  ) {
    this.isBrowser = isPlatformBrowser(this.platformId);
    this.initSession();
  }

  private initSession() {
    if (!this.isBrowser) return;

    const savedUser = localStorage.getItem('currentUser');
    const expiryStr = localStorage.getItem('sessionExpiry');

    if (savedUser && expiryStr) {
      const expiry = parseInt(expiryStr, 10);
      const remainingTime = expiry - Date.now();

      if (remainingTime > 0) {
        try {
          const user = JSON.parse(savedUser);
          this.currentUserSubject.next(user);
          this.startSessionTimer(remainingTime);
          this.setupActivityListeners();
        } catch {
          this.destroySession();
        }
      } else {
        this.destroySession();
      }
    } else {
      this.destroySession();
    }
  }

  private setupActivityListeners() {
    if (!this.isBrowser || this.listenersAttached) return;
    this.listenersAttached = true;

    let lastReset = Date.now();
    const resetSession = () => {
      // Throttle activity updates to once every 30 seconds
      if (Date.now() - lastReset > 30000) {
        lastReset = Date.now();
        if (this.isLoggedIn()) {
          this.extendSession();
        }
      }
    };

    window.addEventListener('mousemove', resetSession, { passive: true });
    window.addEventListener('keydown', resetSession, { passive: true });
    window.addEventListener('click', resetSession, { passive: true });
  }

  private extendSession() {
    if (!this.isBrowser) return;

    const newExpiry = Date.now() + this.SESSION_DURATION_MS;
    localStorage.setItem('sessionExpiry', newExpiry.toString());
    this.startSessionTimer(this.SESSION_DURATION_MS);
  }

  private startSessionTimer(durationMs: number) {
    this.clearSessionTimer();
    this.sessionTimer = setTimeout(() => {
      console.warn('Session expired after 45 minutes of inactivity.');
      this.logoutAndRedirect();
    }, durationMs);
  }

  private clearSessionTimer() {
    if (this.sessionTimer) {
      clearTimeout(this.sessionTimer);
      this.sessionTimer = null;
    }
  }

  public get currentUserValue(): User | null {
    if (this.isBrowser && !this.isSessionValid()) {
      return null;
    }
    return this.currentUserSubject.value;
  }

  public isSessionValid(): boolean {
    if (!this.isBrowser) return true;

    const expiryStr = localStorage.getItem('sessionExpiry');
    if (!expiryStr) return false;

    const expiry = parseInt(expiryStr, 10);
    return Date.now() < expiry;
  }

  login(credentials: any) {
    return this.http.post<any>(`${this.apiUrl}/login`, credentials).pipe(
      tap(response => {
        if (response && response.user) {
          this.setSession(response.user);
        }
      })
    );
  }

  public setSession(user: User) {
    if (this.isBrowser) {
      const expiry = Date.now() + this.SESSION_DURATION_MS;
      localStorage.setItem('currentUser', JSON.stringify(user));
      localStorage.setItem('sessionExpiry', expiry.toString());
      this.startSessionTimer(this.SESSION_DURATION_MS);
      this.setupActivityListeners();
    }
    this.currentUserSubject.next(user);
  }

  logout() {
    this.destroySession();
    return this.http.post(`${this.apiUrl}/logout`, {}).pipe(
      catchError(() => of(true))
    );
  }

  public logoutAndRedirect() {
    this.destroySession();
    this.router.navigate(['/login']);
  }

  public destroySession() {
    this.clearSessionTimer();
    if (this.isBrowser) {
      localStorage.removeItem('currentUser');
      localStorage.removeItem('sessionExpiry');
    }
    this.currentUserSubject.next(null);
  }

  isLoggedIn(): boolean {
    return !!this.currentUserValue && this.isSessionValid();
  }

  hasRole(role: string | string[]): boolean {
    const user = this.currentUserValue;
    if (!user) return false;

    if (Array.isArray(role)) {
      return role.includes(user.role);
    }
    return user.role === role;
  }
}
