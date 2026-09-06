import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-hit-report',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './hit-report.html',
})
export class HitReport implements OnInit {
  searchQuery: string = '';
  results: any[] = [];
  isSearching: boolean = false;
  hasSearched: boolean = false;

  constructor(private route: ActivatedRoute, private http: HttpClient) {}

  ngOnInit() {
    this.route.queryParams.subscribe(params => {
      if (params['q']) {
        this.searchQuery = params['q'];
        this.performSearch();
      }
    });
  }

  performSearch() {
    this.isSearching = true;
    this.hasSearched = true;
    this.http.get<any[]>('http://127.0.0.1:8000/api/cib/search?q=' + encodeURIComponent(this.searchQuery)).subscribe({
      next: (data) => {
        this.results = data;
        this.isSearching = false;
      },
      error: (err) => {
        console.error(err);
        this.isSearching = false;
        this.results = [];
      }
    });
  }
}
