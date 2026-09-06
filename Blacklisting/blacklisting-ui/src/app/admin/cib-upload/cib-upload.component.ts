import { Component, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-cib-upload',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './cib-upload.component.html'
})
export class CibUploadComponent implements OnInit {
  selectedFile: File | null = null;
  entityType: string = 'individual';
  isUploading = false;
  uploadMessage = '';
  lastUploadDate: string | null = null;

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.fetchLatestUploadInfo();
  }

  fetchLatestUploadInfo() {
    this.http.get<any>('http://127.0.0.1:8000/api/cib-blacklists/latest-upload', {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    }).subscribe({
      next: (res) => {
        this.lastUploadDate = res.last_upload_date;
      },
      error: (err) => console.error('Failed to fetch last upload date', err)
    });
  }

  onFileSelected(event: any) {
    this.selectedFile = event.target.files[0];
  }

  uploadFile() {
    if (!this.selectedFile) return;

    this.isUploading = true;
    this.uploadMessage = '';

    const formData = new FormData();
    formData.append('file', this.selectedFile);
    formData.append('entity_type', this.entityType);

    this.http.post<any>('http://127.0.0.1:8000/api/cib-blacklists/import', formData, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
    }).subscribe({
      next: (res) => {
        this.isUploading = false;
        this.uploadMessage = `Upload successful! ${res.released_records} old unlisted records were released.`;
        this.fetchLatestUploadInfo();
      },
      error: (err) => {
        this.isUploading = false;
        this.uploadMessage = 'Upload failed. Check console.';
        console.error(err);
      }
    });
  }
}
