import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { BaseChartDirective } from 'ng2-charts';
import { ChartConfiguration, ChartData, ChartType } from 'chart.js';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, BaseChartDirective],
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  stats: any = {
    individuals: 0,
    units: 0,
    blacklisted: 0,
    released: 0
  };

  uploadMessage: string = '';
  isUploading: boolean = false;

  public barChartOptions: ChartConfiguration['options'] = {
    responsive: true,
    plugins: {
      legend: { display: true },
    }
  };
  public barChartType: ChartType = 'bar';
  public barChartData: ChartData<'bar'> = {
    labels: [ 'Individuals', 'Units' ],
    datasets: [
      { data: [0, 0], label: 'Blacklisted Entities', backgroundColor: ['#ef4444', '#3b82f6'] }
    ]
  };

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.fetchStats();
  }

  fetchStats() {
    this.http.get<any>('http://127.0.0.1:8000/api/cib/stats').subscribe({
      next: (data) => {
        this.stats = data;
        this.barChartData.datasets[0].data = [data.individuals, data.units];
      },
      error: (err) => console.error(err)
    });
  }

  onFileSelected(event: any) {
    const file: File = event.target.files[0];
    if (file) {
      this.isUploading = true;
      this.uploadMessage = '';
      
      const formData = new FormData();
      formData.append('file', file);

      this.http.post('http://127.0.0.1:8000/api/cib/upload', formData).subscribe({
        next: (res: any) => {
          this.uploadMessage = 'CIB Data uploaded successfully!';
          this.isUploading = false;
          this.fetchStats(); // Refresh stats
        },
        error: (err) => {
          this.uploadMessage = 'Upload failed. Please check the file format.';
          this.isUploading = false;
        }
      });
    }
  }
}
