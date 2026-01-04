
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { RouterModule, Router } from '@angular/router';

@Component({
  selector: 'app-change-password',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule, HttpClientModule],
  templateUrl: './change-password.html',
  styleUrls: ['./change-password.css']
})
export class ChangePassword {
  currentPassword: string = '';
  newPassword: string = '';
  confirmPassword: string = '';
  message: string = '';
  messageColor: string = 'red';

  constructor(private http: HttpClient, private router: Router) {}

  onChangePassword() {
    const token = localStorage.getItem('token');
    if (!token) {
      this.message = 'No token found. Please login.';
      this.messageColor = 'red';
      return;
    }

    const payload = {
      currentPassword: this.currentPassword,
      newPassword: this.newPassword,
      confirmPassword: this.confirmPassword
    };

    this.http.post('http://127.0.0.1:8000/api/change-password', payload, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
      .subscribe({
        next: (res: any) => {
          if (res.success) {
            // Clear local auth info and redirect to login
            localStorage.removeItem('token');
            localStorage.removeItem('user_id');
            localStorage.removeItem('user_type');

            this.message = res.message + ' Redirecting to login...';
            this.messageColor = 'green';
            this.currentPassword = '';
            this.newPassword = '';
            this.confirmPassword = '';

            setTimeout(() => {
              this.router.navigate(['/login']);
            }, 800);
          } else {
            this.message = res.message;
            this.messageColor = 'red';
          }
        },
        error: (err) => {
          // On 401/403, clear token and force re-login
          if (err.status === 401 || err.status === 403) {
            localStorage.removeItem('token');
            localStorage.removeItem('user_id');
            localStorage.removeItem('user_type');
            this.message = 'Session expired or unauthorized. Redirecting to login...';
            this.messageColor = 'red';
            setTimeout(() => this.router.navigate(['/login']), 800);
            return;
          }

          this.message = err.error?.message || 'Failed to change password.';
          this.messageColor = 'red';
        }
      });
  }

  scrollToFooter() {
    const footer = document.getElementById('footer');
    if (footer) {
      footer.scrollIntoView({ behavior: 'smooth' });
    }
  }
}
