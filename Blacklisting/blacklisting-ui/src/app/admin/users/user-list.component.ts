import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-user-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './user-list.component.html',
})
export class UserListComponent implements OnInit {
  users: any[] = [];
  isModalOpen = false;
  editingUser: any = null;
  
  formData = {
    name: '',
    email: '',
    password: '',
    role: 'maker',
    tenant_id: 'bank_a',
    staff_id: '',
    branch: '',
    branch_sol: '',
    contact_number: ''
  };

  ngOnInit() {
    this.fetchUsers();
  }

  async fetchUsers() {
    try {
      const res = await fetch('http://127.0.0.1:8000/api/users');
      this.users = await res.json();
    } catch (e) {
      console.error(e);
    }
  }

  openModal(user?: any) {
    if (user) {
      this.editingUser = user;
      this.formData = { 
        ...user, 
        password: '',
        staff_id: user.staff_id || '',
        branch: user.branch || '',
        branch_sol: user.branch_sol || '',
        contact_number: user.contact_number || ''
      };
    } else {
      this.editingUser = null;
      this.formData = { 
        name: '', email: '', password: '', role: 'maker', tenant_id: 'bank_a',
        staff_id: '', branch: '', branch_sol: '', contact_number: ''
      };
    }
    this.isModalOpen = true;
  }

  closeModal() {
    this.isModalOpen = false;
  }

  async saveUser() {
    try {
      const url = this.editingUser 
        ? `http://127.0.0.1:8000/api/users/${this.editingUser.id}` 
        : `http://127.0.0.1:8000/api/users`;
      
      const method = this.editingUser ? 'PUT' : 'POST';

      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(this.formData)
      });

      if (res.ok) {
        this.closeModal();
        this.fetchUsers();
      } else {
        alert('Failed to save user. Please check if the email is already in use.');
      }
    } catch (e) {
      console.error(e);
    }
  }

  async deleteUser(id: number) {
    if(confirm('Are you sure you want to delete this user? This cannot be undone.')) {
      await fetch(`http://127.0.0.1:8000/api/users/${id}`, { method: 'DELETE' });
      this.fetchUsers();
    }
  }
}
