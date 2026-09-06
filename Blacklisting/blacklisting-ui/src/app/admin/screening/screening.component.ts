import { Component } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-screening',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './screening.component.html'
})
export class ScreeningComponent {
  entityType: 'individual' | 'institutional' = 'individual';
  
  // Individual fields
  fullName: string = '';
  citizenshipNumber: string = '';
  fatherName: string = '';
  dateOfBirth: string = '';
  
  // Institutional fields
  companyName: string = '';
  panNumber: string = '';
  panIssueDate: string = '';
  regDate: string = '';

  isSearching = false;
  hasSearched = false;
  
  isBlacklisted = false;
  matches: any[] = [];
  
  logs: any[] = [];
  activeTab: string = 'search';

  constructor(private http: HttpClient) {}

  screenEntity() {
    this.isSearching = true;
    this.hasSearched = false;

    let payload: any = { entity_type: this.entityType };
    
    if (this.entityType === 'individual') {
      payload.name = this.fullName;
      payload.citizenship_number = this.citizenshipNumber;
      payload.father_name = this.fatherName;
      payload.date_of_birth = this.dateOfBirth;
    } else {
      payload.name = this.companyName;
      payload.pan = this.panNumber;
      payload.pan_issue_date = this.panIssueDate;
      payload.reg_date = this.regDate;
    }

    this.http.post<any>('http://127.0.0.1:8000/api/screening/screen', payload, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    }).subscribe({
      next: (res) => {
        this.isSearching = false;
        this.hasSearched = true;
        this.isBlacklisted = res.is_blacklisted;
        this.matches = res.matches;
      },
      error: (err) => {
        this.isSearching = false;
        console.error(err);
      }
    });
  }

  fetchLogs() {
    this.activeTab = 'logs';
    this.http.get<any>('http://127.0.0.1:8000/api/screening/logs', {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    }).subscribe({
      next: (res) => {
        this.logs = res.data || [];
      },
      error: (err) => console.error(err)
    });
  }

  printReport() {
    window.print();
  }
}
