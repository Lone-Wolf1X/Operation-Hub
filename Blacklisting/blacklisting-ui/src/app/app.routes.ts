import { Routes } from '@angular/router';
import { LoginComponent } from './auth/login/login.component';
import { AdminLayoutComponent } from './admin/layout/layout.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RoleGuard } from './auth/role.guard';
import { MakerDashboardComponent } from './admin/maker-dashboard/maker-dashboard.component';
import { CheckerDashboardComponent } from './admin/checker-dashboard/checker-dashboard.component';
import { SuperDashboard as SuperDashboardComponent } from './admin/super-dashboard/super-dashboard';
import { HitReport } from './admin/hit-report/hit-report';
import { Profile } from './admin/profile/profile';
import { LodgeCase } from './admin/lodge-case/lodge-case';
import { ApplicationWorkspace } from './admin/application-workspace/application-workspace';
import { CibUploadComponent } from './admin/cib-upload/cib-upload.component';
import { ScreeningComponent } from './admin/screening/screening.component';
import { UserListComponent } from './admin/users/user-list.component';
import { AllCasesComponent } from './admin/all-cases/all-cases.component';

export const routes: Routes = [
    { path: 'login', component: LoginComponent },
    { 
        path: 'admin', 
        component: AdminLayoutComponent,
        canActivate: [RoleGuard],
        data: { roles: ['superadmin', 'admin', 'maker', 'checker'] },
        children: [
            { 
                path: 'super-dashboard', 
                component: SuperDashboardComponent,
                canActivate: [RoleGuard],
                data: { roles: ['superadmin'] } 
            },
            { 
                path: 'dashboard', 
                component: DashboardComponent,
                canActivate: [RoleGuard],
                data: { roles: ['admin'] } 
            },
            { 
                path: 'maker-dashboard', 
                component: MakerDashboardComponent,
                canActivate: [RoleGuard],
                data: { roles: ['admin', 'maker'] } 
            },
            { 
                path: 'checker-dashboard', 
                component: CheckerDashboardComponent,
                canActivate: [RoleGuard],
                data: { roles: ['admin', 'checker'] } 
            },
            { 
                path: 'users', 
                component: UserListComponent,
                canActivate: [RoleGuard],
                data: { roles: ['superadmin', 'admin'] }
            },
            {
                path: 'hit-report',
                component: HitReport,
                canActivate: [RoleGuard],
                data: { roles: ['superadmin', 'admin', 'maker', 'checker'] }
            },

            {
                path: 'profile',
                component: Profile,
                canActivate: [RoleGuard],
                data: { roles: ['superadmin', 'admin', 'maker', 'checker'] }
            },
            {
                path: 'lodge-case',
                component: LodgeCase,
                canActivate: [RoleGuard],
                data: { roles: ['maker', 'admin', 'superadmin'] }
            },
            {
                path: 'all-cases',
                component: AllCasesComponent,
                canActivate: [RoleGuard],
                data: { roles: ['maker', 'checker', 'admin', 'superadmin'] }
            },
            {
                path: 'application/:id',
                component: ApplicationWorkspace,
                canActivate: [RoleGuard],
                data: { roles: ['maker', 'checker', 'admin', 'superadmin'] }
            },
            {
                path: 'cib-upload',
                component: CibUploadComponent,
                canActivate: [RoleGuard],
                data: { roles: ['admin', 'superadmin'] }
            },
            {
                path: 'screening',
                component: ScreeningComponent,
                canActivate: [RoleGuard],
                data: { roles: ['maker', 'checker', 'admin', 'superadmin'] }
            },
            { path: '', redirectTo: 'dashboard', pathMatch: 'full' }
        ]
    },
    { path: '', redirectTo: 'login', pathMatch: 'full' }
];
