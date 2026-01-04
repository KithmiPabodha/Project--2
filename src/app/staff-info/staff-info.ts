import { Component} from '@angular/core';
import { HttpClient, HttpClientModule } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { Router } from '@angular/router'; 

@Component({
  selector: 'app-staff-info',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule, HttpClientModule],
  templateUrl: './staff-info.html',
  styleUrls: ['./staff-info.css']
})

export class StaffInfo {
  staff = {
    staffId: sessionStorage.getItem('staffId') || '',
    name: '',
    email: '',
    password: '',
    confirm: ''
  };

  constructor(private http: HttpClient, private router: Router) {}

   onSubmit() {
    if (this.staff.password !== this.staff.confirm) {
      alert('Passwords do not match!');
      return;
    }

    this.http.post<any>('http://127.0.0.1:8000/api/staff-register', this.staff)
      .subscribe({
        next: (res) => {
          alert(res.message);
          if (res.redirect) {
            sessionStorage.removeItem('staffId');
            this.router.navigate([res.redirect]);
          }
        },
        error: (err) => {
          alert(err.error.message || 'Registration failed');
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